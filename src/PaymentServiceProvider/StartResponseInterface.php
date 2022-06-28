<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\PaymentServiceProvider;

interface StartResponseInterface
{
    public function getWidgetUrl(): string;

    public function getData(): ?string;

    public function getError(): ?string;
}
