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
use Symfony\Component\HttpFoundation\Request;
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

        $userIdentifier = $this->userSession->getUserIdentifier();
        if ($paymentType->isAuthRequired() && !$userIdentifier) {
            throw ApiError::withDetails(Response::HTTP_UNAUTHORIZED, 'Authorization required!', 'mono:authorization-required');
        }

        $expressionLanguage = new ExpressionLanguage();
        if ($paymentType->getNotifyUrlExpression() && !$expressionLanguage->evaluate($paymentType->getNotifyUrlExpression(), ['payment' => $payment])) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, 'Notify URL not allowed!', 'mono:notify-url-not-allowed');
        }

        if ($paymentType->getReturnUrlExpression() && !$expressionLanguage->evaluate($paymentType->getReturnUrlExpression(), ['payment' => $payment])) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, 'Return URL not allowed!', 'mono:return-url-not-allowed');
        }

        $data = $payment->getData();
        $paymentPersistence = null;
        if (!empty($data)) {
            $criteria = [
                'type' => $type,
                'data' => $data,
            ];
            if ($paymentType->isAuthRequired()) {
                $criteria['userIdentifier'] = $userIdentifier;
            }
            $paymentPersistence = $this->em
                ->getRepository(PaymentPersistence::class)
                ->findOneBy($criteria);
        }

        if ($paymentPersistence === null) {
            $identifier = (string) Uuid::v4();
            $payment->setIdentifier($identifier);
            $payment->setPaymentStatus(Payment::PAYMENT_STATUS_PREPARED);

            $paymentPersistence = PaymentPersistence::fromPayment($payment);
            $request = Request::createFromGlobals();
            $clientIp = $request->getClientIp();
            if (empty($paymentPersistence->getClientIp())) {
                $paymentPersistence->setClientIp($clientIp);
            }
            $createdAt = new \DateTime();
            $paymentPersistence->setCreatedAt($createdAt);
            $paymentPersistence->setNumberOfUses(0);
            if ($paymentType->isAuthRequired()) {
                $paymentPersistence->setUserIdentifier($userIdentifier);
            }

            try {
                $this->em->persist($paymentPersistence);
                $this->em->flush();
            } catch (\Exception $e) {
                throw ApiError::withDetails(Response::HTTP_INTERNAL_SERVER_ERROR, 'Payment could not be created!', 'mono:payment-not-created', ['message' => $e->getMessage()]);
            }
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

        $request = Request::createFromGlobals();
        $clientIp = $request->getClientIp();
        if ($paymentPersistence->getClientIp() !== $clientIp) {
            throw ApiError::withDetails(Response::HTTP_FORBIDDEN, 'Payment client IP not allowed!', 'mono:payment-client-ip-not-allowed');
        }

        $config = $this->configurationService->getConfig();
        $numberOfUses = $config['payment_session_number_of_uses'];
        if ($paymentPersistence->getNumberOfUses() >= $numberOfUses) {
            throw ApiError::withDetails(Response::HTTP_TOO_MANY_REQUESTS, 'Payment too many requests!', 'mono:payment-too-many-requests');
        }

        $now = new \DateTime();
        $timeoutAt = clone $paymentPersistence->getCreatedAt();
        $timeout = $config['payment_session_timeout'];
        $timeoutAt->modify('+'.(int) $timeout.' seconds');
        if ($now >= $timeoutAt) {
            throw ApiError::withDetails(Response::HTTP_GONE, 'Payment timeout exceeded!', 'mono:payment-timeout-exceeded');
        }

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

        $request = Request::createFromGlobals();
        $clientIp = $request->getClientIp();
        if ($paymentPersistence->getClientIp() !== $clientIp) {
            throw ApiError::withDetails(Response::HTTP_FORBIDDEN, 'Start payment client IP not allowed!', 'mono:start-payment-client-ip-not-allowed');
        }

        if ($paymentPersistence->getStartedAt()) {
            throw ApiError::withDetails(Response::HTTP_TOO_MANY_REQUESTS, 'Start payment too many requests!', 'mono:start-payment-too-many-requests');
        }

        $now = new \DateTime();
        $timeoutAt = clone $paymentPersistence->getCreatedAt();
        $config = $this->configurationService->getConfig();
        $timeout = $config['payment_session_timeout'];
        $timeoutAt->modify('+'.(int) $timeout.' seconds');
        if ($now >= $timeoutAt) {
            throw ApiError::withDetails(Response::HTTP_GONE, 'Start payment timeout exceeded!', 'mono:start-payment-timeout-exceeded');
        }

        $paymentPersistence->setPaymentMethod($paymentMethod);

        $type = $paymentPersistence->getType();
        $paymentContract = $this->configurationService->getPaymentContractByTypeAndPaymentMethod($type, $paymentMethod);
        $paymentPersistence->setPaymentContract((string) $paymentContract);

        $paymentPersistence->setPaymentStatus(Payment::PAYMENT_STATUS_STARTED);

        $startedAt = new \DateTime();
        $paymentPersistence->setStartedAt($startedAt);

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
            $this->em->persist($paymentPersistence);
            $this->em->flush();
        } catch (\Exception $e) {
            throw ApiError::withDetails(Response::HTTP_INTERNAL_SERVER_ERROR, 'Payment could not be updated!', 'mono:payment-not-updated', ['message' => $e->getMessage()]);
        }

        $backendService = $this->backendService->getByPaymentType($paymentType);
        $isNotified = $backendService->notify($paymentPersistence);
        if ($isNotified) {
            $notifiedAt = new \DateTime();
            $paymentPersistence->setNotifiedAt($notifiedAt);
        }

        try {
            $this->em->persist($paymentPersistence);
            $this->em->flush();
        } catch (\Exception $e) {
            throw ApiError::withDetails(Response::HTTP_INTERNAL_SERVER_ERROR, 'Payment could not be updated!', 'mono:payment-not-updated', ['message' => $e->getMessage()]);
        }

        return $completeResponse;
    }
}
