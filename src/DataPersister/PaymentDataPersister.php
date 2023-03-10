<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use Dbp\Relay\MonoBundle\Entity\Payment;
use Dbp\Relay\MonoBundle\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PaymentDataPersister extends AbstractController implements ContextAwareDataPersisterInterface
{
    private $api;

    public function __construct(PaymentService $api)
    {
        $this->api = $api;
    }

    public function supports($data, array $context = []): bool
    {
        return $data instanceof Payment;
    }

    public function persist($data, array $context = []): Payment
    {
        $payment = $data;
        assert($payment instanceof Payment);

        $payment = $this->api->createPayment($payment);

        return $payment;
    }

    public function remove($data, array $context = [])
    {
    }
}
