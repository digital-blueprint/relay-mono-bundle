<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use Dbp\Relay\MonoBundle\Config\PaymentMethod;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\PaymentServiceProviderServiceRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PaymentServiceProviderTest extends KernelTestCase
{
    public function testBackendService()
    {
        self::bootKernel();
        $container = self::getContainer();
        $psp = $container->get(PaymentServiceProviderServiceRegistry::class);
        assert($psp instanceof PaymentServiceProviderServiceRegistry);
        $method = new PaymentMethod();
        $method->setContract('quux');
        $service = $psp->getByPaymentmethod($method);
        $this->assertTrue($service instanceof DummyPaymentServiceProviderService);
        $this->assertSame(['quux'], $service->getPaymentContracts());
        $this->assertSame(['baz'], $service->getPaymentMethods('quux'));
    }
}
