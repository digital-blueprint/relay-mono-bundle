<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Persistence;

use Dbp\Relay\MonoBundle\ApiPlatform\Payment;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Dbp\Relay\MonoBundle\Persistence\PaymentPersistenceRepository")
 * @ORM\Table(name="mono_payments")
 */
class PaymentPersistence
{
    /**
     * @ORM\Id
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
     * @ORM\Column(type="string", nullable=true)
     */
    private $userIdentifier;

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
    private $pspReturnUrl;

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
     * @ORM\Column(type="string", nullable=true)
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
    private $paymentContract;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private $dataProtectionDeclarationUrl;

    /**
     * @var \DateTimeInterface
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var \DateTimeInterface
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $timeoutAt;

    /**
     * @var \DateTimeInterface|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dataUpdatedAt;

    /**
     * @var \DateTimeInterface|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $startedAt;

    /**
     * @var \DateTimeInterface|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $completedAt;

    /**
     * @var \DateTimeInterface|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $notifiedAt;

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

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    public function setUserIdentifier(?string $userIdentifier): self
    {
        $this->userIdentifier = $userIdentifier;

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

    public function getPaymentContract(): ?string
    {
        return $this->paymentContract;
    }

    public function setPaymentContract(?string $paymentContract): self
    {
        $this->paymentContract = $paymentContract;

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

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getTimeoutAt(): \DateTimeInterface
    {
        return $this->timeoutAt;
    }

    public function setTimeoutAt(\DateTimeInterface $timeoutAt): self
    {
        $this->timeoutAt = $timeoutAt;

        return $this;
    }

    public function getDataUpdatedAt(): ?\DateTimeInterface
    {
        return $this->dataUpdatedAt;
    }

    public function setDataUpdatedAt(?\DateTimeInterface $dataUpdatedAt): self
    {
        $this->dataUpdatedAt = $dataUpdatedAt;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeInterface $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): self
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getNotifiedAt(): ?\DateTimeInterface
    {
        return $this->notifiedAt;
    }

    public function setNotifiedAt(?\DateTimeInterface $notifiedAt): self
    {
        $this->notifiedAt = $notifiedAt;

        return $this;
    }

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
