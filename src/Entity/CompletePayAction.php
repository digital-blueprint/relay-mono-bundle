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
 *             "path" = "/mono/complete-pay-actions",
 *             "openapi_context" = {
 *                 "tags" = {"ElectronicPayment"},
 *             },
 *         }
 *     },
 *     itemOperations={
 *     },
 *     iri="https://schema.digital-blueprint.org/CompletePayAction",
 *     shortName="MonoCompletePayAction",
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
class CompletePayAction
{
    /**
     * @ApiProperty(identifier=true)
     * @Groups({"MonoPayment:output"})
     */
    private $identifier;

    /**
     * @var PaymentMethod
     * @ApiProperty(iri="https://schema.org/Text")
     * @Groups({"MonoPayment:input"})
     */
    private $routing;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/Text")
     * @Groups({"MonoPayment:input"})
     */
    private $pspData;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/URL")
     * @Groups({"MonoPayment:output"})
     */
    private $returnUrl;

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getRouting(): PaymentMethod
    {
        return $this->routing;
    }

    public function setRouting(PaymentMethod $routing): self
    {
        $this->routing = $routing;

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

    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }

    public function setReturnUrl(string $returnUrl): self
    {
        $this->returnUrl = $returnUrl;

        return $this;
    }
}
