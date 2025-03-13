<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Dbp\Relay\MonoBundle\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @psalm-suppress MissingTemplateParam
 *
 * @implements ProcessorInterface<Payment, Payment>
 */
class PaymentProcessor extends AbstractController implements ProcessorInterface
{
    private $api;

    public function __construct(PaymentService $api)
    {
        $this->api = $api;
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): Payment
    {
        $payment = $data;
        assert($payment instanceof Payment);

        $payment = $this->api->createPayment($payment);

        return $payment;
    }
}
