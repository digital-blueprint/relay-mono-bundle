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
 *             "path" = "/mono/payment",
 *             "openapi_context" = {
 *                 "tags" = {"ElectronicPayment"},
 *             },
 *         }
 *     },
 *     itemOperations={
 *         "get" = {
 *             "path" = "/mono/payment/{identifier}",
 *             "openapi_context" = {
 *                 "tags" = {"ElectronicPayment"},
 *             },
 *         },
 *     },
 *     iri="https://schema.digital-blueprint.org/Payment",
 *     shortName="MonoPayment",
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
class Payment
{
    public const ACTION_STATUS_PREPARED = 'prepared';
    public const ACTION_STATUS_STARTED = 'started';
    public const ACTION_STATUS_COMPLETED = 'completed';
    public const ACTION_STATUS_CANCELLED = 'cancelled';
    public const ACTION_STATUS_PENDING = 'pending';
    public const ACTION_STATUS_FAILED = 'failed';

    public const PRICE_CURRENCY = 'EUR';

    /**
     * @ApiProperty(identifier=true)
     * @Groups({"MonoPayment:output"})
     */
    private $identifier;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/Text")
     * @Groups({"MonoPayment:input"})
     */
    private $type;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/Text")
     * @Groups({"MonoPayment:input"})
     */
    private $data;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/Text")
     * @Groups({"MonoPayment:input"})
     */
    private $clientIp;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/URL")
     * @Groups({"MonoPayment:input"})
     */
    private $returnUrl;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/URL")
     * @Groups({"MonoPayment:input"})
     */
    private $notifyUrl;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/Text")
     * @Groups({"MonoPayment:output", "MonoPayment:input"})
     */
    private $localIdentifier;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/PaymentStatusType")
     * @Groups({"MonoPayment:output"})
     */
    private $paymentStatus;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/Text")
     * @Groups({"MonoPayment:output"})
     */
    private $paymentReference;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/Text")
     * @Groups({"MonoPayment:output"})
     */
    private $amount;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/Text")
     * @Groups({"MonoPayment:output"})
     */
    private $currency;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/Text")
     * @Groups({"MonoPayment:output"})
     */
    private $alternateName;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/Text")
     * @Groups({"MonoPayment:output"})
     */
    private $honorificPrefix;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/Text")
     * @Groups({"MonoPayment:output"})
     */
    private $givenName;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/Text")
     * @Groups({"MonoPayment:output"})
     */
    private $familyName;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/Text")
     * @Groups({"MonoPayment:output"})
     */
    private $companyName;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/Text")
     * @Groups({"MonoPayment:output"})
     */
    private $honorificSuffix;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/Text")
     * @Groups({"MonoPayment:output"})
     */
    private $recipient;

    /**
     * @var array<PaymentMethod>
     * @ApiProperty(iri="https://schema.org/ItemList")
     * @Groups({"MonoPayment:output"})
     */
    private $paymentMethod;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/URL")
     * @Groups({"MonoPayment:output"})
     */
    private $dataProtectionDeclarationUrl;

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getClientIp(): string
    {
        return $this->clientIp;
    }

    public function setClientIp(string $clientIp): self
    {
        $this->clientIp = $clientIp;

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

    public function getNotifyUrl(): string
    {
        return $this->notifyUrl;
    }

    public function setNotifyUrl(string $notifyUrl): self
    {
        $this->notifyUrl = $notifyUrl;

        return $this;
    }

    public function getLocalIdentifier(): string
    {
        return $this->localIdentifier;
    }

    public function setLocalIdentifier(string $localIdentifier): self
    {
        $this->localIdentifier = $localIdentifier;

        return $this;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): self
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function getPaymentReference(): string
    {
        return $this->paymentReference;
    }

    public function setPaymentReference(string $paymentReference): self
    {
        $this->paymentReference = $paymentReference;

        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getAlternateName(): string
    {
        return $this->alternateName;
    }

    public function setAlternateName(string $alternateName): self
    {
        $this->alternateName = $alternateName;

        return $this;
    }

    public function getHonorificPrefix(): string
    {
        return $this->honorificPrefix;
    }

    public function setHonorificPrefix(string $honorificPrefix): self
    {
        $this->honorificPrefix = $honorificPrefix;

        return $this;
    }

    public function getGivenName(): string
    {
        return $this->givenName;
    }

    public function setGivenName(string $givenName): self
    {
        $this->givenName = $givenName;

        return $this;
    }

    public function getFamilyName(): string
    {
        return $this->familyName;
    }

    public function setFamilyName(string $familyName): self
    {
        $this->familyName = $familyName;

        return $this;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): self
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getHonorificSuffix(): string
    {
        return $this->honorificSuffix;
    }

    public function setHonorificSuffix(string $honorificSuffix): self
    {
        $this->honorificSuffix = $honorificSuffix;

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

    public function getPaymentMethod(): array
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(array $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getDataProtectionDeclarationUrl(): string
    {
        return $this->dataProtectionDeclarationUrl;
    }

    public function setDataProtectionDeclarationUrl(string $dataProtectionDeclarationUrl): self
    {
        $this->dataProtectionDeclarationUrl = $dataProtectionDeclarationUrl;

        return $this;
    }
}
