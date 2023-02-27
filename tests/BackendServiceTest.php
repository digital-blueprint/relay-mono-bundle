<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use Dbp\Relay\MonoBundle\Entity\PaymentType;
use Dbp\Relay\MonoBundle\Service\BackendService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BackendServiceTest extends KernelTestCase
{
    public function testBackendService()
    {
        self::bootKernel();
        $container = self::getContainer();

        $backend = new BackendService($container);
        $paymentType = new PaymentType();
        $paymentType->setService(DummyBackendService::class);
        $backendService = $backend->getByPaymentType($paymentType);
        $this->assertTrue($backendService instanceof DummyBackendService);
    }
}
