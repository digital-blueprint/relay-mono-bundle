<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\ApiPlatform;

use Symfony\Component\Serializer\Annotation\Groups;

class CompletePayAction
{
    /**
     * @Groups({"MonoPayment:output"})
     */
    private $identifier;

    /**
     * @var string
     * @Groups({"MonoPayment:input"})
     */
    private $pspData;

    /**
     * @var string|null
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
