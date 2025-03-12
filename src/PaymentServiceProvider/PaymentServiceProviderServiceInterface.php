<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\PaymentServiceProvider;

use Dbp\Relay\MonoBundle\Persistence\PaymentPersistence;

interface PaymentServiceProviderServiceInterface
{
    /**
     * Returns a list of payment contract IDs provided by the connector.
     *
     * @return string[]
     */
    public function getPspContracts(): array;

    /**
     * Returns a list of payment method IDs for a given contract.
     *
     * @return string[]
     */
    public function getPspMethods(string $pspContract): array;

    /**
     * Gets called with a filled out payment entry to start a payment with the PSP.
     */
    public function start(string $pspContract, string $pspMethod, PaymentPersistence $paymentPersistence): StartResponseInterface;

    /**
     * Gets called once the user has finished the payment process and returned from the PSP.
     * The $pspData is a PSP specific string provided by the PSP frontend process.
     */
    public function complete(string $pspContract, PaymentPersistence $paymentPersistence): CompleteResponseInterface;

    /**
     * Gets called right before the payment is deleted and allows the PSP service to delete related data to the payment.
     * Should return true if deleting was successful or if there was nothing to delete.
     */
    public function cleanup(string $pspContract, PaymentPersistence $paymentPersistence): bool;

    /**
     * Given a PSP data string should return a payment ID in case the PSP recognizes the
     * format. Or NULL in case the pspData isn't handled by this PSP.
     */
    public function getPaymentIdForPspData(string $pspData): ?string;
}
