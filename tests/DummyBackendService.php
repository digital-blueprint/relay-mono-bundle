<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use Dbp\Relay\MonoBundle\Entity\Payment;
use Dbp\Relay\MonoBundle\Persistence\PaymentPersistence;
use Dbp\Relay\MonoBundle\Service\BackendServiceInterface;

class DummyBackendService implements BackendServiceInterface
{
    public function updateData(PaymentPersistence $paymentPersistence): bool
    {
        return true;
    }

    public function updateEntity(PaymentPersistence $paymentPersistence, Payment $payment): bool
    {
        return true;
    }

    public function notify(PaymentPersistence $paymentPersistence): bool
    {
        return true;
    }

    public function cleanup(PaymentPersistence $paymentPersistence): bool
    {
        return true;
    }
}
