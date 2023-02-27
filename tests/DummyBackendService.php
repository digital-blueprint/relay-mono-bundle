<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use Dbp\Relay\MonoBundle\Entity\PaymentPersistence;
use Dbp\Relay\MonoBundle\Service\BackendServiceInterface;

class DummyBackendService implements BackendServiceInterface
{
    public function updateData(PaymentPersistence &$payment): bool
    {
        return true;
    }

    public function notify(PaymentPersistence &$payment): bool
    {
        return true;
    }

    public function cleanup(PaymentPersistence &$payment): bool
    {
        return true;
    }
}
