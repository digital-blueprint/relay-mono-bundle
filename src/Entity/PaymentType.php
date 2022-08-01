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
    private $returnUrlExpression;

    /**
     * @var string
     */
    private $notifyUrlExpression;

    /**
     * @var string
     */
    private $pspReturnUrlExpression;

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

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

    public static function fromConfig(string $identifier, array $config): PaymentType
    {
        $paymentType = new PaymentType();
        $paymentType->setIdentifier((string) $identifier);
        $paymentType->setService((string) $config['service']);
        $paymentType->setAuthRequired((bool) $config['auth_required']);
        $paymentType->setReturnUrlExpression((string) $config['return_url_expression']);
        $paymentType->setNotifyUrlExpression((string) $config['notify_url_expression']);
        $paymentType->setPspReturnUrlExpression((string) $config['psp_return_url_expression']);

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
