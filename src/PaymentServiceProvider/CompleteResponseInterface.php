<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\PaymentServiceProvider;

interface CompleteResponseInterface
{
    public function getReturnUrl(): ?string;

    public function getData(): ?string;

    public function getError(): ?string;
}
