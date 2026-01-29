<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use Dbp\Relay\MonoBundle\Config\ConfigurationService;
use Dbp\Relay\MonoBundle\Persistence\PaymentPersistence;
use Dbp\Relay\MonoBundle\Persistence\PaymentStatus;
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

    public function testSendReporting(): void
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
                        'html_template' => 'emails/reporting.html.twig',
                        'created_begin' => 'P1D',
                    ],
                ],
            ],
        ]);

        $clock = new MockClock('2024-03-15 14:30:00', 'UTC');
        $payment1 = $this->createPaymentPersistence('payment-1', 'test-payment', $clock);
        $payment1->setPaymentStatus(PaymentStatus::COMPLETED);
        $payment1->setCreatedAt($clock->now()->modify('-2 hours'));
        $this->em->persist($payment1);

        $payment2 = $this->createPaymentPersistence('payment-2', 'test-payment', $clock);
        $payment2->setPaymentStatus(PaymentStatus::PREPARED);
        $payment2->setCreatedAt($clock->now()->modify('-1 hour'));
        $this->em->persist($payment2);

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
                <h2>Payments from 14.03.2024 14:30:00 UTC to 15.03.2024 14:30:00 UTC</h2>
                <table>
                    <tr>
                        <th>Prepared</th>
                        <td>1</td>
                    </tr>
                    <tr>
                        <th>Started</th>
                        <td>0</td>
                    </tr>
                    <tr>
                        <th>Completed</th>
                        <td>1</td>
                    </tr>
                    <tr>
                        <th>Cancelled</th>
                        <td>0</td>
                    </tr>
                    <tr>
                        <th>Pending</th>
                        <td>0</td>
                    </tr>
                    <tr>
                        <th>Failed</th>
                        <td>0</td>
                    </tr>
                </table>
            </body>
            </html>
            HTML;

        $this->assertSame($expectedHtml, $html);
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
                        'html_template' => 'emails/notify-error.html.twig',
                        'completed_begin' => 'P1D',
                    ],
                ],
            ],
        ]);

        $clock = new MockClock('2024-03-15 14:30:00 UTC');

        $payment1 = $this->createPaymentPersistence('payment-1', 'test-payment', $clock);
        $payment1->setPaymentStatus(PaymentStatus::COMPLETED);
        $payment1->setCompletedAt($clock->now()->modify('-2 hours'));
        $this->em->persist($payment1);

        $payment2 = $this->createPaymentPersistence('payment-2', 'test-payment', $clock);
        $payment2->setPaymentStatus(PaymentStatus::COMPLETED);
        $payment2->setCompletedAt($clock->now()->modify('-1 hours'));
        $this->em->persist($payment2);

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
            <table>
                <tr>
                    <th>Notify errors</th>
                    <td>2</td>
                </tr>
            </table>
            </body>
            </html>
            HTML;

        $this->assertSame($expectedHtml, $html);
    }
}
