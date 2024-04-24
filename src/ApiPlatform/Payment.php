<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\ApiPlatform;

use Symfony\Component\Serializer\Annotation\Groups;

class Payment
{
    #[Groups(['MonoPayment:output'])]
    private $identifier;

    /**
     * @var string
     */
    #[Groups(['MonoPayment:input'])]
    private $type;

    /**
     * @var string
     */
    #[Groups(['MonoPayment:input'])]
    private $data;

    /**
     * @var string|null
     */
    #[Groups(['MonoPayment:input'])]
    private $clientIp;

    /**
     * @var string|null
     */
    #[Groups(['MonoPayment:input', 'MonoPayment:output'])]
    private $returnUrl;

    /**
     * @var string|null
     */
    #[Groups(['MonoPayment:input'])]
    private $notifyUrl;

    /**
     * @var string|null
     */
    #[Groups(['MonoPayment:output', 'MonoPayment:input'])]
    private $pspReturnUrl;

    /**
     * @var string|null
     */
    #[Groups(['MonoPayment:output', 'MonoPayment:input'])]
    private $localIdentifier;

    /**
     * @var string
     */
    #[Groups(['MonoPayment:output'])]
    private $paymentStatus;

    /**
     * @var string|null
     */
    #[Groups(['MonoPayment:output'])]
    private $paymentReference;

    /**
     * @var string|null
     */
    #[Groups(['MonoPayment:output'])]
    private $amount;

    /**
     * @var string|null
     */
    #[Groups(['MonoPayment:output'])]
    private $currency;

    /**
     * @var string|null
     */
    #[Groups(['MonoPayment:output'])]
    private $alternateName;

    /**
     * @var string|null
     */
    #[Groups(['MonoPayment:output'])]
    private $honorificPrefix;

    /**
     * @var string|null
     */
    #[Groups(['MonoPayment:output'])]
    private $givenName;

    /**
     * @var string|null
     */
    #[Groups(['MonoPayment:output'])]
    private $familyName;

    /**
     * @var string|null
     */
    #[Groups(['MonoPayment:output'])]
    private $companyName;

    /**
     * @var string|null
     */
    #[Groups(['MonoPayment:output'])]
    private $honorificSuffix;

    /**
     * @var string|null
     */
    #[Groups(['MonoPayment:output'])]
    private $recipient;

    /**
     * @var string
     */
    #[Groups(['MonoPayment:output'])]
    private $paymentMethod;

    /**
     * @var string|null
     */
    #[Groups(['MonoPayment:output'])]
    private $dataProtectionDeclarationUrl;

    public function __construct()
    {
        $this->paymentMethod = '';
    }

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

    public function getClientIp(): ?string
    {
        return $this->clientIp;
    }

    public function setClientIp(?string $clientIp): self
    {
        $this->clientIp = $clientIp;

        return $this;
    }

    public function getReturnUrl(): ?string
    {
        return $this->returnUrl;
    }

    public function setReturnUrl(?string $returnUrl): self
    {
        $this->returnUrl = $returnUrl;

        return $this;
    }

    public function getNotifyUrl(): ?string
    {
        return $this->notifyUrl;
    }

    public function setNotifyUrl(?string $notifyUrl): self
    {
        $this->notifyUrl = $notifyUrl;

        return $this;
    }

    public function getPspReturnUrl(): ?string
    {
        return $this->pspReturnUrl;
    }

    public function setPspReturnUrl(?string $pspReturnUrl): self
    {
        $this->pspReturnUrl = $pspReturnUrl;

        return $this;
    }

    public function getLocalIdentifier(): ?string
    {
        return $this->localIdentifier;
    }

    public function setLocalIdentifier(?string $localIdentifier): self
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

    public function getPaymentReference(): ?string
    {
        return $this->paymentReference;
    }

    public function setPaymentReference(?string $paymentReference): self
    {
        $this->paymentReference = $paymentReference;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getAlternateName(): ?string
    {
        return $this->alternateName;
    }

    public function setAlternateName(?string $alternateName): self
    {
        $this->alternateName = $alternateName;

        return $this;
    }

    public function getHonorificPrefix(): ?string
    {
        return $this->honorificPrefix;
    }

    public function setHonorificPrefix(?string $honorificPrefix): self
    {
        $this->honorificPrefix = $honorificPrefix;

        return $this;
    }

    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    public function setGivenName(?string $givenName): self
    {
        $this->givenName = $givenName;

        return $this;
    }

    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }

    public function setFamilyName(?string $familyName): self
    {
        $this->familyName = $familyName;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): self
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getHonorificSuffix(): ?string
    {
        return $this->honorificSuffix;
    }

    public function setHonorificSuffix(?string $honorificSuffix): self
    {
        $this->honorificSuffix = $honorificSuffix;

        return $this;
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

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

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
}
