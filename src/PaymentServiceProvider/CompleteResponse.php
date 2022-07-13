<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\PaymentServiceProvider;

class CompleteResponse implements CompleteResponseInterface
{
    /**
     * @var string|null
     */
    private $returnUrl;

    /**
     * @var string|null
     */
    private $data;

    /**
     * @var string|null
     */
    private $error;

    public function __construct(
        ?string $returnUrl,
        string $data = null,
        string $error = null
    ) {
        $this->returnUrl = $returnUrl;
        $this->data = $data;
        $this->error = $error;
    }

    public function getReturnUrl(): ?string
    {
        return $this->returnUrl;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function getError(): ?string
    {
        return $this->error;
    }
}
