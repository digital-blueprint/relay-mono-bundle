<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Service;

use Dbp\Relay\MonoBundle\PaymentServiceProvider\CompleteResponseInterface;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\StartResponseInterface;
use Dbp\Relay\MonoBundle\Persistence\PaymentPersistence;

interface PaymentServiceProviderServiceInterface
{
    /**
     * Gets called with a filled out payment entry to start a payment with the PSP.
     */
    public function start(PaymentPersistence $paymentPersistence): StartResponseInterface;

    /**
     * Gets called once the user has finished the payment process and returned from the PSP.
     * The $pspData is a PSP specific string provided by the PSP frontend process.
     */
    public function complete(PaymentPersistence $paymentPersistence): CompleteResponseInterface;

    /**
     * Gets called right before the payment is deleted and allows the PSP service to delete related data to the payment.
     * Should return true if deleting was successful or if there was nothing to delete.
     */
    public function cleanup(PaymentPersistence $paymentPersistence): bool;

    /**
     * Given a PSP data string should return a payment ID in case the PSP recognizes the
     * format. Or NULL in case the pspData isn't handled by this PSP.
     */
    public function getPaymentIdForPspData(string $pspData): ?string;
}
