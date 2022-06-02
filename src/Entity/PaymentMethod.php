<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     collectionOperations={
 *     },
 *     itemOperations={
 *     },
 *     iri="https://schema.digital-blueprint.org/PaymentMethod",
 *     shortName="MonoPaymentMethod",
 *     normalizationContext={
 *         "groups" = {"MonoPaymentMethod:output"},
 *         "jsonld_embed_context" = true
 *     }
 * )
 */
class PaymentMethod
{
    /**
     * @ApiProperty(identifier=true)
     * @Groups({"MonoPaymentMethod:output"})
     */
    private $identifier;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/Text")
     * @Groups({"MonoPaymentMethod:output"})
     */
    private $name;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/URL")
     * @Groups({"MonoPaymentMethod:output"})
     */
    private $image;

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;

        return $this;
    }
}
