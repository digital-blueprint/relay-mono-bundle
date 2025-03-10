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
                    'client_type' => 'foo',
                    'service' => 'bla',
                    'auth_required' => false,
                    'return_url_expression' => 'true',
                    'return_url_override' => 'true',
                    'notify_url_expression' => 'true',
                    'psp_return_url_expression' => 'true',
                    'recipient' => '',
                    'concurrency_limits' => [
                        'max_concurrent_payments' => 42,
                        'max_concurrent_auth_payments' => 2,
                        'max_concurrent_auth_payments_per_user' => 3,
                        'max_concurrent_unauth_payments' => 4,
                        'max_concurrent_unauth_payments_per_ip' => 5,
                    ],
                    'payment_methods' => [
                        [
                            'identifier' => 'quux',
                            'contract' => 'somecontract',
                            'method' => 'somemethod',
                            'name' => 'somename',
                            'image' => 'bar.svg',
                            'demo_mode' => true,
                        ],
                    ],
                ],
            ],
        ]);

        $service->checkConfig();

        $this->assertSame('PT1234S', $service->getPaymentSessionTimeout());
        $this->assertSame('P1D', $service->getCleanupTimeout('started'));
        $this->assertNull($service->getCleanupTimeout('nope'));

        $paymentTypes = $service->getPaymentTypes();
        $this->assertCount(1, $paymentTypes);
        $this->assertSame(42, $paymentTypes[0]->getMaxConcurrentPayments());

        $methods = $service->getPaymentMethodsByType($paymentTypes[0]->getIdentifier());
        $this->assertCount(1, $methods);
        $this->assertSame('bar.svg', $methods[0]->getImage());
        $this->assertSame(' (DEMO)', $methods[0]->getName());
        $this->assertSame('somecontract', $methods[0]->getContract());
        $this->assertSame('somemethod', $methods[0]->getMethod());

        $this->assertSame('sometype', $service->getPaymentTypeByType('sometype')->getIdentifier());

        $this->assertNull($service->getPaymentMethodByTypeAndPaymentMethod('nope', 'quux'));
        $this->assertNull($service->getPaymentMethodByTypeAndPaymentMethod('sometype', 'nope'));
        $method = $service->getPaymentMethodByTypeAndPaymentMethod('sometype', 'quux');
        $this->assertSame('quux', $method->getIdentifier());
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
