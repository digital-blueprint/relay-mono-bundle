<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\BackendServiceProvider;

use Dbp\Relay\MonoBundle\Config\PaymentType;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BackendService
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(
        ContainerInterface $container
    ) {
        $this->container = $container;
    }

    public function getByPaymentType(PaymentType $paymentType): BackendServiceInterface
    {
        $service = $paymentType->getService();

        $backend = $this->container->get($service);
        assert($backend instanceof BackendServiceInterface);

        return $backend;
    }
}
