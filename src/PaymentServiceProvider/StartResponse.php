<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\PaymentServiceProvider;

class StartResponse implements StartResponseInterface
{
    /**
     * @var string
     */
    private $widgetUrl;

    /**
     * @var string|null
     */
    private $data;

    /**
     * @var string|null
     */
    private $error;

    public function __construct(
        string $widgetUrl,
        string $data = null,
        string $error = null
    ) {
        $this->widgetUrl = $widgetUrl;
        $this->data = $data;
        $this->error = $error;
    }

    /**
     * @return string|null
     */
    public function getWidgetUrl(): string
    {
        return $this->widgetUrl;
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
