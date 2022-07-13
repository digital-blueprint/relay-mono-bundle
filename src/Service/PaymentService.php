<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Service;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\MonoBundle\Entity\Payment;
use Dbp\Relay\MonoBundle\Entity\PaymentPersistence;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\CompleteResponseInterface;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\StartResponseInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class PaymentService
{
    /**
     * @var BackendService
     */
    private $backendService;

    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var PaymentServiceProviderService
     */
    private $paymentServiceProviderService;

    /**
     * @var UserSessionInterface
     */
    private $userSession;

    public function __construct(
        BackendService $backendService,
        ConfigurationService $configurationService,
        ManagerRegistry $managerRegistry,
        PaymentServiceProviderService $paymentServiceProviderService,
        UserSessionInterface $userSession
    ) {
        $this->backendService = $backendService;
        $this->configurationService = $configurationService;

        $manager = $managerRegistry->getManager('dbp_relay_mono_bundle');
        assert($manager instanceof EntityManagerInterface);
        $this->em = $manager;

        $this->paymentServiceProviderService = $paymentServiceProviderService;
        $this->userSession = $userSession;
    }

    public function createPayment(Payment $payment): Payment
    {
        $type = $payment->getType();
        $paymentType = $this->configurationService->getPaymentTypeByType($type);

        if ($paymentType->isAuthRequired() && !$this->userSession->getUserIdentifier()) {
            throw ApiError::withDetails(Response::HTTP_UNAUTHORIZED, 'Authorization required!', 'mono:authorization-required');
        }

        $expressionLanguage = new ExpressionLanguage();
        if ($paymentType->getNotifyUrlExpression() && !$expressionLanguage->evaluate($paymentType->getNotifyUrlExpression(), ['payment' => $payment])) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, 'Notify URL not allowed!', 'mono:notify-url-not-allowed');
        }

        if ($paymentType->getReturnUrlExpression() && !$expressionLanguage->evaluate($paymentType->getReturnUrlExpression(), ['payment' => $payment])) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, 'Return URL not allowed!', 'mono:return-url-not-allowed');
        }

        $identifier = (string) Uuid::v4();
        $payment->setIdentifier($identifier);
        $payment->setPaymentStatus(Payment::PAYMENT_STATUS_PREPARED);

        $paymentPersistence = PaymentPersistence::fromPayment($payment);
        $createdAt = new \DateTime();
        $paymentPersistence->setCreatedAt($createdAt);

        try {
            $this->em->persist($paymentPersistence);
            $this->em->flush();
        } catch (\Exception $e) {
            throw ApiError::withDetails(Response::HTTP_INTERNAL_SERVER_ERROR, 'Payment could not be created!', 'mono:payment-not-created', ['message' => $e->getMessage()]);
        }

        $payment = Payment::fromPaymentPersistence($paymentPersistence);

        return $payment;
    }

    private function getPaymentPersistenceByIdentifier(string $identifier): PaymentPersistence
    {
        /** @var PaymentPersistence $paymentPersistence */
        $paymentPersistence = $this->em
            ->getRepository(PaymentPersistence::class)
            ->find($identifier);

        if (!$paymentPersistence) {
            throw ApiError::withDetails(Response::HTTP_NOT_FOUND, 'Payment was not found!', 'mono:payment-not-found');
        }

        return $paymentPersistence;
    }

    public function getPaymentByIdentifier(string $identifier): Payment
    {
        $paymentPersistence = $this->getPaymentPersistenceByIdentifier($identifier);
        $type = $paymentPersistence->getType();
        $paymentType = $this->configurationService->getPaymentTypeByType($type);

        $backendService = $this->backendService->getByPaymentType($paymentType);
        $isDataUpdated = $backendService->updateData($paymentPersistence);
        if ($isDataUpdated) {
            $dataUpdatedAt = new \DateTime();
            $paymentPersistence->setDataUpdatedAt($dataUpdatedAt);
        }

        try {
            $this->em->persist($paymentPersistence);
            $this->em->flush();
        } catch (\Exception $e) {
            throw ApiError::withDetails(Response::HTTP_INTERNAL_SERVER_ERROR, 'Payment could not be updated!', 'mono:payment-not-updated', ['message' => $e->getMessage()]);
        }

        $payment = Payment::fromPaymentPersistence($paymentPersistence);
        $paymentMethods = $this->configurationService->getPaymentMethodsByType($type);
        $paymentMethod = json_encode($paymentMethods);
        $payment->setPaymentMethod($paymentMethod);

        return $payment;
    }

    public function startPayAction(
        string $identifier,
        string $paymentMethod
    ): StartResponseInterface {
        $paymentPersistence = $this->getPaymentPersistenceByIdentifier($identifier);
        $paymentPersistence->setPaymentMethod($paymentMethod);

        $type = $paymentPersistence->getType();
        $paymentContract = $this->configurationService->getPaymentContractByTypeAndPaymentMethod($type, $paymentMethod);
        $paymentPersistence->setPaymentContract((string) $paymentContract);

        try {
            $this->em->persist($paymentPersistence);
            $this->em->flush();
        } catch (\Exception $e) {
            throw ApiError::withDetails(Response::HTTP_INTERNAL_SERVER_ERROR, 'Payment could not be updated!', 'mono:payment-not-updated', ['message' => $e->getMessage()]);
        }

        $paymentServiceProvider = $this->paymentServiceProviderService->getByPaymentContract($paymentContract);
        $startResponse = $paymentServiceProvider->start($paymentPersistence);

        return $startResponse;
    }

    public function completePayAction(
        string $identifier,
        string $pspData
    ): CompleteResponseInterface {
        $paymentPersistence = $this->getPaymentPersistenceByIdentifier($identifier);
        $type = $paymentPersistence->getType();
        $paymentType = $this->configurationService->getPaymentTypeByType($type);

        $paymentMethod = $paymentPersistence->getPaymentMethod();
        $paymentContract = $this->configurationService->getPaymentContractByTypeAndPaymentMethod($type, $paymentMethod);

        $paymentServiceProvider = $this->paymentServiceProviderService->getByPaymentContract($paymentContract);
        $completeResponse = $paymentServiceProvider->complete($paymentPersistence, $pspData);

        try {
            $backendService = $this->backendService->getByPaymentType($paymentType);
            $isNotified = $backendService->notify($paymentPersistence);
            if ($isNotified) {
                $notifiedAt = new \DateTime();
                $paymentPersistence->setNotifiedAt($notifiedAt);
            }
            $this->em->persist($paymentPersistence);
            $this->em->flush();
        } catch (\Exception $e) {
            throw ApiError::withDetails(Response::HTTP_INTERNAL_SERVER_ERROR, 'Payment could not be updated!', 'mono:payment-not-updated', ['message' => $e->getMessage()]);
        }

        return $completeResponse;
    }
}
