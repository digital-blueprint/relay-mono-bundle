<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use Dbp\Relay\MonoBundle\Entity\PaymentPersistence;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\CompleteResponse;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\CompleteResponseInterface;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\StartResponse;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\StartResponseInterface;
use Dbp\Relay\MonoBundle\Service\PaymentServiceProviderServiceInterface;

class DummyPaymentServiceProviderService implements PaymentServiceProviderServiceInterface
{
    public function start(PaymentPersistence $paymentPersistence): StartResponseInterface
    {
        return new StartResponse('');
    }

    public function complete(PaymentPersistence $paymentPersistence, string $pspData): CompleteResponseInterface
    {
        return new CompleteResponse('');
    }

    public function cleanup(PaymentPersistence $paymentPersistence): bool
    {
        return true;
    }

    public function getPaymentIdForPspData(string $pspData): ?string
    {
        return null;
    }
}
