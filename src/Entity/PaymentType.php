<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Entity;

class PaymentType
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $service;

    /**
     * @var bool
     */
    private $authRequired;

    /**
     * @var string
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
     * @var string
     */
    private $recipient;

    /**
     * @var ?string
     */
    private $dataProtectionDeclarationUrl;

    /**
     * @var ?array
     */
    private $notifyErrorConfig;

    /**
     * @var ?array
     */
    private $reportingConfig;

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

    public function getService(): string
    {
        return $this->service;
    }

    public function setService(string $service): self
    {
        $this->service = $service;

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

    public function getReturnUrlOverride(): string
    {
        return $this->returnUrlOverride;
    }

    public function setReturnUrlOverride(string $returnUrlOverride): self
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

    public function getNotifyUrlExpression(): string
    {
        return $this->notifyUrlExpression;
    }

    public function setNotifyUrlExpression(string $notifyUrlExpression): self
    {
        $this->notifyUrlExpression = $notifyUrlExpression;

        return $this;
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

    public function getRecipient(): string
    {
        return $this->recipient;
    }

    public function setRecipient(string $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getNotifyErrorConfig(): ?array
    {
        return $this->notifyErrorConfig;
    }

    public function setNotifyErrorConfig(?array $notifyErrorConfig): self
    {
        $this->notifyErrorConfig = $notifyErrorConfig;

        return $this;
    }

    public function getReportingConfig(): ?array
    {
        return $this->reportingConfig;
    }

    public function setReportingConfig(?array $reportingConfig): self
    {
        $this->reportingConfig = $reportingConfig;

        return $this;
    }

    public static function fromConfig(string $identifier, array $config): PaymentType
    {
        $paymentType = new PaymentType();
        $paymentType->setIdentifier((string) $identifier);
        $paymentType->setService((string) $config['service']);
        $paymentType->setAuthRequired((bool) $config['auth_required']);
        $paymentType->setReturnUrlOverride((string) $config['return_url_override']);
        $paymentType->setReturnUrlExpression((string) $config['return_url_expression']);
        $paymentType->setNotifyUrlExpression((string) $config['notify_url_expression']);
        $paymentType->setPspReturnUrlExpression((string) $config['psp_return_url_expression']);
        $paymentType->setDataProtectionDeclarationUrl($config['data_protection_declaration_url'] ?? null);
        $paymentType->setRecipient((string) $config['recipient']);
        if (
            array_key_exists('notify_error', $config)
            && is_array($config['notify_error'])
            && !empty($config['dsn'])
            && !empty($config['from'])
            && !empty($config['to'])
            && !empty($config['subject'])
            && !empty($config['html_template'])
            && !empty($config['completed_begin'])
        ) {
            $paymentType->setNotifyErrorConfig($config['notify_error']);
        }
        if (
            array_key_exists('reporting', $config)
            && is_array($config['reporting'])
            && !empty($config['dsn'])
            && !empty($config['from'])
            && !empty($config['to'])
            && !empty($config['subject'])
            && !empty($config['html_template'])
            && !empty($config['created_begin'])
        ) {
            $paymentType->setReportingConfig($config['reporting']);
        }

        return $paymentType;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->identifier;
    }
}
