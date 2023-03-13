<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Service;

use Dbp\Relay\MonoBundle\Entity\Payment;
use Dbp\Relay\MonoBundle\Entity\PaymentPersistence;

interface BackendServiceInterface
{
    /**
     * Should fill out the PaymentPersistence or update its content and return
     * true if something was changed. This can be called multiple times for a payment.
     */
    public function updateData(PaymentPersistence $paymentPersistence): bool;

    /**
     * Can optionally update the Payment entity, before it is shown to the user.
     * PaymentPersistence should not be changed, only Payment.
     * Returns true if something in Payment was changed.
     */
    public function updateEntity(PaymentPersistence $paymentPersistence, Payment $payment): bool;

    /**
     * Should notify the backend service that a payment is complete and return true if the
     * notification was successful. After it has returned true once, it won't be called anymore.
     */
    public function notify(PaymentPersistence $paymentPersistence): bool;

    /**
     * Gets called right before the payment is deleted and allows the backend service
     * to delete related data to the payment. Should return true if deleting was successful or if there was nothing
     * to delete.
     */
    public function cleanup(PaymentPersistence $paymentPersistence): bool;
}
