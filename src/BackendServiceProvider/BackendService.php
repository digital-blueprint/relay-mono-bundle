<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\BackendServiceProvider;

use Dbp\Relay\MonoBundle\Config\PaymentType;

class BackendService
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
        $this->services[$service::class] = $service;
    }

    public function getByPaymentType(PaymentType $paymentType): BackendServiceInterface
    {
        $serviceClass = $paymentType->getService();

        $backend = $this->services[$serviceClass] ?? null;
        if ($backend === null) {
            throw new \RuntimeException("$serviceClass not found");
        }
        assert($backend instanceof BackendServiceInterface);

        return $backend;
    }
}
