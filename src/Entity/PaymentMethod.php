<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Entity;

class PaymentMethod implements \JsonSerializable
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
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

    /**
     * @param array $config
     * @return PaymentMethod
     */
    public static function fromConfig(array $config): PaymentMethod
    {
        $paymentMethod = new PaymentMethod();
        $paymentMethod->setIdentifier((string)$config['identifier']);
        $paymentMethod->setName((string)$config['name']);
        $paymentMethod->setImage((string)$config['image']);

        return $paymentMethod;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'identifier' => $this->identifier,
            'name' => $this->name,
            'image' => $this->image,
        ];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->identifier;
    }
}
