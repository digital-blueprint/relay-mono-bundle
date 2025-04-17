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
    shortName: 'MonoCompletePayAction',
    types: ['https://schema.digital-blueprint.org/CompletePayAction'],
    operations: [
        new GetCollection(
            uriTemplate: '/complete-pay-actions',
            openapi: new Operation(
                tags: ['Electronic Payment']
            ),
            provider: DummyProvider::class
        ),
        new Get(
            uriTemplate: '/complete-pay-actions/{identifier}',
            openapi: new Operation(
                tags: ['Electronic Payment']
            ),
            provider: DummyProvider::class
        ),
        new Post(
            uriTemplate: '/complete-pay-actions',
            openapi: new Operation(
                tags: ['Electronic Payment']
            ),
            processor: CompletePayActionProcessor::class
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
class CompletePayAction
{
    /**
     * @var string
     */
    #[ApiProperty(identifier: true)]
    #[Groups(['MonoPayment:output'])]
    private $identifier;

    /**
     * @var string
     */
    #[ApiProperty(iris: ['https://schema.org/Text'])]
    #[Groups(['MonoPayment:input'])]
    private $pspData;

    /**
     * @var string|null
     */
    #[ApiProperty(iris: ['https://schema.org/URL'])]
    #[Groups(['MonoPayment:output'])]
    private $returnUrl;

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
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

    public function getReturnUrl(): ?string
    {
        return $this->returnUrl;
    }

    public function setReturnUrl(?string $returnUrl): self
    {
        $this->returnUrl = $returnUrl;

        return $this;
    }
}
