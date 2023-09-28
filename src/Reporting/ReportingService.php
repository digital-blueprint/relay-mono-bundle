<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Reporting;

use Dbp\Relay\MonoBundle\Config\ConfigurationService;
use Dbp\Relay\MonoBundle\Config\PaymentType;
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

    public function sendAllReporting(string $email = '')
    {
        $paymentTypes = $this->configurationService->getPaymentTypes();

        foreach ($paymentTypes as $paymentType) {
            if ($paymentType->getReportingConfig()) {
                $this->sendReporting($paymentType, $email);
            }
        }
    }

    public function sendReporting(PaymentType $paymentType, string $email = '')
    {
        $repo = $this->em->getRepository(PaymentPersistence::class);
        assert($repo instanceof PaymentPersistenceRepository);

        $this->logger->debug('Send reporting for: '.$paymentType->getIdentifier());

        $reportingConfig = $paymentType->getReportingConfig();

        $type = $paymentType->getIdentifier();
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $createdSince = $now->sub(new \DateInterval($reportingConfig['created_begin']));

        $count = $repo->countByTypeCreatedSince($type, $createdSince);

//        if (count($count)) {
        // We want a report every day, even if there are no payments
        if (true) {
            $context = [
                'paymentType' => $paymentType,
                'createdSince' => $createdSince,
                'createdTo' => $now,
                'count' => $count,
            ];

            if ($email !== '') {
                $reportingConfig['to'] = $email;
            }

            $this->sendEmail($reportingConfig, $context);
        }
    }

    public function sendNotifyError(PaymentType $paymentType)
    {
        $repo = $this->em->getRepository(PaymentPersistence::class);
        assert($repo instanceof PaymentPersistenceRepository);

        $this->logger->debug('Send notify error for: '.$paymentType->getIdentifier());

        $notifyErrorConfig = $paymentType->getNotifyErrorConfig();

        $type = $paymentType->getIdentifier();
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $completedSince = $now->sub(new \DateInterval($notifyErrorConfig['completed_begin']));
        $items = $repo->findUnnotifiedByTypeCompletedSince($type, $completedSince);
        $count = count($items);

        if ($count) {
            $context = [
                'paymentType' => $paymentType,
                'items' => $items,
                'count' => $count,
            ];

            $this->sendEmail($notifyErrorConfig, $context);
        }
    }

    private function sendEmail(array $config, array $context)
    {
        $loader = new FilesystemLoader(dirname(__FILE__).'/../Resources/views/');
        $twig = new Environment($loader);

        $template = $twig->load($config['html_template']);
        $html = $template->render($context);

        $transport = Transport::fromDsn($config['dsn']);
        $mailer = new Mailer($transport);

        $email = (new Email())
            ->from($config['from'])
            ->to($config['to'])
            ->subject($config['subject'])
            ->html($html);

        $this->logger->debug('Sending email to: '.$config['to']);
        $mailer->send($email);
    }
}
