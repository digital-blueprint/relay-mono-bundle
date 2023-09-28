<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Service;

use Dbp\Relay\MonoBundle\Config\PaymentContract;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymentServiceProviderService
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

    public function getByPaymentContract(PaymentContract $paymentContract): PaymentServiceProviderServiceInterface
    {
        $service = $paymentContract->getService();

        $backend = $this->container->get($service);
        assert($backend instanceof PaymentServiceProviderServiceInterface);

        return $backend;
    }
}
