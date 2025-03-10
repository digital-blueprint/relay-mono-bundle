<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\PaymentServiceProvider;

use Dbp\Relay\MonoBundle\Config\PaymentMethod;

class PaymentServiceProviderServiceRegistry
{
    /**
     * @var array<class-string,PaymentServiceProviderServiceInterface>
     */
    private array $mapping;

    /**
     * @var PaymentServiceProviderServiceInterface[]
     */
    private array $services;

    public function __construct()
    {
        $this->mapping = [];
        $this->services = [];
    }

    public function addService(PaymentServiceProviderServiceInterface $service): void
    {
        foreach ($service->getPaymentContracts() as $contractId) {
            if (array_key_exists($contractId, $this->mapping)) {
                throw new \RuntimeException("$contractId already registered");
            }
            $this->mapping[$contractId] = $service;
        }
        $this->services[] = $service;
    }

    /**
     * @return PaymentServiceProviderServiceInterface[]
     */
    public function getServices(): array
    {
        return $this->services;
    }

    public function getByPaymentMethod(PaymentMethod $paymentMethod): PaymentServiceProviderServiceInterface
    {
        $contractId = $paymentMethod->getContract();

        $backend = $this->mapping[$contractId] ?? null;
        if ($backend === null) {
            throw new \RuntimeException("$contractId not found");
        }
        assert($backend instanceof PaymentServiceProviderServiceInterface);

        return $backend;
    }
}
