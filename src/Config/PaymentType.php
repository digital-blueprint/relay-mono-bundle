<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Config;

use Dbp\Relay\CoreBundle\ExpressionLanguage\ExpressionLanguage;

class PaymentType
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var bool
     */
    private $authRequired;

    /**
     * @var ?int
     */
    private $maxConcurrentPayments;

    /**
     * @var ?int
     */
    private $maxConcurrentAuthPayments;

    /**
     * @var ?int
     */
    private $maxConcurrentAuthPaymentsPerUser;

    /**
     * @var ?int
     */
    private $maxConcurrentUnauthPayments;

    /**
     * @var ?int
     */
    private $maxConcurrentUnauthPaymentsPerIp;

    /**
     * @var ?string
     */
    private $returnUrlOverride;

    /**
     * @var string
     */
    private $returnUrlExpression;

    /**
     * @var string
     */
    private $notifyUrlExpression;

    /**
     * @var string
     */
    private $pspReturnUrlExpression;

    /**
     * @var ?string
     */
    private $recipient;

    /**
     * @var ?string
     */
    private $dataProtectionDeclarationUrl;

    /**
     * @var ?NotifyErrorConfig
     */
    private $notifyErrorConfig;

    /**
     * @var ?ReportingConfig
     */
    private $reportingConfig;

    /**
     * @var string
     */
    private $backendType;

    /**
     * @var string
     */
    private $sessionTimeout;

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getDataProtectionDeclarationUrl(): ?string
    {
        return $this->dataProtectionDeclarationUrl;
    }

    public function setDataProtectionDeclarationUrl(?string $dataProtectionDeclarationUrl): self
    {
        $this->dataProtectionDeclarationUrl = $dataProtectionDeclarationUrl;

        return $this;
    }

    public function isAuthRequired(): bool
    {
        return $this->authRequired;
    }

    public function setAuthRequired(bool $authRequired): self
    {
        $this->authRequired = $authRequired;

        return $this;
    }

    public function setMaxConcurrentPayments(?int $maxConcurrentPayments): void
    {
        $this->maxConcurrentPayments = $maxConcurrentPayments;
    }

    public function getMaxConcurrentPayments(): ?int
    {
        return $this->maxConcurrentPayments;
    }

    public function setMaxConcurrentAuthPayments(?int $maxConcurrentAuthPayments): void
    {
        $this->maxConcurrentAuthPayments = $maxConcurrentAuthPayments;
    }

    public function getMaxConcurrentAuthPayments(): ?int
    {
        return $this->maxConcurrentAuthPayments;
    }

    public function setMaxConcurrentAuthPaymentsPerUser(?int $maxConcurrentAuthPaymentsPerUser): void
    {
        $this->maxConcurrentAuthPaymentsPerUser = $maxConcurrentAuthPaymentsPerUser;
    }

    public function getMaxConcurrentAuthPaymentsPerUser(): ?int
    {
        return $this->maxConcurrentAuthPaymentsPerUser;
    }

    public function setMaxConcurrentUnauthPayments(?int $maxConcurrentUnauthPayments): void
    {
        $this->maxConcurrentUnauthPayments = $maxConcurrentUnauthPayments;
    }

    public function getMaxConcurrentUnauthPayments(): ?int
    {
        return $this->maxConcurrentUnauthPayments;
    }

    public function setMaxConcurrentUnauthPaymentsPerIp(?int $maxConcurrentUnauthPaymentsPerIp): void
    {
        $this->maxConcurrentUnauthPaymentsPerIp = $maxConcurrentUnauthPaymentsPerIp;
    }

    public function getMaxConcurrentUnauthPaymentsPerIp(): ?int
    {
        return $this->maxConcurrentUnauthPaymentsPerIp;
    }

    public function getReturnUrlOverride(): ?string
    {
        return $this->returnUrlOverride;
    }

    public function setReturnUrlOverride(?string $returnUrlOverride): self
    {
        $this->returnUrlOverride = $returnUrlOverride;

        return $this;
    }

    public function getReturnUrlExpression(): string
    {
        return $this->returnUrlExpression;
    }

    public function setReturnUrlExpression(string $returnUrlExpression): self
    {
        $this->returnUrlExpression = $returnUrlExpression;

        return $this;
    }

    public function evaluateReturnUrlExpression(string $url): bool
    {
        $expressionLanguage = new ExpressionLanguage();

        return $expressionLanguage->evaluate($this->getReturnUrlExpression(), ['url' => $url]);
    }

    public function getNotifyUrlExpression(): string
    {
        return $this->notifyUrlExpression;
    }

    public function setNotifyUrlExpression(string $notifyUrlExpression): self
    {
        $this->notifyUrlExpression = $notifyUrlExpression;

        return $this;
    }

    public function evaluateNotifyUrlExpression(string $url): bool
    {
        $expressionLanguage = new ExpressionLanguage();

        return $expressionLanguage->evaluate($this->getNotifyUrlExpression(), ['url' => $url]);
    }

    public function getPspReturnUrlExpression(): string
    {
        return $this->pspReturnUrlExpression;
    }

    public function setPspReturnUrlExpression(string $pspReturnUrlExpression): self
    {
        $this->pspReturnUrlExpression = $pspReturnUrlExpression;

        return $this;
    }

    public function evaluatePspReturnUrlExpression(string $url): bool
    {
        $expressionLanguage = new ExpressionLanguage();

        // pass pspReturnUrl only for backwards compat
        return $expressionLanguage->evaluate($this->getPspReturnUrlExpression(), ['url' => $url, 'pspReturnUrl' => $url]);
    }

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    public function setRecipient(?string $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getNotifyErrorConfig(): ?NotifyErrorConfig
    {
        return $this->notifyErrorConfig;
    }

    public function setNotifyErrorConfig(?NotifyErrorConfig $notifyErrorConfig)
    {
        $this->notifyErrorConfig = $notifyErrorConfig;
    }

    public function getReportingConfig(): ?ReportingConfig
    {
        return $this->reportingConfig;
    }

    public function setReportingConfig(?ReportingConfig $reportingConfig): self
    {
        $this->reportingConfig = $reportingConfig;

        return $this;
    }

    public static function fromConfig(string $identifier, array $config): PaymentType
    {
        $paymentType = new PaymentType();
        $paymentType->setIdentifier($identifier);
        $paymentType->setBackendType($config['backend_type']);
        $paymentType->setAuthRequired($config['auth_required']);
        $paymentType->setReturnUrlOverride($config['return_url_override']);
        $paymentType->setReturnUrlExpression($config['return_url_expression']);
        $paymentType->setNotifyUrlExpression($config['notify_url_expression']);
        $paymentType->setPspReturnUrlExpression($config['psp_return_url_expression']);
        $paymentType->setDataProtectionDeclarationUrl($config['data_protection_declaration_url']);
        $paymentType->setRecipient($config['recipient']);
        $paymentType->setSessionTimeout($config['session_timeout']);

        $concurrencyLimits = $config['concurrency_limits'];
        $paymentType->setMaxConcurrentPayments($concurrencyLimits['max_concurrent_payments']);
        $paymentType->setMaxConcurrentAuthPayments($concurrencyLimits['max_concurrent_auth_payments']);
        $paymentType->setMaxConcurrentAuthPaymentsPerUser($concurrencyLimits['max_concurrent_auth_payments_per_user']);
        $paymentType->setMaxConcurrentUnauthPayments($concurrencyLimits['max_concurrent_unauth_payments']);
        $paymentType->setMaxConcurrentUnauthPaymentsPerIp($concurrencyLimits['max_concurrent_unauth_payments_per_ip']);

        $notifyErrorConfig = $config['notify_error'] ?? null;
        if ($notifyErrorConfig !== null) {
            $paymentType->setNotifyErrorConfig(new NotifyErrorConfig($notifyErrorConfig));
        }

        $reportingConfig = $config['reporting'] ?? null;
        if ($reportingConfig !== null) {
            $paymentType->setReportingConfig(new ReportingConfig($reportingConfig));
        }

        return $paymentType;
    }

    public function getBackendType(): string
    {
        return $this->backendType;
    }

    public function setBackendType(string $backendType): void
    {
        $this->backendType = $backendType;
    }

    public function getSessionTimeout(): string
    {
        return $this->sessionTimeout;
    }

    public function setSessionTimeout(string $sessionTimeout): void
    {
        $this->sessionTimeout = $sessionTimeout;
    }
}
