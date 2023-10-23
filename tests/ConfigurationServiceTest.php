<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use Dbp\Relay\MonoBundle\Config\ConfigurationService;
use Dbp\Relay\MonoBundle\Config\PaymentType;
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
                    'timeout_before' => 'P1D',
                ],
            ],
            'payment_session_timeout' => 'PT1234S',
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
                    'return_url_expression' => 'true',
                    'return_url_override' => 'true',
                    'notify_url_expression' => 'true',
                    'psp_return_url_expression' => 'true',
                    'recipient' => '',
                    'payment_contracts' => [
                        'somecontract' => [
                            'service' => 'bla',
                            'payment_methods' => [
                                [
                                    'identifier' => 'quux',
                                    'name' => 'somename',
                                    'image' => 'bar.svg',
                                    'demo_mode' => false,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $service->checkConfig();

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

    public function testPaymentTypeExpressions()
    {
        $paymentType = new PaymentType();
        $paymentType->setReturnUrlExpression('relay.str_starts_with(url, "https://return.com/")');
        $this->assertFalse($paymentType->evaluateReturnUrlExpression('https://return.at/bla'));
        $this->assertTrue($paymentType->evaluateReturnUrlExpression('https://return.com/bla'));

        $paymentType->setPspReturnUrlExpression('relay.str_starts_with(url, "https://psp.com/")');
        $this->assertFalse($paymentType->evaluatePspReturnUrlExpression('https://psp.at/bla'));
        $this->assertTrue($paymentType->evaluatePspReturnUrlExpression('https://psp.com/bla'));

        $paymentType->setNotifyUrlExpression('relay.str_starts_with(url, "https://notify.com/")');
        $this->assertFalse($paymentType->evaluateNotifyUrlExpression('https://notify.at/bla'));
        $this->assertTrue($paymentType->evaluateNotifyUrlExpression('https://notify.com/bla'));
    }
}
