<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Service;

use Dbp\Relay\MonoBundle\Entity\PaymentPersistence;

interface BackendServiceInterface
{
    public function updateData(PaymentPersistence &$payment): bool;

    public function notify(PaymentPersistence &$payment): bool;

    public function cleanup(PaymentPersistence &$payment): bool;
}
