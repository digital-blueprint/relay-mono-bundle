<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'MonoStartPayAction',
    types: ['https://schema.digital-blueprint.org/StartPayAction'],
    operations: [
        new GetCollection(
            uriTemplate: '/start-pay-actions',
            openapi: false,
            provider: DummyProvider::class
        ),
        new Get(
            uriTemplate: '/start-pay-actions/{identifier}',
            openapi: false,
            provider: DummyProvider::class
        ),
        new Post(
            uriTemplate: '/start-pay-actions',
            openapi: new Operation(
                tags: ['Electronic Payment']
            ),
            processor: StartPayActionProcessor::class
        ),
    ],
    routePrefix: '/mono',
    normalizationContext: [
        'groups' => ['MonoPayment:output'],
    ],
    denormalizationContext: [
        'groups' => ['MonoPayment:input'],
    ]
)]
class StartPayAction
{
    /**
     * @var string
     */
    #[ApiProperty(identifier: true)]
    #[Groups(['MonoPayment:input'])]
    private $identifier;

    /**
     * @var string
     */
    #[ApiProperty(iris: ['https://schema.org/Text'])]
    #[Groups(['MonoPayment:input'])]
    private $paymentMethod;

    /**
     * @var string
     */
    #[ApiProperty(iris: ['https://schema.org/URL'])]
    #[Groups(['MonoPayment:input'])]
    private $pspReturnUrl;

    /**
     * @var bool
     */
    #[ApiProperty(iris: ['https://schema.org/Boolean'])]
    #[Groups(['MonoPayment:input'])]
    private $consent;

    /**
     * @var string
     */
    #[ApiProperty(iris: ['https://schema.org/URL'])]
    #[Groups(['MonoPayment:output'])]
    private $widgetUrl;

    /**
     * @var string|null
     */
    #[ApiProperty(iris: ['https://schema.org/Text'])]
    #[Groups(['MonoPayment:output'])]
    private $pspData;

    /**
     * @var string|null
     */
    #[ApiProperty(iris: ['https://schema.org/Text'])]
    #[Groups(['MonoPayment:output'])]
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
