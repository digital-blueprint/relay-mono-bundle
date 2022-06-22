<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Service;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\MonoBundle\Entity\Payment;
use Dbp\Relay\MonoBundle\Entity\PaymentPersistence;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class PaymentService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $manager = $managerRegistry->getManager('dbp_relay_mono_bundle');
        assert($manager instanceof EntityManagerInterface);
        $this->em = $manager;
    }


    /**
     * @param Payment $payment
     * @return Payment
     */
    public function createPayment(Payment $payment): Payment
    {
        $payment->setIdentifier((string)Uuid::v4());
        $payment->setPaymentStatus(Payment::PAYMENT_STATUS_PREPARED);

        $paymentPersistence = PaymentPersistence::fromPayment($payment);

        try {
            $this->em->persist($paymentPersistence);
            $this->em->flush();
        } catch (\Exception $e) {
            throw ApiError::withDetails(Response::HTTP_INTERNAL_SERVER_ERROR, 'Payment could not be created!', 'mono:payment-not-created', ['message' => $e->getMessage()]);
        }

        $payment = Payment::fromPaymentPersistence($paymentPersistence);

        return $payment;
    }

    /**
     * @param Payment $payment
     * @return Payment
     */
    public function getPaymentByIdentifier(string $identifier): Payment
    {
        $paymentPersistence = $this->em
            ->getRepository(PaymentPersistence::class)
            ->find($identifier);

        if (!$paymentPersistence) {
            throw ApiError::withDetails(Response::HTTP_NOT_FOUND, 'Payment was not found!', 'mono:payment-not-found');
        }

        $payment = Payment::fromPaymentPersistence($paymentPersistence);

        return $payment;
    }
}
