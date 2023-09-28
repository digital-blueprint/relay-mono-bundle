<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Entity;

class PaymentStatus
{
    /**
     * After the payment is first created. In this state it is filled/updated with information
     * from the request and the backend service.
     */
    public const PREPARED = 'prepared';

    /**
     * After the payment is filled out and submitted to the payment service provider.
     */
    public const STARTED = 'started';

    /**
     * After the payment has started, in case we get some information back from the service provider,
     * but the final state of the payment isn't known yet.
     */
    public const PENDING = 'pending';

    /**
     * After the payment failed for some other reason, or if the reason is unknown.
     * After this the status no longer changes.
     */
    public const FAILED = 'failed';

    /**
     * After started/pending once we get back that the payment is finished.
     * After this the status no longer changes.
     */
    public const COMPLETED = 'completed';
}
