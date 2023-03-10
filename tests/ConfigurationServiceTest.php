<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use Dbp\Relay\MonoBundle\Service\ConfigurationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConfigurationServiceTest extends TestCase
{
    public function test()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $stack = new RequestStack();
        $urlHelper = new UrlHelper($stack);
        $service = new ConfigurationService($translator, $urlHelper);
        $service->setConfig([
            'database_url' => 'bla',
            'cleanup' => [
                [
                    'payment_status' => 'started',
                    'timeout_before' => '123',
                ],
            ],
            'payment_session_timeout' => 1234,
            'payment_types' => [
                'sometype' => [
                    'identifier' => 'foo',
                    'service' => 'bla',
                    'auth_required' => false,
                    'max_concurrent_payments' => 42,
                    'max_concurrent_auth_payments' => 2,
                    'max_concurrent_auth_payments_per_user' => 3,
                    'max_concurrent_unauth_payments' => 4,
                    'max_concurrent_unauth_payments_per_ip' => 5,
                    'return_url_expression' => '    ',
                    'return_url_override' => '',
                    'notify_url_expression' => '',
                    'psp_return_url_expression' => '',
                    'recipient' => '',
                    'demo_mode' => false,
                    'payment_contracts' => [
                        'somecontract' => [
                            'service' => 'bla',
                            'payment_methods' => [
                                [
                                    'identifier' => 'quux',
                                    'name' => 'somename',
                                    'image' => 'bar.svg',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertTrue(is_array($service->getConfig()));
        $this->assertSame('started', $service->getCleanupConfiguration()[0]['payment_status']);

        $paymentTypes = $service->getPaymentTypes();
        $this->assertCount(1, $paymentTypes);
        $this->assertSame(42, $paymentTypes[0]->getMaxConcurrentPayments());

        $methods = $service->getPaymentMethodsByType($paymentTypes[0]->getIdentifier());
        $this->assertCount(1, $methods);
        $this->assertSame('bar.svg', $methods[0]->getImage());

        $this->assertSame('sometype', $service->getPaymentTypeByType('sometype')->getIdentifier());

        $contract = $service->getPaymentContractByTypeAndPaymentMethod('sometype', 'quux');
        $this->assertSame('somecontract', $contract->getIdentifier());
    }
}
