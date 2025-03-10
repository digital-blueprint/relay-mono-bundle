<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use Dbp\Relay\MonoBundle\PaymentServiceProvider\CompleteResponse;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\CompleteResponseInterface;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\PaymentServiceProviderServiceInterface;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\StartResponse;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\StartResponseInterface;
use Dbp\Relay\MonoBundle\Persistence\PaymentPersistence;

class DummyPaymentServiceProviderService implements PaymentServiceProviderServiceInterface
{
    public function start(string $pspContract, string $pspMethod, PaymentPersistence $paymentPersistence): StartResponseInterface
    {
        return new StartResponse('');
    }

    public function complete(string $pspContract, PaymentPersistence $paymentPersistence): CompleteResponseInterface
    {
        return new CompleteResponse('');
    }

    public function cleanup(string $pspContract, PaymentPersistence $paymentPersistence): bool
    {
        return true;
    }

    public function getPaymentIdForPspData(string $pspData): ?string
    {
        return null;
    }

    public function getPaymentContracts(): array
    {
        return ['quux'];
    }

    public function getPaymentMethods(string $pspContract): array
    {
        if ($pspContract === 'quux') {
            return ['baz'];
        }

        return [];
    }
}
