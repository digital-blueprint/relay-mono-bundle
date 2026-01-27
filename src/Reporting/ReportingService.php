<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Reporting;

use Dbp\Relay\MonoBundle\Config\ConfigurationService;
use Dbp\Relay\MonoBundle\Config\EmailConfig;
use Dbp\Relay\MonoBundle\Config\PaymentType;
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

    public function sendAllReporting(?string $overrideEmail = null): void
    {
        $paymentTypes = $this->configurationService->getPaymentTypes();

        foreach ($paymentTypes as $paymentType) {
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
        $createdSince = $now->sub(new \DateInterval($reportingConfig->getCreatedBegin()));

        $count = $repo->countByTypeCreatedSince($type, $createdSince);

        $context = [
            'paymentType' => $paymentType,
            'createdSince' => $createdSince,
            'createdTo' => $now,
            'count' => $count,
        ];

        return $this->buildEmail($reportingConfig, $context, $overrideEmail);
    }

    public function sendNotifyError(PaymentType $paymentType): void
    {
        $email = $this->buildNotifyErrorEmail($paymentType);
        if ($email !== null) {
            $this->sendBuiltEmail($email, $paymentType->getNotifyErrorConfig());
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
        $completedSince = $now->sub(new \DateInterval($notifyErrorConfig->getCompletedBegin()));
        $items = $repo->findUnnotifiedByTypeCompletedSince($type, $completedSince);
        $count = count($items);

        if ($count === 0) {
            return null;
        }

        $context = [
            'paymentType' => $paymentType,
            'items' => $items,
            'count' => $count,
        ];

        return $this->buildEmail($notifyErrorConfig, $context);
    }

    /**
     * @param mixed[] $context
     */
    private function buildEmail(EmailConfig $config, array $context, ?string $overrideEmail = null): Email
    {
        $loader = new FilesystemLoader(dirname(__FILE__).'/../Resources/views/');
        $twig = new Environment($loader);

        $template = $twig->load($config->getHtmlTemplate());
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
