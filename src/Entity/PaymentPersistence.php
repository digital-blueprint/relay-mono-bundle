<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="mono_payments")
 */
class PaymentPersistence
{
    public const ACTION_STATUS_PREPARED = 'prepared';
    public const ACTION_STATUS_STARTED = 'started';
    public const ACTION_STATUS_COMPLETED = 'completed';
    public const ACTION_STATUS_CANCELLED = 'cancelled';
    public const ACTION_STATUS_PENDING = 'pending';
    public const ACTION_STATUS_FAILED = 'failed';

    public const PRICE_CURRENCY = 'EUR';

    /**
     * @ORM\Id()
     * @ORM\Column(type="string", length=36, unique=true)
     */
    private $identifier;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $type;

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    private $data;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    private $clientIp;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private $returnUrl;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private $notifyUrl;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private $localIdentifier;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $paymentStatus;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private $paymentReference;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=8, nullable=true)
     */
    private $amount;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=3, nullable=true)
     */
    private $currency;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=3, nullable=true)
     */
    private $alternateName;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private $honorificPrefix;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private $givenName;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private $familyName;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private $companyName;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private $honorificSuffix;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private $recipient;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private $paymentMethod;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
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

    /**
     * @param Payment $payment
     * @return PaymentPersistence
     */
    public static function fromPayment(Payment $payment): PaymentPersistence
    {
        $paymentPersistence = new PaymentPersistence();
        $paymentPersistence->setIdentifier($payment->getIdentifier());
        $paymentPersistence->setType($payment->getType());
        $paymentPersistence->setData($payment->getData());
        $paymentPersistence->setClientIp($payment->getClientIp());
        $paymentPersistence->setReturnUrl($payment->getReturnUrl());
        $paymentPersistence->setNotifyUrl($payment->getNotifyUrl());
        $paymentPersistence->setLocalIdentifier($payment->getLocalIdentifier());
        $paymentPersistence->setPaymentStatus($payment->getPaymentStatus());

        return $paymentPersistence;
    }
}
