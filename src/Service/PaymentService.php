<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Service;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\MonoBundle\Entity\Payment;
use Dbp\Relay\MonoBundle\Entity\PaymentPersistence;
use Dbp\Relay\MonoBundle\Entity\PaymentType;
use Dbp\Relay\MonoBundle\Entity\StartPayAction;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\CompleteResponseInterface;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\StartResponseInterface;
use Dbp\Relay\MonoBundle\Repository\PaymentPersistenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        $this->logger = new NullLogger();
    }

    public function checkConnection()
    {
        $this->em->getConnection()->connect();
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
            $repo = $paymentPersistence = $this->em
                ->getRepository(PaymentPersistence::class);
            assert($repo instanceof PaymentPersistenceRepository);
            /** @var PaymentPersistence $paymentPersistence */
            $paymentPersistence = $repo->findOneActiveBy($criteria);
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
            $paymentPersistence->setDataProtectionDeclarationUrl($paymentType->getDataProtectionDeclarationUrl());
        }

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

        return $payment;
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
        $type = $paymentPersistence->getType();
        $paymentType = $this->configurationService->getPaymentTypeByType($type);

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
                Payment::PAYMENT_STATUS_PREPARED,
                Payment::PAYMENT_STATUS_STARTED,
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
        $paymentPersistence->setPspReturnUrl($pspReturnUrl);

        $paymentMethod = $startPayAction->getPaymentMethod();
        $paymentPersistence->setPaymentMethod($paymentMethod);

        $paymentContract = $this->configurationService->getPaymentContractByTypeAndPaymentMethod($type, $paymentMethod);
        $paymentPersistence->setPaymentContract((string) $paymentContract);

        $paymentPersistence->setPaymentStatus(Payment::PAYMENT_STATUS_STARTED);

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

        $cleanupConfigs = $this->configurationService->getCleanupConfiguration();
        foreach ($cleanupConfigs as $cleanupConfig) {
            $paymentStatus = $cleanupConfig['payment_status'];
            $timeoutBefore = new \DateTime($cleanupConfig['timeout_before']);
            $paymentPersistences = $repo->findByPaymentStatusTimeoutBefore($paymentStatus, $timeoutBefore);
            foreach ($paymentPersistences as $paymentPersistence) {
                $type = $paymentPersistence->getType();
                $paymentType = $this->configurationService->getPaymentTypeByType($type);

                $backendService = $this->backendService->getByPaymentType($paymentType);
                $backendService->cleanup($paymentPersistence);

                $paymentMethod = $paymentPersistence->getPaymentMethod();
                $paymentContract = $this->configurationService->getPaymentContractByTypeAndPaymentMethod($type, $paymentMethod);

                $paymentServiceProvider = $this->paymentServiceProviderService->getByPaymentContract($paymentContract);
                $paymentServiceProvider->cleanup($paymentPersistence);

                $this->em->remove($paymentPersistence);
            }
        }
        $this->em->flush();
    }

    public function sendNotifyError(PaymentType $paymentType)
    {
        $repo = $this->em->getRepository(PaymentPersistence::class);
        assert($repo instanceof PaymentPersistenceRepository);

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

    public function sendReporting(PaymentType $paymentType)
    {
        $repo = $this->em->getRepository(PaymentPersistence::class);
        assert($repo instanceof PaymentPersistenceRepository);

        $reportingConfig = $paymentType->getReportingConfig();

        $type = $paymentType->getIdentifier();
        $createdSince = new \DateTime($reportingConfig['created_begin']);
        $count = $repo->countByTypeCreatedSince($type, $createdSince);

        if (count($count)) {
            $context = [
                'paymentType' => $paymentType,
                'count' => $count,
            ];

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

        $mailer->send($email);
    }
}
