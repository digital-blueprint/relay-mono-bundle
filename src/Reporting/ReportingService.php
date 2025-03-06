<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Reporting;

use Dbp\Relay\MonoBundle\Config\ConfigurationService;
use Dbp\Relay\MonoBundle\Config\EmailConfig;
use Dbp\Relay\MonoBundle\Config\PaymentProfile;
use Dbp\Relay\MonoBundle\Persistence\PaymentPersistence;
use Dbp\Relay\MonoBundle\Persistence\PaymentPersistenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
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

    public function __construct(ConfigurationService $configurationService, EntityManagerInterface $em)
    {
        $this->configurationService = $configurationService;
        $this->em = $em;
        $this->logger = new NullLogger();
    }

    public function sendAllReporting(?string $overrideEmail = null)
    {
        $paymentProfiles = $this->configurationService->getPaymentProfiles();

        foreach ($paymentProfiles as $paymentProfile) {
            $this->sendReporting($paymentProfile, $overrideEmail);
        }
    }

    public function sendReporting(PaymentProfile $paymentProfile, ?string $overrideEmail = null)
    {
        $reportingConfig = $paymentProfile->getReportingConfig();
        if ($reportingConfig === null) {
            return;
        }

        $repo = $this->em->getRepository(PaymentPersistence::class);
        assert($repo instanceof PaymentPersistenceRepository);

        $this->logger->debug('Send reporting for: '.$paymentProfile->getType());

        $type = $paymentProfile->getType();
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $createdSince = $now->sub(new \DateInterval($reportingConfig->getCreatedBegin()));

        $count = $repo->countByTypeCreatedSince($type, $createdSince);

        // We want a report every day, even if there are no payments
        $context = [
            'paymentProfile' => $paymentProfile,
            'createdSince' => $createdSince,
            'createdTo' => $now,
            'count' => $count,
        ];

        $this->sendEmail($reportingConfig, $context, $overrideEmail);
    }

    public function sendNotifyError(PaymentProfile $paymentProfile)
    {
        $notifyErrorConfig = $paymentProfile->getNotifyErrorConfig();
        if ($notifyErrorConfig === null) {
            return;
        }

        $repo = $this->em->getRepository(PaymentPersistence::class);
        assert($repo instanceof PaymentPersistenceRepository);

        $this->logger->debug('Send notify error for: '.$paymentProfile->getType());

        $type = $paymentProfile->getType();
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $completedSince = $now->sub(new \DateInterval($notifyErrorConfig->getCompletedBegin()));
        $items = $repo->findUnnotifiedByTypeCompletedSince($type, $completedSince);
        $count = count($items);

        if ($count) {
            $context = [
                'paymentProfile' => $paymentProfile,
                'items' => $items,
                'count' => $count,
            ];

            $this->sendEmail($notifyErrorConfig, $context);
        }
    }

    private function sendEmail(EmailConfig $config, array $context, ?string $overrideEmail = null)
    {
        $loader = new FilesystemLoader(dirname(__FILE__).'/../Resources/views/');
        $twig = new Environment($loader);

        $template = $twig->load($config->getHtmlTemplate());
        $html = $template->render($context);

        $transport = Transport::fromDsn($config->getDsn());
        $mailer = new Mailer($transport);

        $to = $overrideEmail ?? $config->getTo();

        $email = (new Email())
            ->from($config->getFrom())
            ->to($to)
            ->subject($config->getSubject())
            ->html($html);

        $this->logger->debug('Sending email to: '.$to);
        $mailer->send($email);
    }
}
