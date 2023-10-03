<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Dbp\Relay\MonoBundle\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class StartPayActionProcessor extends AbstractController implements ProcessorInterface
{
    private $api;

    public function __construct(PaymentService $api)
    {
        $this->api = $api;
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): StartPayAction
    {
        $startPayAction = $data;
        assert($startPayAction instanceof StartPayAction);

        $startResponse = $this->api->startPayAction($startPayAction);

        $startPayAction->setWidgetUrl($startResponse->getWidgetUrl());
        $startPayAction->setPspData($startResponse->getData());
        $startPayAction->setPspError($startResponse->getError());

        return $startPayAction;
    }
}
