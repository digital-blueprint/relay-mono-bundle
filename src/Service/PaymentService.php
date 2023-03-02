<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Service;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\MonoBundle\Entity\Payment;
use Dbp\Relay\MonoBundle\Entity\PaymentPersistence;
use Dbp\Relay\MonoBundle\Entity\PaymentStatus;
use Dbp\Relay\MonoBundle\Entity\PaymentType;
use Dbp\Relay\MonoBundle\Entity\StartPayAction;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\CompleteResponseInterface;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\StartResponseInterface;
use Dbp\Relay\MonoBundle\Repository\PaymentPersistenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Uid\Uuid;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

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
        $this->em->getConnection()->connect();
    }

    private function createPaymentLock(PaymentPersistence $payment): LockInterface
    {
        $resourceKey = sprintf(
            'mono-%s',
            $payment->getIdentifier()
        );

        return $this->lockFactory->createLock($resourceKey, 60, true);
    }

    private function getLoggingContext(PaymentPersistence $payment): array
    {
        return ['relay-mono-payment-id' => $payment->getIdentifier()];
    }

    public function createPayment(Payment $payment): Payment
    {
        $type = $payment->getType();
        $paymentType = $this->configurationService->getPaymentTypeByType($type);
        if ($paymentType === null) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, 'Unknown payment type', 'mono:unknown-payment-type');
        }

        $returnUrlOverride = $paymentType->getReturnUrlOverride();
        if ($returnUrlOverride) {
            $payment->setReturnUrl($returnUrlOverride);
        }

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

        $paymentPersistence = PaymentPersistence::fromPayment($payment);
        $createdAt = new \DateTime();
        $paymentPersistence->setCreatedAt($createdAt);
        $paymentPersistence->setNumberOfUses(0);
        if ($paymentType->isAuthRequired()) {
            $paymentPersistence->setUserIdentifier($userIdentifier);
        }
        $paymentPersistence->setDataProtectionDeclarationUrl($paymentType->getDataProtectionDeclarationUrl());

        $config = $this->configurationService->getConfig();
        $timeoutAt = new \DateTime();
        $timeout = $config['payment_session_timeout'];
        $timeoutAt->modify('+'.(int) $timeout.' seconds');
        $paymentPersistence->setTimeoutAt($timeoutAt);

        try {
            $this->em->persist($paymentPersistence);
            $this->em->flush();
        } catch (\Exception $e) {
            throw ApiError::withDetails(Response::HTTP_INTERNAL_SERVER_ERROR, 'Payment could not be created!', 'mono:payment-not-created', ['message' => $e->getMessage()]);
        }

        $payment = Payment::fromPaymentPersistence($paymentPersistence);
        $this->adjustPaymentForDemoMode($paymentType, $payment);

        return $payment;
    }

    private function adjustPaymentForDemoMode(PaymentType $paymentType, Payment $payment)
    {
        // in case the demo mode is active we want to show this in the UI to the user somehow.
        // the alternate name is usually shown to the user, so attach it there.
        if ($paymentType->isDemoMode()) {
            $payment->setAlternateName($payment->getAlternateName().' [DEMO MODE]');
        }
    }

    public function getPaymentPersistenceByIdentifier(string $identifier): PaymentPersistence
    {
        $repo = $this->em->getRepository(PaymentPersistence::class);
        assert($repo instanceof PaymentPersistenceRepository);
        /** @var PaymentPersistence $paymentPersistence */
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

    public function notify(PaymentPersistence $paymentPersistence)
    {
        // Only notify if the payment is completed
        if ($paymentPersistence->getPaymentStatus() !== PaymentStatus::COMPLETED) {
            return;
        }

        $lock = $this->createPaymentLock($paymentPersistence);
        if (!$lock->acquire()) {
            return;
        }
        try {
            if ($paymentPersistence->getNotifiedAt() !== null) {
                return;
            }

            $type = $paymentPersistence->getType();
            $paymentType = $this->configurationService->getPaymentTypeByType($type);

            $backendService = $this->backendService->getByPaymentType($paymentType);

            $this->auditLogger->debug('Notifying backend service', $this->getLoggingContext($paymentPersistence));

            if ($paymentType->isDemoMode()) {
                $this->auditLogger->warning('Demo mode active, backend not notified.', $this->getLoggingContext($paymentPersistence));
                $isNotified = true;
            } else {
                $isNotified = $backendService->notify($paymentPersistence);
            }

            if ($isNotified) {
                $notifiedAt = new \DateTime();
                $paymentPersistence->setNotifiedAt($notifiedAt);
            }

            $lock->refresh();
            try {
                $this->em->persist($paymentPersistence);
                $this->em->flush();
            } catch (\Exception $e) {
                throw ApiError::withDetails(Response::HTTP_INTERNAL_SERVER_ERROR, 'Payment could not be updated!', 'mono:payment-not-updated', ['message' => $e->getMessage()]);
            }
        } finally {
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

        $now = new \DateTime();
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

        if (in_array(
            $paymentPersistence->getPaymentStatus(),
            [
                PaymentStatus::PREPARED,
                PaymentStatus::STARTED,
            ],
            true
        )) {
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
        }

        $payment = Payment::fromPaymentPersistence($paymentPersistence);
        $paymentMethods = $this->configurationService->getPaymentMethodsByType($type);
        $paymentMethod = json_encode($paymentMethods);
        $payment->setPaymentMethod($paymentMethod);
        $recipient = $paymentType->getRecipient();
        $payment->setRecipient($recipient);
        $this->adjustPaymentForDemoMode($paymentType, $payment);

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

        if (!$startPayAction->isRestart() && $paymentPersistence->getStartedAt()) {
            throw ApiError::withDetails(Response::HTTP_TOO_MANY_REQUESTS, 'Start payment too many requests!', 'mono:start-payment-too-many-requests');
        }

        $now = new \DateTime();
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
        $expressionLanguage = new ExpressionLanguage();
        if ($paymentType->getPspReturnUrlExpression() && !$expressionLanguage->evaluate($paymentType->getPspReturnUrlExpression(), ['payment' => $paymentPersistence, 'pspReturnUrl' => $pspReturnUrl])) {
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
        $timeoutAt = new \DateTime();
        $timeout = $config['payment_session_timeout'];
        $timeoutAt->modify('+'.(int) $timeout.' seconds');
        $paymentPersistence->setTimeoutAt($timeoutAt);

        $startedAt = new \DateTime();
        $paymentPersistence->setStartedAt($startedAt);

        try {
            $this->em->persist($paymentPersistence);
            $this->em->flush();
        } catch (\Exception $e) {
            $this->logger->error('Payment could not be updated!', ['exception' => $e]);
            throw new ApiError(Response::HTTP_INTERNAL_SERVER_ERROR, 'Payment could not be updated!');
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

        $this->auditLogger->debug('Completing payment', $this->getLoggingContext($paymentPersistence));

        $type = $paymentPersistence->getType();

        $paymentMethod = $paymentPersistence->getPaymentMethod();
        $paymentContract = $this->configurationService->getPaymentContractByTypeAndPaymentMethod($type, $paymentMethod);

        $paymentServiceProvider = $this->paymentServiceProviderService->getByPaymentContract($paymentContract);
        $completeResponse = $paymentServiceProvider->complete($paymentPersistence, $pspData);

        try {
            $this->em->persist($paymentPersistence);
            $this->em->flush();
        } catch (\Exception $e) {
            $this->logger->error('Payment could not be updated!', ['exception' => $e]);
            throw new ApiError(Response::HTTP_INTERNAL_SERVER_ERROR, 'Payment could not be updated!');
        }

        $this->notify($paymentPersistence);

        return $completeResponse;
    }

    public function cleanup()
    {
        $repo = $this->em->getRepository(PaymentPersistence::class);
        assert($repo instanceof PaymentPersistenceRepository);

        $this->logger->debug('Running cleanup');

        $cleanupConfigs = $this->configurationService->getCleanupConfiguration();
        foreach ($cleanupConfigs as $cleanupConfig) {
            $paymentStatus = $cleanupConfig['payment_status'];
            $timeoutBefore = new \DateTime($cleanupConfig['timeout_before']);
            $paymentPersistences = $repo->findByPaymentStatusTimeoutBefore($paymentStatus, $timeoutBefore);
            foreach ($paymentPersistences as $paymentPersistence) {
                $type = $paymentPersistence->getType();
                $paymentType = $this->configurationService->getPaymentTypeByType($type);

                $backendService = $this->backendService->getByPaymentType($paymentType);
                $cleanupWorked = $backendService->cleanup($paymentPersistence);
                if ($cleanupWorked !== true) {
                    $this->logger->error('Backend cleanup failed for '.$paymentPersistence->getIdentifier().', skipping further cleanup');
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

                        $this->logger->error("Can't find payment contract for method '$paymentMethod'. Can't clean up entry: ".$paymentPersistence->getIdentifier());
                        continue;
                    }
                    $paymentServiceProvider = $this->paymentServiceProviderService->getByPaymentContract($paymentContract);
                    $cleanupWorked = $paymentServiceProvider->cleanup($paymentPersistence);
                    if ($cleanupWorked !== true) {
                        $this->logger->error('Payment provider cleanup failed for '.$paymentPersistence->getIdentifier().', skipping further cleanup');
                        continue;
                    }
                }

                $this->em->remove($paymentPersistence);
            }
        }
        $this->em->flush();
    }

    public function sendNotifyError(PaymentType $paymentType)
    {
        $repo = $this->em->getRepository(PaymentPersistence::class);
        assert($repo instanceof PaymentPersistenceRepository);

        $this->logger->debug('Send notify error for: '.$paymentType->getIdentifier());

        $notifyErrorConfig = $paymentType->getNotifyErrorConfig();

        $type = $paymentType->getIdentifier();
        $completedSince = new \DateTime($notifyErrorConfig['completed_begin']);
        $items = $repo->findUnnotifiedByTypeCompletedSince($type, $completedSince);
        $count = count($items);

        if ($count) {
            $context = [
                'paymentType' => $paymentType,
                'items' => $items,
                'count' => $count,
            ];

            $this->sendEmail($notifyErrorConfig, $context);
        }
    }

    public function sendAllReporting(string $email = '')
    {
        $paymentTypes = $this->configurationService->getPaymentTypes();

        foreach ($paymentTypes as $paymentType) {
            if ($paymentType->getReportingConfig()) {
                $this->sendReporting($paymentType, $email);
            }
        }
    }

    public function sendReporting(PaymentType $paymentType, string $email = '')
    {
        $repo = $this->em->getRepository(PaymentPersistence::class);
        assert($repo instanceof PaymentPersistenceRepository);

        $this->logger->debug('Send reporting for: '.$paymentType->getIdentifier());

        $reportingConfig = $paymentType->getReportingConfig();

        $type = $paymentType->getIdentifier();
        $createdSince = new \DateTime($reportingConfig['created_begin']);
        $count = $repo->countByTypeCreatedSince($type, $createdSince);

//        if (count($count)) {
        // We want a report every day, even if there are no payments
        if (true) {
            $context = [
                'paymentType' => $paymentType,
                'createdSince' => $createdSince,
                'createdTo' => new \DateTime(),
                'count' => $count,
            ];

            if ($email !== '') {
                $reportingConfig['to'] = $email;
            }

            $this->sendEmail($reportingConfig, $context);
        }
    }

    private function sendEmail(array $config, array $context)
    {
        $loader = new FilesystemLoader(dirname(__FILE__).'/../Resources/views/');
        $twig = new Environment($loader);

        $template = $twig->load($config['html_template']);
        $html = $template->render($context);

        $transport = Transport::fromDsn($config['dsn']);
        $mailer = new Mailer($transport);

        $email = (new Email())
            ->from($config['from'])
            ->to($config['to'])
            ->subject($config['subject'])
            ->html($html);

        $this->logger->debug('Sending email to: '.$config['to']);
        $mailer->send($email);
    }
}
