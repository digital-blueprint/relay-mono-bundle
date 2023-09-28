<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Config;

class PaymentContract
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $service;

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getService(): string
    {
        return $this->service;
    }

    public function setService(string $service): self
    {
        $this->service = $service;

        return $this;
    }

    public static function fromConfig(string $identifier, array $config): PaymentContract
    {
        $paymentContract = new PaymentContract();
        $paymentContract->setIdentifier((string) $identifier);
        $paymentContract->setService((string) $config['service']);

        return $paymentContract;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->identifier;
    }
}
