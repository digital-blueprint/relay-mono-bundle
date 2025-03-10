<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use Dbp\Relay\MonoBundle\ApiPlatform\Payment;
use Dbp\Relay\MonoBundle\BackendServiceProvider\BackendServiceInterface;
use Dbp\Relay\MonoBundle\Persistence\PaymentPersistence;

class DummyBackendService implements BackendServiceInterface
{
    public function updateData(string $paymentClientType, PaymentPersistence $paymentPersistence): bool
    {
        return true;
    }

    public function updateEntity(string $paymentClientType, PaymentPersistence $paymentPersistence, Payment $payment): bool
    {
        return true;
    }

    public function notify(string $paymentClientType, PaymentPersistence $paymentPersistence): bool
    {
        return true;
    }

    public function cleanup(string $paymentClientType, PaymentPersistence $paymentPersistence): bool
    {
        return true;
    }

    public function getPaymentClientTypes(): array
    {
        return ['foobar'];
    }
}
