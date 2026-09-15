<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use Dbp\Relay\CoreBundle\Cron\CronManager;
use Dbp\Relay\MonoBundle\Config\ConfigurationService;
use Dbp\Relay\MonoBundle\Config\ReportingConfig;
use Dbp\Relay\MonoBundle\Persistence\PaymentPersistence;
use Dbp\Relay\MonoBundle\Persistence\PaymentStatus;
use Dbp\Relay\MonoBundle\Reporting\NotifyErrorCronJob;
use Dbp\Relay\MonoBundle\Reporting\ReportingCronJob;
use Dbp\Relay\MonoBundle\Reporting\ReportingService;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\Translation\IdentityTranslator;

class ReportingServiceTest extends KernelTestCase
{
    private EntityManager $em;
    private ConfigurationService $configService;

    public function setUp(): void
    {
        $container = $this->getContainer();
        $registry = $container->get('doctrine');
        assert($registry instanceof Registry);
        $em = $registry->getManager('dbp_relay_mono_bundle');
        assert($em instanceof EntityManager);
        $this->em = $em;
        $this->em->clear();
        $metaData = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->em);
        $schemaTool->updateSchema($metaData);

        $stack = new RequestStack();
        $urlHelper = new UrlHelper($stack);
        $this->configService = new ConfigurationService(new IdentityTranslator(), $urlHelper);
    }

    public function tearDown(): void
    {
        $schemaTool = new SchemaTool($this->em);
        $schemaTool->dropDatabase();
    }

    private function createPaymentPersistence(string $id, string $type, ClockInterface $clock): PaymentPersistence
    {
        $payment = new PaymentPersistence();
        $payment->setIdentifier($id);
        $payment->setType($type);
        $payment->setData('test-data');
        $payment->setClientIp('127.0.0.1');
        $payment->setReturnUrl('https://return.example.com');
        $payment->setNotifyUrl('https://notify.example.com');
        $payment->setPspReturnUrl('https://psp.example.com');
        $payment->setLocalIdentifier('local-'.$id);
        $payment->setPaymentReference('ref-'.$id);
        $payment->setAmount('100');
        $payment->setCurrency('EUR');
        $payment->setRecipient('Test Recipient');
        $payment->setPaymentMethod('test-method');
        $payment->setDataProtectionDeclarationUrl('https://data.example.com');
        $payment->setDataUpdatedAt($clock->now());
        $payment->setPaymentStatus(PaymentStatus::PREPARED);

        return $payment;
    }

    private function configureReporting(string $cadence): void
    {
        $this->configService->setConfig([
            'database_url' => 'sqlite:///:memory:',
            'payment_types' => [
                'test-payment' => [
                    'backend_type' => 'test',
                    'service' => 'test',
                    'auth_required' => false,
                    'session_timeout' => 'PT1H',
                    'return_url_expression' => 'true',
                    'return_url_override' => null,
                    'notify_url_expression' => 'true',
                    'psp_return_url_expression' => 'true',
                    'data_protection_declaration_url' => null,
                    'recipient' => 'Test Recipient',
                    'disabled' => false,
                    'concurrency_limits' => [
                        'max_concurrent_payments' => null,
                        'max_concurrent_auth_payments' => null,
                        'max_concurrent_auth_payments_per_user' => null,
                        'max_concurrent_unauth_payments' => null,
                        'max_concurrent_unauth_payments_per_ip' => null,
                    ],
                    'reporting' => [
                        'dsn' => 'null://null',
                        'from' => 'sender@example.com',
                        'to' => 'recipient@example.com',
                        'subject' => 'Payment Report',
                        'cadence' => $cadence,
                    ],
                ],
            ],
        ]);
    }

    public function testSendReporting(): void
    {
        $this->configureReporting(ReportingConfig::CADENCE_DAILY);

        $clock = new MockClock('2024-03-15 14:30:00', 'UTC');
        $payment1 = $this->createPaymentPersistence('payment-1', 'test-payment', $clock);
        $payment1->setPaymentStatus(PaymentStatus::COMPLETED);
        $payment1->setCreatedAt(new \DateTimeImmutable('2024-03-14 22:30:00 UTC'));
        $payment1->setStartedAt(new \DateTimeImmutable('2024-03-14 22:45:00 UTC'));
        $payment1->setCompletedAt(new \DateTimeImmutable('2024-03-14 23:00:00 UTC'));
        $payment1->setNotifiedAt(new \DateTimeImmutable('2024-03-14 23:05:00 UTC'));
        $this->em->persist($payment1);

        $payment2 = $this->createPaymentPersistence('payment-2', 'test-payment', $clock);
        $payment2->setPaymentStatus(PaymentStatus::PREPARED);
        $payment2->setCreatedAt(new \DateTimeImmutable('2024-03-14 23:30:00 UTC'));
        $this->em->persist($payment2);

        $payment3 = $this->createPaymentPersistence('payment-3', 'test-payment', $clock);
        $payment3->setPaymentStatus(PaymentStatus::PENDING);
        $payment3->setCreatedAt(new \DateTimeImmutable('2024-03-13 12:00:00 UTC'));
        $payment3->setStartedAt(new \DateTimeImmutable('2024-03-14 12:00:00 UTC'));
        $this->em->persist($payment3);

        $payment4 = $this->createPaymentPersistence('payment-4', 'test-payment', $clock);
        $payment4->setPaymentStatus(PaymentStatus::COMPLETED);
        $payment4->setCreatedAt(new \DateTimeImmutable('2024-03-13 10:00:00 UTC'));
        $payment4->setStartedAt(new \DateTimeImmutable('2024-03-13 10:15:00 UTC'));
        $payment4->setCompletedAt(new \DateTimeImmutable('2024-03-14 14:00:00 UTC'));
        $this->em->persist($payment4);

        $currentDayPayment = $this->createPaymentPersistence('current-day-payment', 'test-payment', $clock);
        $currentDayPayment->setCreatedAt(new \DateTimeImmutable('2024-03-15 01:00:00 UTC'));
        $this->em->persist($currentDayPayment);

        $this->em->flush();

        $paymentType = $this->configService->getPaymentTypeByType('test-payment');
        $service = new ReportingService($this->configService, $this->em, $clock);

        $email = $service->buildReportingEmail($paymentType);

        $this->assertNotNull($email);
        $this->assertSame('sender@example.com', $email->getFrom()[0]->getAddress());
        $this->assertSame('recipient@example.com', $email->getTo()[0]->getAddress());
        $this->assertSame('Payment Report', $email->getSubject());

        $html = $email->getHtmlBody();
        assert(is_string($html));

        $expectedHtml = <<<'HTML'
            <html>
            <body>
                <h2>Payment report: test-payment</h2>
                <p>
                    Reporting period: 14.03.2024 00:00:00 UTC
                    to 15.03.2024 00:00:00 UTC
                </p>

                <h3>Activity during the reporting period</h3>
                <table>
                    <tr>
                        <th>Payment sessions prepared</th>
                        <td>2</td>
                    </tr>
                    <tr>
                        <th>Payments started</th>
                        <td>2</td>
                    </tr>
                    <tr>
                        <th>Payments completed</th>
                        <td>2</td>
                    </tr>
                    <tr>
                        <th>Completed payments reported to backend</th>
                        <td>1</td>
                    </tr>
                </table>

                <h3>Current outcomes of payment sessions prepared during the reporting period</h3>
                <table>
                    <tr>
                        <th>Completed and reported to backend</th>
                        <td>1</td>
                    </tr>
                    <tr>
                        <th>Completed, awaiting backend reporting</th>
                        <td>0</td>
                    </tr>
                    <tr>
                        <th>Failed</th>
                        <td>0</td>
                    </tr>
                    <tr>
                        <th>Pending, awaiting final result</th>
                        <td>0</td>
                    </tr>
                    <tr>
                        <th>Prepared but not yet started</th>
                        <td>1</td>
                    </tr>
                </table>

            </body>
            </html>
            HTML;

        $this->assertXmlStringEqualsXmlString($expectedHtml, $html);
    }

    public function testReportingCadencePeriods(): void
    {
        $clock = new MockClock('2024-03-15 14:30:00', 'UTC');
        $service = new ReportingService($this->configService, $this->em, $clock);
        $expectedPeriods = [
            ReportingConfig::CADENCE_HOURLY => ['15.03.2024 13:00:00 UTC', '15.03.2024 14:00:00 UTC'],
            ReportingConfig::CADENCE_DAILY => ['14.03.2024 00:00:00 UTC', '15.03.2024 00:00:00 UTC'],
            ReportingConfig::CADENCE_WEEKLY => ['04.03.2024 00:00:00 UTC', '11.03.2024 00:00:00 UTC'],
        ];

        foreach ($expectedPeriods as $cadence => [$expectedStart, $expectedEnd]) {
            $this->configureReporting($cadence);
            $paymentType = $this->configService->getPaymentTypeByType('test-payment');
            $email = $service->buildReportingEmail($paymentType);
            $this->assertNotNull($email);
            $html = $email->getHtmlBody();
            assert(is_string($html));
            $this->assertStringContainsString("Reporting period: $expectedStart", $html);
            $this->assertStringContainsString("to $expectedEnd", $html);
        }
    }

    public function testReportingCronJobs(): void
    {
        $cronManager = $this->getContainer()->get(CronManager::class);
        assert($cronManager instanceof CronManager);

        $reportingJobs = array_values(array_filter(
            $cronManager->getJobs(),
            static fn (object $job): bool => $job instanceof ReportingCronJob
        ));
        $jobsByName = [];
        foreach ($reportingJobs as $reportingJob) {
            $jobsByName[$reportingJob->getName()] = $reportingJob->getInterval();
        }
        ksort($jobsByName);

        $this->assertSame([
            'Mono daily payment reporting' => '0 0 * * *',
            'Mono hourly payment reporting' => '0 * * * *',
            'Mono weekly payment reporting' => '0 0 * * 1',
        ], $jobsByName);

        $notifyErrorJobs = array_values(array_filter(
            $cronManager->getJobs(),
            static fn (object $job): bool => $job instanceof NotifyErrorCronJob
        ));
        $notifyErrorJobsByName = [];
        foreach ($notifyErrorJobs as $notifyErrorJob) {
            $notifyErrorJobsByName[$notifyErrorJob->getName()] = $notifyErrorJob->getInterval();
        }
        ksort($notifyErrorJobsByName);

        $this->assertSame([
            'Mono daily payment notify error reporting' => '0 0 * * *',
            'Mono hourly payment notify error reporting' => '0 * * * *',
            'Mono weekly payment notify error reporting' => '0 0 * * 1',
        ], $notifyErrorJobsByName);
    }

    public function testSendNotifyError(): void
    {
        $this->configService->setConfig([
            'database_url' => 'sqlite:///:memory:',
            'payment_types' => [
                'test-payment' => [
                    'backend_type' => 'test',
                    'service' => 'test',
                    'auth_required' => false,
                    'session_timeout' => 'PT1H',
                    'return_url_expression' => 'true',
                    'return_url_override' => null,
                    'notify_url_expression' => 'true',
                    'psp_return_url_expression' => 'true',
                    'data_protection_declaration_url' => null,
                    'recipient' => 'Test Recipient',
                    'disabled' => false,
                    'concurrency_limits' => [
                        'max_concurrent_payments' => null,
                        'max_concurrent_auth_payments' => null,
                        'max_concurrent_auth_payments_per_user' => null,
                        'max_concurrent_unauth_payments' => null,
                        'max_concurrent_unauth_payments_per_ip' => null,
                    ],
                    'notify_error' => [
                        'dsn' => 'null://null',
                        'from' => 'sender@example.com',
                        'to' => 'admin@example.com',
                        'subject' => 'Payment Errors',
                        'report_after' => 'PT15M',
                        'cadence' => 'daily',
                    ],
                ],
            ],
        ]);

        $clock = new MockClock('2024-03-15 14:30:00 UTC');

        $oldPayment = $this->createPaymentPersistence('old-payment', 'test-payment', $clock);
        $oldPayment->setPaymentStatus(PaymentStatus::COMPLETED);
        $oldPayment->setCompletedAt($clock->now()->modify('-2 days'));
        $this->em->persist($oldPayment);

        $boundaryPayment = $this->createPaymentPersistence('boundary-payment', 'test-payment', $clock);
        $boundaryPayment->setPaymentStatus(PaymentStatus::COMPLETED);
        $boundaryPayment->setCompletedAt($clock->now()->modify('-15 minutes'));
        $this->em->persist($boundaryPayment);

        $recentPayment = $this->createPaymentPersistence('recent-payment', 'test-payment', $clock);
        $recentPayment->setPaymentStatus(PaymentStatus::COMPLETED);
        $recentPayment->setCompletedAt($clock->now()->modify('-14 minutes'));
        $this->em->persist($recentPayment);

        $this->em->flush();

        $paymentType = $this->configService->getPaymentTypeByType('test-payment');
        $service = new ReportingService($this->configService, $this->em, $clock);

        $email = $service->buildNotifyErrorEmail($paymentType);

        $this->assertNotNull($email);
        $this->assertSame('sender@example.com', $email->getFrom()[0]->getAddress());
        $this->assertSame('admin@example.com', $email->getTo()[0]->getAddress());
        $this->assertSame('Payment Errors', $email->getSubject());

        $html = $email->getHtmlBody();
        assert(is_string($html));

        $expectedHtml = <<<'HTML'
            <html>
            <body>
            <h2>Unnotified completed payments</h2>
            <table>
                <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Payment type</th>
                    <th>Backend type</th>
                    <th>Completed at</th>
                </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>old-payment</td>
                        <td>test-payment</td>
                        <td>test</td>
                        <td>2024-03-13 14:30:00 UTC</td>
                    </tr>
                    <tr>
                        <td>boundary-payment</td>
                        <td>test-payment</td>
                        <td>test</td>
                        <td>2024-03-15 14:15:00 UTC</td>
                    </tr>
                </tbody>
            </table>
            </body>
            </html>
            HTML;

        $this->assertXmlStringEqualsXmlString($expectedHtml, $html);
    }
}
