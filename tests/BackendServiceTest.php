<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use Dbp\Relay\MonoBundle\BackendServiceProvider\BackendServiceRegistry;
use Dbp\Relay\MonoBundle\Config\PaymentType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BackendServiceTest extends KernelTestCase
{
    public function testBackendService(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $backend = $container->get(BackendServiceRegistry::class);
        assert($backend instanceof BackendServiceRegistry);
        $paymentType = new PaymentType();
        $paymentType->setBackendType('foobar');
        $backendService = $backend->getByPaymentType($paymentType);
        $this->assertTrue($backendService instanceof DummyBackendService);
    }
}
