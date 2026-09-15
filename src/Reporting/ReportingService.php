<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Reporting;

use Dbp\Relay\MonoBundle\Config\ConfigurationService;
use Dbp\Relay\MonoBundle\Config\EmailConfig;
use Dbp\Relay\MonoBundle\Config\PaymentType;
use Dbp\Relay\MonoBundle\Config\ReportingConfig;
use Dbp\Relay\MonoBundle\Persistence\PaymentPersistence;
use Dbp\Relay\MonoBundle\Persistence\PaymentPersistenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class ReportingService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const REPORTING_TEMPLATE = 'reporting.html.twig';
    private const NOTIFY_ERROR_TEMPLATE = 'notify-error.html.twig';

    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var ClockInterface
     */
    private $clock;

    public function __construct(ConfigurationService $configurationService, EntityManagerInterface $em, ?ClockInterface $clock)
    {
        $this->configurationService = $configurationService;
        $this->em = $em;
        $this->clock = $clock;
        $this->logger = new NullLogger();
    }

    public function sendAllReporting(?string $overrideEmail = null, ?string $cadence = null): void
    {
        $paymentTypes = $this->configurationService->getPaymentTypes();

        foreach ($paymentTypes as $paymentType) {
            $reportingConfig = $paymentType->getReportingConfig();
            if ($reportingConfig === null || ($cadence !== null && $reportingConfig->getCadence() !== $cadence)) {
                continue;
            }
            $this->sendReporting($paymentType, $overrideEmail);
        }
    }

    public function sendReporting(PaymentType $paymentType, ?string $overrideEmail = null): void
    {
        $email = $this->buildReportingEmail($paymentType, $overrideEmail);
        if ($email !== null) {
            $this->sendBuiltEmail($email, $paymentType->getReportingConfig());
        }
    }

    public function buildReportingEmail(PaymentType $paymentType, ?string $overrideEmail = null): ?Email
    {
        $reportingConfig = $paymentType->getReportingConfig();
        if ($reportingConfig === null) {
            return null;
        }

        $repo = $this->em->getRepository(PaymentPersistence::class);
        assert($repo instanceof PaymentPersistenceRepository);

        $this->logger->debug('Build reporting email for: '.$paymentType->getIdentifier());

        $type = $paymentType->getIdentifier();
        $now = $this->clock->now();
        [$periodStart, $periodEnd] = $this->getReportingPeriod($reportingConfig->getCadence(), $now);

        $outcomes = $repo->countByTypeCreatedBetween($type, $periodStart, $periodEnd);
        $completedAndNotified = $repo->countNotifiedCompletedByTypeCreatedBetween($type, $periodStart, $periodEnd);

        $context = [
            'paymentType' => $paymentType,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'activity' => [
                'created' => array_sum($outcomes),
                'started' => $repo->countByTypeStartedBetween($type, $periodStart, $periodEnd),
                'completed' => $repo->countByTypeCompletedBetween($type, $periodStart, $periodEnd),
                'notified' => $repo->countByTypeNotifiedBetween($type, $periodStart, $periodEnd),
            ],
            'outcomes' => $outcomes,
            'completedAndNotified' => $completedAndNotified,
            'completedNotNotified' => ($outcomes['completed'] ?? 0) - $completedAndNotified,
        ];

        return $this->buildEmail($reportingConfig, $context, self::REPORTING_TEMPLATE, $overrideEmail);
    }

    /**
     * @return array{\DateTimeImmutable, \DateTimeImmutable}
     */
    private function getReportingPeriod(string $cadence, \DateTimeImmutable $now): array
    {
        $now = $now->setTimezone(new \DateTimeZone('UTC'));

        switch ($cadence) {
            case ReportingConfig::CADENCE_HOURLY:
                $periodEnd = $now->setTime((int) $now->format('H'), 0);
                $periodStart = $periodEnd->sub(new \DateInterval('PT1H'));
                break;
            case ReportingConfig::CADENCE_DAILY:
                $periodEnd = $now->setTime(0, 0);
                $periodStart = $periodEnd->sub(new \DateInterval('P1D'));
                break;
            case ReportingConfig::CADENCE_WEEKLY:
                $periodEnd = $now->modify('monday this week')->setTime(0, 0);
                $periodStart = $periodEnd->sub(new \DateInterval('P7D'));
                break;
            default:
                throw new \InvalidArgumentException("Unknown reporting cadence: $cadence");
        }

        return [$periodStart, $periodEnd];
    }

    public function sendNotifyError(PaymentType $paymentType): void
    {
        $email = $this->buildNotifyErrorEmail($paymentType);
        if ($email !== null) {
            $this->sendBuiltEmail($email, $paymentType->getNotifyErrorConfig());
        }
    }

    public function sendAllNotifyErrors(?string $cadence = null): void
    {
        foreach ($this->configurationService->getPaymentTypes() as $paymentType) {
            $notifyErrorConfig = $paymentType->getNotifyErrorConfig();
            if ($notifyErrorConfig === null || ($cadence !== null && $notifyErrorConfig->getCadence() !== $cadence)) {
                continue;
            }
            $this->sendNotifyError($paymentType);
        }
    }

    public function buildNotifyErrorEmail(PaymentType $paymentType): ?Email
    {
        $notifyErrorConfig = $paymentType->getNotifyErrorConfig();
        if ($notifyErrorConfig === null) {
            return null;
        }

        $repo = $this->em->getRepository(PaymentPersistence::class);
        assert($repo instanceof PaymentPersistenceRepository);

        $this->logger->debug('Build notify error email for: '.$paymentType->getIdentifier());

        $type = $paymentType->getIdentifier();
        $now = $this->clock->now();
        $completedBefore = $now->sub(new \DateInterval($notifyErrorConfig->getReportAfter()));
        $items = $repo->findUnnotifiedByTypeCompletedBefore($type, $completedBefore);
        $count = count($items);

        if ($count === 0) {
            return null;
        }

        $context = [
            'paymentType' => $paymentType,
            'items' => $items,
            'count' => $count,
        ];

        return $this->buildEmail($notifyErrorConfig, $context, self::NOTIFY_ERROR_TEMPLATE);
    }

    /**
     * @param mixed[] $context
     */
    private function buildEmail(EmailConfig $config, array $context, string $htmlTemplate, ?string $overrideEmail = null): Email
    {
        $loader = new FilesystemLoader(dirname(__FILE__));
        $twig = new Environment($loader);

        $template = $twig->load($htmlTemplate);
        $html = $template->render($context);

        $to = $overrideEmail ?? $config->getTo();

        return (new Email())
            ->from($config->getFrom())
            ->to($to)
            ->subject($config->getSubject())
            ->html($html);
    }

    private function sendBuiltEmail(Email $email, EmailConfig $config): void
    {
        $transport = Transport::fromDsn($config->getDsn());
        $mailer = new Mailer($transport);
        $this->logger->debug('Sending email to: '.$email->getTo()[0]->getAddress());
        $mailer->send($email);
    }
}
