<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Service;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\MonoBundle\ApiPlatform\Payment;
use Dbp\Relay\MonoBundle\ApiPlatform\StartPayAction;
use Dbp\Relay\MonoBundle\BackendServiceProvider\BackendService;
use Dbp\Relay\MonoBundle\Config\ConfigurationService;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\CompleteResponseInterface;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\PaymentServiceProviderService;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\StartResponseInterface;
use Dbp\Relay\MonoBundle\Persistence\PaymentPersistence;
use Dbp\Relay\MonoBundle\Persistence\PaymentPersistenceRepository;
use Dbp\Relay\MonoBundle\Persistence\PaymentStatus;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Uid\Uuid;

class PaymentService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

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

    /**
     * @var LoggerInterface
     */
    private $auditLogger;

    /**
     * @var LockFactory
     */
    private $lockFactory;

    public function __construct(
        BackendService $backendService,
        ConfigurationService $configurationService,
        EntityManagerInterface $em,
        PaymentServiceProviderService $paymentServiceProviderService,
        UserSessionInterface $userSession,
        LoggerInterface $auditLogger,
        LockFactory $lockFactory
    ) {
        $this->backendService = $backendService;
        $this->configurationService = $configurationService;
        $this->em = $em;

        $this->paymentServiceProviderService = $paymentServiceProviderService;
        $this->userSession = $userSession;
        $this->logger = new NullLogger();
        $this->auditLogger = $auditLogger;
        $this->lockFactory = $lockFactory;
    }

    public function checkConnection()
    {
        $this->em->getConnection()->getNativeConnection();
    }

    private function createPaymentLock(PaymentPersistence $payment): LockInterface
    {
        $resourceKey = sprintf(
            'mono-%s',
            $payment->getIdentifier()
        );

        return $this->lockFactory->createLock($resourceKey, 60, true);
    }

    private function getLoggingContext(PaymentPersistence $payment, array $extra = []): array
    {
        return array_merge(['relay-mono-payment-id' => $payment->getIdentifier()], $extra);
    }

    public function createPayment(Payment $payment): Payment
    {
        $type = $payment->getType();
        $paymentType = $this->configurationService->getPaymentTypeByType($type);
        if ($paymentType === null) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, 'Unknown payment type', 'mono:unknown-payment-type');
        }

        $userIdentifier = $this->userSession->getUserIdentifier();
        if ($paymentType->isAuthRequired() && !$userIdentifier) {
            throw ApiError::withDetails(Response::HTTP_UNAUTHORIZED, 'Authorization required!', 'mono:authorization-required');
        }

        $notifyUrl = $payment->getNotifyUrl();
        if ($notifyUrl !== null && !$paymentType->evaluateNotifyUrlExpression($notifyUrl)) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, 'Notify URL not allowed!', 'mono:notify-url-not-allowed');
        }

        $returnUrl = $payment->getReturnUrl();
        if ($returnUrl !== null && !$paymentType->evaluateReturnUrlExpression($returnUrl)) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, 'Return URL not allowed!', 'mono:return-url-not-allowed');
        }

        $returnUrlOverride = $paymentType->getReturnUrlOverride();
        if ($returnUrlOverride) {
            $payment->setReturnUrl($returnUrlOverride);
        }

        if (empty($payment->getClientIp())) {
            $request = Request::createFromGlobals();
            $clientIp = $request->getClientIp();
            $payment->setClientIp($clientIp);
        }

        $repo = $this->em->getRepository(PaymentPersistence::class);
        assert($repo instanceof PaymentPersistenceRepository);
        if (
            (
                $paymentType->getMaxConcurrentPayments() >= 0
                && $repo->countConcurrent() >= $paymentType->getMaxConcurrentPayments()
            )
            || (
                $userIdentifier
                && $paymentType->getMaxConcurrentAuthPayments() >= 0
                && $repo->countAuthConcurrent() >= $paymentType->getMaxConcurrentAuthPayments()
            )
            || (
                $userIdentifier
                && $paymentType->getMaxConcurrentAuthPaymentsPerUser() >= 0
                && $repo->countAuthConcurrent($userIdentifier) >= $paymentType->getMaxConcurrentAuthPaymentsPerUser()
            )
            || (
                !$userIdentifier
                && $paymentType->getMaxConcurrentUnauthPayments() >= 0
                && $repo->countUnauthConcurrent() >= $paymentType->getMaxConcurrentUnauthPayments()
            )
            || (
                !$userIdentifier
                && $paymentType->getMaxConcurrentUnauthPaymentsPerIp() >= 0
                && $repo->countUnauthConcurrent($payment->getClientIp()) >= $paymentType->getMaxConcurrentUnauthPaymentsPerIp()
            )
        ) {
            throw ApiError::withDetails(Response::HTTP_TOO_MANY_REQUESTS, 'Too many requests!', 'mono:too-many-requests');
        }

        $identifier = (string) Uuid::v4();
        $payment->setIdentifier($identifier);
        $payment->setPaymentStatus(PaymentStatus::PREPARED);

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $paymentPersistence = PaymentPersistence::fromPayment($payment);
        $paymentPersistence->setCreatedAt($now);
        if ($paymentType->isAuthRequired()) {
            $paymentPersistence->setUserIdentifier($userIdentifier);
        }
        $paymentPersistence->setDataProtectionDeclarationUrl($paymentType->getDataProtectionDeclarationUrl());

        $config = $this->configurationService->getConfig();
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $timeoutAt = $now->add(new \DateInterval($config['payment_session_timeout']));
        $paymentPersistence->setTimeoutAt($timeoutAt);

        try {
            $this->em->persist($paymentPersistence);
            $this->em->flush();
        } catch (\Exception $e) {
            throw ApiError::withDetails(Response::HTTP_INTERNAL_SERVER_ERROR, 'Payment could not be created!', 'mono:payment-not-created', ['message' => $e->getMessage()]);
        }

        $payment = self::createPaymentcreatePaymentFromPaymentPersistence($paymentPersistence);

        return $payment;
    }

    public function getPaymentPersistenceByIdentifier(string $identifier): PaymentPersistence
    {
        $repo = $this->em->getRepository(PaymentPersistence::class);
        assert($repo instanceof PaymentPersistenceRepository);
        /** @var ?PaymentPersistence $paymentPersistence */
        $paymentPersistence = $repo->findOneActive($identifier);

        if (!$paymentPersistence) {
            throw ApiError::withDetails(Response::HTTP_NOT_FOUND, 'Payment was not found!', 'mono:payment-not-found');
        }

        return $paymentPersistence;
    }

    /**
     * @return PaymentPersistence[]
     */
    public function getUnnotified(): array
    {
        $repo = $this->em->getRepository(PaymentPersistence::class);
        assert($repo instanceof PaymentPersistenceRepository);
        $paymentPersistences = $repo->findUnnotified();

        return $paymentPersistences;
    }

    public function notifyIfCompleted(PaymentPersistence $paymentPersistence)
    {
        // Only notify if the payment is completed
        if ($paymentPersistence->getPaymentStatus() !== PaymentStatus::COMPLETED) {
            return;
        }

        $lock = $this->createPaymentLock($paymentPersistence);
        if (!$lock->acquire()) {
            $this->auditLogger->debug('Failed to acquire lock for notify, skipping notify', $this->getLoggingContext($paymentPersistence));

            return;
        }
        $this->auditLogger->debug('Acquired lock for notify', $this->getLoggingContext($paymentPersistence));
        try {
            if ($paymentPersistence->getNotifiedAt() !== null) {
                return;
            }

            $type = $paymentPersistence->getType();
            $method = $paymentPersistence->getPaymentMethod();
            $paymentType = $this->configurationService->getPaymentTypeByType($type);

            $backendService = $this->backendService->getByPaymentType($paymentType);

            $this->auditLogger->debug('Notifying backend service', $this->getLoggingContext($paymentPersistence));

            $paymentMethod = $this->configurationService->getPaymentMethodByTypeAndPaymentMethod($type, $method);

            if ($paymentMethod->isDemoMode()) {
                $this->auditLogger->warning('Demo mode active, backend not notified.', $this->getLoggingContext($paymentPersistence));
                $isNotified = true;
            } else {
                $isNotified = $backendService->notify($paymentPersistence);
            }

            if ($isNotified) {
                $this->auditLogger->debug('Setting payment as notified', $this->getLoggingContext($paymentPersistence));
                $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
                $paymentPersistence->setNotifiedAt($now);
            }

            $lock->refresh();
            try {
                $this->em->persist($paymentPersistence);
                $this->em->flush();
            } catch (\Exception $e) {
                $this->auditLogger->error('Persisting the notification status failed', $this->getLoggingContext($paymentPersistence, ['exception' => $e]));
                throw ApiError::withDetails(Response::HTTP_INTERNAL_SERVER_ERROR, 'Payment could not be updated!', 'mono:payment-not-updated', ['message' => $e->getMessage()]);
            }
        } finally {
            $this->auditLogger->debug('Releasing lock for notify', $this->getLoggingContext($paymentPersistence));
            $lock->release();
        }
    }

    public function getPaymentByIdentifier(string $identifier): Payment
    {
        $paymentPersistence = $this->getPaymentPersistenceByIdentifier($identifier);

        $request = Request::createFromGlobals();
        $clientIp = $request->getClientIp();
        if ($paymentPersistence->getClientIp() !== $clientIp) {
            throw ApiError::withDetails(Response::HTTP_FORBIDDEN, 'Payment client IP not allowed!', 'mono:payment-client-ip-not-allowed');
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        if ($now >= $paymentPersistence->getTimeoutAt()) {
            throw ApiError::withDetails(Response::HTTP_GONE, 'Payment timeout exceeded!', 'mono:payment-timeout-exceeded');
        }

        $type = $paymentPersistence->getType();
        $paymentType = $this->configurationService->getPaymentTypeByType($type);
        if ($paymentType === null) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, 'Unknown payment type', 'mono:start-payment-unknown-payment-type');
        }

        $userIdentifier = $this->userSession->getUserIdentifier();
        if ($paymentType->isAuthRequired() && !$userIdentifier) {
            throw ApiError::withDetails(Response::HTTP_UNAUTHORIZED, 'Authorization required!', 'mono:authorization-required');
        }

        if ($paymentType->isAuthRequired() && $userIdentifier !== $paymentPersistence->getUserIdentifier()) {
            throw ApiError::withDetails(Response::HTTP_FORBIDDEN, 'Start payment user identifier not allowed!', 'mono:start-payment-user-identifier-not-allowed');
        }

        $backendService = $this->backendService->getByPaymentType($paymentType);

        // We allow the backend to update the payment data as long as we are in the prepared state
        if ($paymentPersistence->getPaymentStatus() === PaymentStatus::PREPARED) {
            $isDataUpdated = $backendService->updateData($paymentPersistence);
            if ($isDataUpdated) {
                $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
                $paymentPersistence->setDataUpdatedAt($now);
            }

            try {
                $this->em->persist($paymentPersistence);
                $this->em->flush();
            } catch (\Exception $e) {
                throw ApiError::withDetails(Response::HTTP_INTERNAL_SERVER_ERROR, 'Payment could not be updated!', 'mono:payment-not-updated', ['message' => $e->getMessage()]);
            }
        }

        $payment = self::createPaymentcreatePaymentFromPaymentPersistence($paymentPersistence);
        $paymentMethods = $this->configurationService->getPaymentMethodsByType($type);
        $paymentMethod = json_encode($paymentMethods);
        $payment->setPaymentMethod($paymentMethod);
        $recipient = $paymentType->getRecipient();
        $payment->setRecipient($recipient);

        // We give the backend service one last chance to change things, for example for translations
        $backendService->updateEntity($paymentPersistence, $payment);

        return $payment;
    }

    public static function createPaymentcreatePaymentFromPaymentPersistence(PaymentPersistence $paymentPersistence): Payment
    {
        $payment = new Payment();
        $payment->setIdentifier($paymentPersistence->getIdentifier());
        $payment->setReturnUrl($paymentPersistence->getReturnUrl());
        $payment->setPspReturnUrl($paymentPersistence->getPspReturnUrl());
        $payment->setLocalIdentifier($paymentPersistence->getLocalIdentifier());
        $payment->setPaymentStatus($paymentPersistence->getPaymentStatus());
        $payment->setPaymentReference($paymentPersistence->getPaymentReference());
        $payment->setAmount($paymentPersistence->getAmount());
        $payment->setCurrency($paymentPersistence->getCurrency());
        $payment->setAlternateName($paymentPersistence->getAlternateName());
        $payment->setHonorificPrefix($paymentPersistence->getHonorificPrefix());
        $payment->setGivenName($paymentPersistence->getGivenName());
        $payment->setFamilyName($paymentPersistence->getFamilyName());
        $payment->setCompanyName($paymentPersistence->getCompanyName());
        $payment->setHonorificSuffix($paymentPersistence->getHonorificSuffix());
        $payment->setRecipient($paymentPersistence->getRecipient());
        $payment->setDataProtectionDeclarationUrl($paymentPersistence->getDataProtectionDeclarationUrl());

        return $payment;
    }

    public function startPayAction(StartPayAction $startPayAction): StartResponseInterface
    {
        $identifier = $startPayAction->getIdentifier();
        $paymentPersistence = $this->getPaymentPersistenceByIdentifier($identifier);

        if ($paymentPersistence->getAmount() <= 0) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, 'Amount has to be bigger than 0!', 'mono:start-payment-amount-too-low');
        }

        $request = Request::createFromGlobals();
        $clientIp = $request->getClientIp();
        if ($paymentPersistence->getClientIp() !== $clientIp) {
            throw ApiError::withDetails(Response::HTTP_FORBIDDEN, 'Start payment client IP not allowed!', 'mono:start-payment-client-ip-not-allowed');
        }

        $status = $paymentPersistence->getPaymentStatus();
        if (!in_array($status, [PaymentStatus::PREPARED, PaymentStatus::STARTED, PaymentStatus::FAILED], true)) {
            throw new ApiError(Response::HTTP_BAD_REQUEST, "Can't (re)start payment with status: ".$status);
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        if ($now >= $paymentPersistence->getTimeoutAt()) {
            throw ApiError::withDetails(Response::HTTP_GONE, 'Start payment timeout exceeded!', 'mono:start-payment-timeout-exceeded');
        }

        $type = $paymentPersistence->getType();
        $paymentType = $this->configurationService->getPaymentTypeByType($type);
        if ($paymentType === null) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, 'Unknown payment type', 'mono:start-payment-unknown-payment-type');
        }

        $userIdentifier = $this->userSession->getUserIdentifier();
        if ($paymentType->isAuthRequired() && !$userIdentifier) {
            throw ApiError::withDetails(Response::HTTP_UNAUTHORIZED, 'Authorization required!', 'mono:authorization-required');
        }

        if ($paymentType->isAuthRequired() && $userIdentifier !== $paymentPersistence->getUserIdentifier()) {
            throw ApiError::withDetails(Response::HTTP_FORBIDDEN, 'Start payment user identifier not allowed!', 'mono:start-payment-user-identifier-not-allowed');
        }

        $pspReturnUrl = $startPayAction->getPspReturnUrl();
        if (!$paymentType->evaluatePspReturnUrlExpression($pspReturnUrl)) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, 'PSP return URL not allowed!', 'mono:psp-return-url-not-allowed');
        }

        $this->auditLogger->debug('Starting payment', $this->getLoggingContext($paymentPersistence));

        $paymentPersistence->setPspReturnUrl($pspReturnUrl);

        $paymentMethod = $startPayAction->getPaymentMethod();
        $paymentPersistence->setPaymentMethod($paymentMethod);

        $paymentContract = $this->configurationService->getPaymentContractByTypeAndPaymentMethod($type, $paymentMethod);
        $paymentPersistence->setPaymentContract((string) $paymentContract);

        $paymentPersistence->setPaymentStatus(PaymentStatus::STARTED);

        $config = $this->configurationService->getConfig();
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $timeoutAt = $now->add(new \DateInterval($config['payment_session_timeout']));
        $paymentPersistence->setTimeoutAt($timeoutAt);

        $paymentPersistence->setStartedAt($now);

        $paymentServiceProvider = $this->paymentServiceProviderService->getByPaymentContract($paymentContract);
        try {
            $startResponse = $paymentServiceProvider->start($paymentPersistence);
        } finally {
            try {
                $this->em->persist($paymentPersistence);
                $this->em->flush();
            } catch (\Exception $e) {
                $this->logger->error('Payment could not be updated!', $this->getLoggingContext($paymentPersistence, ['exception' => $e]));
                throw new ApiError(Response::HTTP_INTERNAL_SERVER_ERROR, 'Payment could not be updated!');
            }
        }

        return $startResponse;
    }

    /**
     * This iterates through all PSP connectors and asks them to handle the PSP data.
     * The first one that recognizes it will give us a matching payment ID.
     */
    public function completeGetPaymentId(string $pspData): string
    {
        $this->auditLogger->debug('Completing for PSP data', ['mono-psp-data' => $pspData]);
        // first map the PSP data to an existing payment entry by asking all PSP connectors
        foreach ($this->configurationService->getPaymentContracts() as $contract) {
            $pspService = $this->paymentServiceProviderService->getByPaymentContract($contract);
            try {
                $paymentId = $pspService->getPaymentIdForPspData($pspData);
            } catch (\Exception $e) {
                $this->logger->error('PSP service failed to get payment ID', ['exception' => $e]);
                continue;
            }
            if ($paymentId !== null) {
                return $paymentId;
            }
        }
        $this->auditLogger->error("PSP data wasn't handled by any connector", ['mono-psp-data' => $pspData]);
        throw new ApiError(Response::HTTP_BAD_REQUEST, 'PSP data not handled');
    }

    public function completePayAction(
        string $identifier
    ): CompleteResponseInterface {
        $paymentPersistence = $this->getPaymentPersistenceByIdentifier($identifier);

        $this->auditLogger->debug('Trying to complete payment', $this->getLoggingContext($paymentPersistence));

        $type = $paymentPersistence->getType();

        $paymentMethod = $paymentPersistence->getPaymentMethod();
        $paymentContract = $this->configurationService->getPaymentContractByTypeAndPaymentMethod($type, $paymentMethod);

        $paymentServiceProvider = $this->paymentServiceProviderService->getByPaymentContract($paymentContract);
        try {
            $completeResponse = $paymentServiceProvider->complete($paymentPersistence);
        } finally {
            try {
                $this->em->persist($paymentPersistence);
                $this->em->flush();
            } catch (\Exception $e) {
                $this->logger->error('Payment could not be updated!', $this->getLoggingContext($paymentPersistence, ['exception' => $e]));
                throw new ApiError(Response::HTTP_INTERNAL_SERVER_ERROR, 'Payment could not be updated!');
            }
        }

        $this->notifyIfCompleted($paymentPersistence);

        return $completeResponse;
    }

    public function cleanup()
    {
        $repo = $this->em->getRepository(PaymentPersistence::class);
        assert($repo instanceof PaymentPersistenceRepository);

        $this->logger->debug('Running cleanup');

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $cleanupConfigs = $this->configurationService->getCleanupConfiguration();
        foreach ($cleanupConfigs as $cleanupConfig) {
            $paymentStatus = $cleanupConfig['payment_status'];
            $timeoutBefore = $now->sub(new \DateInterval($cleanupConfig['timeout_before']));
            $paymentPersistences = $repo->findByPaymentStatusTimeoutBefore($paymentStatus, $timeoutBefore);
            foreach ($paymentPersistences as $paymentPersistence) {
                $type = $paymentPersistence->getType();
                $paymentType = $this->configurationService->getPaymentTypeByType($type);

                $backendService = $this->backendService->getByPaymentType($paymentType);
                $cleanupWorked = $backendService->cleanup($paymentPersistence);
                if ($cleanupWorked !== true) {
                    $this->logger->error('Backend cleanup failed, skipping further cleanup', $this->getLoggingContext($paymentPersistence));
                    continue;
                }

                $paymentMethod = $paymentPersistence->getPaymentMethod();
                // We only have a payment method once the payment was started
                assert($paymentMethod !== null || $paymentStatus === PaymentStatus::PREPARED);

                if ($paymentMethod !== null) {
                    $paymentContract = $this->configurationService->getPaymentContractByTypeAndPaymentMethod($type, $paymentMethod);
                    if ($paymentContract === null) {
                        // in case the config is wrong, better not delete the entry from the DB if some related data could
                        // be still stored somewhere that needs to be cleaned up

                        $this->logger->error("Can't find payment contract for method '$paymentMethod'. Can't clean up entry.", $this->getLoggingContext($paymentPersistence));
                        continue;
                    }

                    $paymentServiceProvider = $this->paymentServiceProviderService->getByPaymentContract($paymentContract);
                    try {
                        $cleanupWorked = $paymentServiceProvider->cleanup($paymentPersistence);
                    } catch (\Exception $e) {
                        $this->logger->error('PSP cleanup failed', $this->getLoggingContext($paymentPersistence, ['exception' => $e]));
                        $cleanupWorked = false;
                    }

                    if ($cleanupWorked !== true) {
                        $this->logger->error('Payment provider cleanup failed, skipping further cleanup', $this->getLoggingContext($paymentPersistence));
                        continue;
                    }
                }

                $this->em->remove($paymentPersistence);
            }
        }
        $this->em->flush();
    }
}
