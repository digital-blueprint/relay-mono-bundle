<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use Dbp\Relay\MonoBundle\Entity\PaymentContract;
use Dbp\Relay\MonoBundle\Service\PaymentServiceProviderService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PaymentServiceProviderTest extends KernelTestCase
{
    public function testBackendService()
    {
        self::bootKernel();
        $container = self::getContainer();

        $psp = new PaymentServiceProviderService($container);
        $contract = new PaymentContract();
        $contract->setService(DummyPaymentServiceProviderService::class);
        $service = $psp->getByPaymentContract($contract);
        $this->assertTrue($service instanceof DummyPaymentServiceProviderService);
    }
}
