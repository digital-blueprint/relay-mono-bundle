<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use Dbp\Relay\MonoBundle\Config\PaymentContract;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\PaymentServiceProviderService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PaymentServiceProviderTest extends KernelTestCase
{
    public function testBackendService()
    {
        self::bootKernel();
        $container = self::getContainer();
        $psp = $container->get(PaymentServiceProviderService::class);
        $contract = new PaymentContract();
        $contract->setService(DummyPaymentServiceProviderService::class);
        $service = $psp->getByPaymentContract($contract);
        $this->assertTrue($service instanceof DummyPaymentServiceProviderService);
    }
}
