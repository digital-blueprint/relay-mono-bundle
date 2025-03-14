<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Config;

class PaymentMethod
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $pspContract;

    /**
     * @var string
     */
    private $pspMethod;

    /**
     * @var string
     */
    private $name;

    /**
     * @var ?string
     */
    private $image;

    /**
     * @var bool
     */
    private $demoMode;

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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function isDemoMode(): bool
    {
        return $this->demoMode;
    }

    public function setDemoMode(bool $demoMode): void
    {
        $this->demoMode = $demoMode;
    }

    /**
     * @param mixed[] $config
     */
    public static function fromConfig(string $identifier, array $config): PaymentMethod
    {
        $paymentMethod = new PaymentMethod();
        $paymentMethod->setIdentifier($identifier);
        $paymentMethod->setPspContract($config['contract']);
        $paymentMethod->setPspMethod($config['method']);
        $paymentMethod->setName($config['name']);
        $paymentMethod->setImage($config['image']);
        $paymentMethod->setDemoMode($config['demo_mode']);

        return $paymentMethod;
    }

    public function getPspContract(): string
    {
        return $this->pspContract;
    }

    public function setPspContract(string $pspContract): void
    {
        $this->pspContract = $pspContract;
    }

    public function getPspMethod(): string
    {
        return $this->pspMethod;
    }

    public function setPspMethod(string $pspMethod): void
    {
        $this->pspMethod = $pspMethod;
    }
}
