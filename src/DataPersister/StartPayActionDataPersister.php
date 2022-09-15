<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use Dbp\Relay\MonoBundle\Entity\StartPayAction;
use Dbp\Relay\MonoBundle\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class StartPayActionDataPersister extends AbstractController implements ContextAwareDataPersisterInterface
{
    private $api;

    public function __construct(PaymentService $api)
    {
        $this->api = $api;
    }

    public function supports($data, array $context = []): bool
    {
        return $data instanceof StartPayAction;
    }

    public function persist($data, array $context = []): StartPayAction
    {
        $startPayAction = $data;
        assert($startPayAction instanceof StartPayAction);

        $startResponse = $this->api->startPayAction($startPayAction);

        $startPayAction->setWidgetUrl($startResponse->getWidgetUrl());
        $startPayAction->setPspData($startResponse->getData());
        $startPayAction->setPspError($startResponse->getError());

        return $startPayAction;
    }

    public function remove($data, array $context = [])
    {
    }
}
