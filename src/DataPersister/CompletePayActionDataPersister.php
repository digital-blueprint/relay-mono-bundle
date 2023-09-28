<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use Dbp\Relay\MonoBundle\Entity\CompletePayAction;
use Dbp\Relay\MonoBundle\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CompletePayActionDataPersister extends AbstractController implements ContextAwareDataPersisterInterface
{
    private $api;

    public function __construct(PaymentService $api)
    {
        $this->api = $api;
    }

    public function supports($data, array $context = []): bool
    {
        return $data instanceof CompletePayAction;
    }

    public function persist($data, array $context = []): CompletePayAction
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

    public function remove($data, array $context = [])
    {
    }
}
