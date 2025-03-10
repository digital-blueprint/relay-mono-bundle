<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\BackendServiceProvider;

use Dbp\Relay\MonoBundle\Config\PaymentType;

class BackendServiceRegistry
{
    /**
     * @var array<class-string,BackendServiceInterface>
     */
    private array $services;

    public function __construct()
    {
        $this->services = [];
    }

    public function addService(BackendServiceInterface $service): void
    {
        foreach ($service->getPaymentClientTypes() as $paymentTypeId) {
            if (array_key_exists($paymentTypeId, $this->services)) {
                throw new \RuntimeException("$paymentTypeId already registered");
            }
            $this->services[$paymentTypeId] = $service;
        }
    }

    public function getByPaymentType(PaymentType $paymentType): BackendServiceInterface
    {
        $paymentTypeId = $paymentType->getIdentifier();

        $backend = $this->services[$paymentTypeId] ?? null;
        if ($backend === null) {
            throw new \RuntimeException("$paymentTypeId not found");
        }
        assert($backend instanceof BackendServiceInterface);

        return $backend;
    }
}
