<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Dbp\Relay\MonoBundle\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @implements ProviderInterface<Payment>
 */
class PaymentProvider extends AbstractController implements ProviderInterface
{
    private $api;

    public function __construct(PaymentService $api)
    {
        $this->api = $api;
    }

    /**
     * @return Payment|iterable<Payment>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        $id = $uriVariables['identifier'];
        $payment = $this->api->getPaymentByIdentifier($id);

        return $payment;
    }
}
