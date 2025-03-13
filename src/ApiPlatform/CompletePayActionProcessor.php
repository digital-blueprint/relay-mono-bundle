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
 * @implements ProcessorInterface<CompletePayAction, CompletePayAction>
 */
class CompletePayActionProcessor extends AbstractController implements ProcessorInterface
{
    private PaymentService $api;

    public function __construct(PaymentService $api)
    {
        $this->api = $api;
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): CompletePayAction
    {
        $completePayAction = $data;

        $pspData = $completePayAction->getPspData();
        $identifier = $this->api->completeGetPaymentId($pspData);

        $completeResponse = $this->api->completePayAction($identifier);

        $completePayAction->setIdentifier($identifier);
        $completePayAction->setReturnUrl($completeResponse->getReturnUrl());

        return $completePayAction;
    }
}
