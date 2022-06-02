<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     collectionOperations={
 *         "post" = {
 *             "path" = "/mono/start-pay-actions",
 *             "openapi_context" = {
 *                 "tags" = {"ElectronicPayment"},
 *             },
 *         }
 *     },
 *     itemOperations={
 *     },
 *     iri="https://schema.digital-blueprint.org/StartPayAction",
 *     shortName="MonoStartPayAction",
 *     normalizationContext={
 *         "groups" = {"MonoPayment:output"},
 *         "jsonld_embed_context" = true
 *     },
 *     denormalizationContext={
 *         "groups" = {"MonoPayment:input"},
 *         "jsonld_embed_context" = true
 *     }
 * )
 */
class StartPayAction
{
    /**
     * @ApiProperty(identifier=true)
     * @Groups({"MonoPayment:input"})
     */
    private $identifier;

    /**
     * @var PaymentMethod
     * @ApiProperty(iri="https://schema.digital-blueprint.org/PaymentMethod")
     * @Groups({"MonoPayment:input"})
     */
    private $paymentMethod;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/URL")
     * @Groups({"MonoPayment:input"})
     */
    private $returnBaseUrl;

    /**
     * @var bool
     * @ApiProperty(iri="https://schema.org/Boolean")
     * @Groups({"MonoPayment:input"})
     */
    private $consent;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/URL")
     * @Groups({"MonoPayment:output"})
     */
    private $widgetUrl;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/Text")
     * @Groups({"MonoPayment:output"})
     */
    private $pspData;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/Text")
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

    public function getPaymentMethod(): PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(PaymentMethod $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getReturnBaseUrl(): string
    {
        return $this->returnBaseUrl;
    }

    public function setReturnBaseUrl(string $returnBaseUrl): self
    {
        $this->returnBaseUrl = $returnBaseUrl;

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

    public function getPspData(): string
    {
        return $this->pspData;
    }

    public function setPspData(string $pspData): self
    {
        $this->pspData = $pspData;

        return $this;
    }

    public function getPspError(): string
    {
        return $this->pspError;
    }

    public function setPspError(string $pspError): self
    {
        $this->pspError = $pspError;

        return $this;
    }
}
