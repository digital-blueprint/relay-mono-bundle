<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use Dbp\Relay\MonoBundle\Config\ConfigurationService;
use Dbp\Relay\MonoBundle\Config\PaymentProfile;
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
            'payment_contracts' => [
                'somecontract' => [
                    'service' => 'bla',
                ],
            ],
            'payment_types' => [
                'sometype' => [
                    'service' => 'bla',
                ],
            ],
            'payment_profiles' => [
                [
                    'type' => 'sometype',
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

        $paymentProfiles = $service->getPaymentProfiles();
        $this->assertCount(1, $paymentProfiles);
        $this->assertSame(42, $paymentProfiles[0]->getMaxConcurrentPayments());

        $contracts = $service->getPaymentContracts();
        $this->assertCount(1, $contracts);
        $this->assertSame('somecontract', $contracts[0]->getIdentifier());

        $methods = $service->getPaymentMethodsByType($paymentProfiles[0]->getType());
        $this->assertCount(1, $methods);
        $this->assertSame('bar.svg', $methods[0]->getImage());
        $this->assertSame(' (DEMO)', $methods[0]->getName());
        $this->assertSame('somecontract', $methods[0]->getContract());

        $this->assertSame('sometype', $service->getPaymentProfileByType('sometype')->getType());

        $this->assertNull($service->getPaymentContract('nope'));
        $contract = $service->getPaymentContract('somecontract');
        $this->assertSame('somecontract', $contract->getIdentifier());

        $this->assertNull($service->getPaymentMethodByTypeAndPaymentMethod('nope', 'quux'));
        $this->assertNull($service->getPaymentMethodByTypeAndPaymentMethod('sometype', 'nope'));
        $method = $service->getPaymentMethodByTypeAndPaymentMethod('sometype', 'quux');
        $this->assertSame('quux', $method->getIdentifier());

        $this->assertNull($service->getPaymentContractByTypeAndPaymentMethod('nope', 'quux'));
        $this->assertNull($service->getPaymentContractByTypeAndPaymentMethod('sometype', 'nope'));
        $contract = $service->getPaymentContractByTypeAndPaymentMethod('sometype', 'quux');
        $this->assertSame('somecontract', $contract->getIdentifier());
    }

    public function testPaymentProfileExpressions()
    {
        $paymentProfile = new PaymentProfile();
        $paymentProfile->setReturnUrlExpression('relay.str_starts_with(url, "https://return.com/")');
        $this->assertFalse($paymentProfile->evaluateReturnUrlExpression('https://return.at/bla'));
        $this->assertTrue($paymentProfile->evaluateReturnUrlExpression('https://return.com/bla'));

        $paymentProfile->setPspReturnUrlExpression('relay.str_starts_with(url, "https://psp.com/")');
        $this->assertFalse($paymentProfile->evaluatePspReturnUrlExpression('https://psp.at/bla'));
        $this->assertTrue($paymentProfile->evaluatePspReturnUrlExpression('https://psp.com/bla'));

        $paymentProfile->setNotifyUrlExpression('relay.str_starts_with(url, "https://notify.com/")');
        $this->assertFalse($paymentProfile->evaluateNotifyUrlExpression('https://notify.at/bla'));
        $this->assertTrue($paymentProfile->evaluateNotifyUrlExpression('https://notify.com/bla'));
    }
}
