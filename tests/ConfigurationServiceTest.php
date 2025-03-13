<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use Dbp\Relay\MonoBundle\Config\ConfigurationService;
use Dbp\Relay\MonoBundle\Config\PaymentType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\Translation\IdentityTranslator;

class ConfigurationServiceTest extends TestCase
{
    public function test(): void
    {
        $stack = new RequestStack();
        $urlHelper = new UrlHelper($stack);
        $service = new ConfigurationService(new IdentityTranslator(), $urlHelper);
        $service->setConfig([
            'database_url' => 'bla',
            'cleanup' => [
                [
                    'payment_status' => 'started',
                    'timeout_before' => 'P1D',
                ],
            ],
            'payment_types' => [
                'sometype' => [
                    'backend_type' => 'foo',
                    'service' => 'bla',
                    'auth_required' => false,
                    'session_timeout' => 'PT1234S',
                    'return_url_expression' => 'true',
                    'return_url_override' => 'true',
                    'notify_url_expression' => 'true',
                    'psp_return_url_expression' => 'true',
                    'data_protection_declaration_url' => null,
                    'recipient' => '',
                    'concurrency_limits' => [
                        'max_concurrent_payments' => 42,
                        'max_concurrent_auth_payments' => 2,
                        'max_concurrent_auth_payments_per_user' => 3,
                        'max_concurrent_unauth_payments' => 4,
                        'max_concurrent_unauth_payments_per_ip' => 5,
                    ],
                    'payment_methods' => [
                        'quux' => [
                            'contract' => 'somecontract',
                            'method' => 'somemethod',
                            'name' => 'somename',
                            'image' => 'bar.svg',
                            'demo_mode' => true,
                        ],
                        'baz' => [
                            'contract' => 'somecontract',
                            'method' => 'somemethod',
                            'name' => 'somename2',
                            'image' => 'bar2.svg',
                            'demo_mode' => false,
                        ],
                    ],
                ],
            ],
        ]);

        $service->checkConfig();

        $this->assertSame('P1D', $service->getCleanupTimeout('started'));
        $this->assertNull($service->getCleanupTimeout('nope'));

        $paymentTypes = $service->getPaymentTypes();
        $this->assertCount(1, $paymentTypes);
        $this->assertSame(42, $paymentTypes[0]->getMaxConcurrentPayments());
        $this->assertSame('PT1234S', $paymentTypes[0]->getSessionTimeout());

        $methods = $service->getPaymentMethodsByType($paymentTypes[0]->getIdentifier());
        $this->assertCount(2, $methods);
        $this->assertSame('bar.svg', $methods[0]->getImage());
        $this->assertSame('somename (DEMO)', $methods[0]->getName());
        $this->assertSame('somecontract', $methods[0]->getContract());
        $this->assertSame('somemethod', $methods[0]->getMethod());

        $this->assertSame('sometype', $service->getPaymentTypeByType('sometype')->getIdentifier());

        $this->assertNull($service->getPaymentMethodByTypeAndPaymentMethod('nope', 'quux'));
        $this->assertNull($service->getPaymentMethodByTypeAndPaymentMethod('sometype', 'nope'));
        $method = $service->getPaymentMethodByTypeAndPaymentMethod('sometype', 'quux');
        $this->assertSame('quux', $method->getIdentifier());
        $this->assertSame('bar.svg', $method->getImage());
        $this->assertSame('somename (DEMO)', $method->getName());

        $this->assertSame(
            '[{"identifier":"quux","name":"somename (DEMO)","image":"bar.svg"},{"identifier":"baz","name":"somename2","image":"bar2.svg"}]',
            $service->createJsonForMethods('sometype'));

        $this->assertSame('[]', $service->createJsonForMethods('someothertype'));
    }

    public function testPaymentTypeExpressions(): void
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
