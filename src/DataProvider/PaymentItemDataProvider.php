<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Dbp\Relay\MonoBundle\Entity\Payment;
use Dbp\Relay\MonoBundle\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PaymentItemDataProvider extends AbstractController implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private $api;

    public function __construct(PaymentService $api)
    {
        $this->api = $api;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Payment
    {
        $payment = $this->api->getPaymentByIdentifier($id);

        return $payment;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Payment::class === $resourceClass;
    }
}
