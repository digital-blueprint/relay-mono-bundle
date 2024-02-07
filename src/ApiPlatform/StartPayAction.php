<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\ApiPlatform;

use Symfony\Component\Serializer\Annotation\Groups;

class StartPayAction
{
    /**
     * @Groups({"MonoPayment:input"})
     */
    private $identifier;

    /**
     * @var string
     *
     * @Groups({"MonoPayment:input"})
     */
    private $paymentMethod;

    /**
     * @var string
     *
     * @Groups({"MonoPayment:input"})
     */
    private $pspReturnUrl;

    /**
     * @var bool
     *
     * @Groups({"MonoPayment:input"})
     */
    private $consent;

    /**
     * @var string
     *
     * @Groups({"MonoPayment:output"})
     */
    private $widgetUrl;

    /**
     * @var string|null
     *
     * @Groups({"MonoPayment:output"})
     */
    private $pspData;

    /**
     * @var string|null
     *
     * @Groups({"MonoPayment:output"})
     */
    private $pspError;

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getPspReturnUrl(): string
    {
        return $this->pspReturnUrl;
    }

    public function setPspReturnUrl(string $pspReturnUrl): self
    {
        $this->pspReturnUrl = $pspReturnUrl;

        return $this;
    }

    public function isConsent(): bool
    {
        return $this->consent;
    }

    public function setConsent(bool $consent): self
    {
        $this->consent = $consent;

        return $this;
    }

    public function getWidgetUrl(): string
    {
        return $this->widgetUrl;
    }

    public function setWidgetUrl(string $widgetUrl): self
    {
        $this->widgetUrl = $widgetUrl;

        return $this;
    }

    public function getPspData(): ?string
    {
        return $this->pspData;
    }

    public function setPspData(?string $pspData): self
    {
        $this->pspData = $pspData;

        return $this;
    }

    public function getPspError(): ?string
    {
        return $this->pspError;
    }

    public function setPspError(?string $pspError): self
    {
        $this->pspError = $pspError;

        return $this;
    }
}
