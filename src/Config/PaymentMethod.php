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
    private $contract;

    /**
     * @var string
     */
    private $method;

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
        $paymentMethod->setContract($config['contract']);
        $paymentMethod->setMethod($config['method']);
        $paymentMethod->setName($config['name']);
        $paymentMethod->setImage($config['image']);
        $paymentMethod->setDemoMode($config['demo_mode']);

        return $paymentMethod;
    }

    public function getContract(): string
    {
        return $this->contract;
    }

    public function setContract(string $contract): void
    {
        $this->contract = $contract;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }
}
