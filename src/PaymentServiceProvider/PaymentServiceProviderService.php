<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\PaymentServiceProvider;

use Dbp\Relay\MonoBundle\Config\PaymentContract;

class PaymentServiceProviderService
{
    /**
     * @var array<class-string,PaymentServiceProviderServiceInterface>
     */
    private array $services;

    public function __construct()
    {
        $this->services = [];
    }

    public function addService(PaymentServiceProviderServiceInterface $service): void
    {
        $this->services[$service::class] = $service;
    }

    public function getByPaymentContract(PaymentContract $paymentContract): PaymentServiceProviderServiceInterface
    {
        $serviceClass = $paymentContract->getService();

        $backend = $this->services[$serviceClass] ?? null;
        if ($backend === null) {
            throw new \RuntimeException("$serviceClass not found");
        }
        assert($backend instanceof PaymentServiceProviderServiceInterface);

        return $backend;
    }
}
