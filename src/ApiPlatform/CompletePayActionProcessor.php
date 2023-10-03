<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Dbp\Relay\MonoBundle\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CompletePayActionProcessor extends AbstractController implements ProcessorInterface
{
    private $api;

    public function __construct(PaymentService $api)
    {
        $this->api = $api;
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): CompletePayAction
    {
        $completePayAction = $data;
        assert($completePayAction instanceof CompletePayAction);

        $pspData = $completePayAction->getPspData();
        $identifier = $this->api->completeGetPaymentId($pspData);

        $completeResponse = $this->api->completePayAction($identifier);

        $completePayAction->setIdentifier($identifier);
        $completePayAction->setReturnUrl($completeResponse->getReturnUrl());

        return $completePayAction;
    }
}
