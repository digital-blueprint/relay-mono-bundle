<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Service;

use Dbp\Relay\MonoBundle\Entity\PaymentPersistence;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\CompleteResponseInterface;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\StartResponseInterface;

interface PaymentServiceProviderServiceInterface
{
    public function start(PaymentPersistence &$payment): StartResponseInterface;

    public function complete(PaymentPersistence &$payment, string $pspData): CompleteResponseInterface;

    public function cleanup(PaymentPersistence &$payment): bool;
}
