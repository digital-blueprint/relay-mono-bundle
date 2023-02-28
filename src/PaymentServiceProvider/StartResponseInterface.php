<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\PaymentServiceProvider;

interface StartResponseInterface
{
    /**
     * Should return a URL to a page shown to the user.
     */
    public function getWidgetUrl(): string;

    /**
     * A JSON encoded string of PSP specific data that will be forwarded to the page loaded via getWidgetUrl()
     * via post-message.
     */
    public function getData(): ?string;

    /**
     * A JSON encoded string containing PSP specific error related data that will be forwarded to the page loaded
     * via the getWidgetUrl() via post-message.
     */
    public function getError(): ?string;
}
