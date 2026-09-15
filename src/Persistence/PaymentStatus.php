<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Persistence;

class PaymentStatus
{
    /**
     * Before the payment is successfully started with the payment service provider. In this state
     * it is filled/updated with information from the request and the backend service. A start attempt
     * that does not set another status remains prepared and may be retried.
     */
    public const PREPARED = 'prepared';

    /**
     * After the payment has started, but the final state of the payment isn't known yet.
     */
    public const PENDING = 'pending';

    /**
     * After the payment failed for some other reason, or if the reason is unknown.
     * After this the status no longer changes.
     */
    public const FAILED = 'failed';

    /**
     * After pending once we get back that the payment is finished.
     * After this the status no longer changes.
     */
    public const COMPLETED = 'completed';
}
