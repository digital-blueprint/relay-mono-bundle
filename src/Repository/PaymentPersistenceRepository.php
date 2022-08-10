<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Repository;

use Dbp\Relay\MonoBundle\Entity\Payment;
use Dbp\Relay\MonoBundle\Entity\PaymentPersistence;
use Doctrine\ORM\EntityRepository;

class PaymentPersistenceRepository extends EntityRepository
{
    public function findOneActive($identifier): ?PaymentPersistence
    {
        $now = new \DateTime();
        $qb = $this->createQueryBuilder('p')
            ->where('p.identifier = :identifier')
            ->andWhere('p.timeoutAt >= :timeoutAt')
            ->setParameters([
                'identifier' => $identifier,
                'timeoutAt' => $now,
            ]);

        $query = $qb->getQuery();

        $query->execute();

        return $query->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function findOneActiveBy(array $criteria): ?PaymentPersistence
    {
        $now = new \DateTime();
        $parameters = array_merge($criteria, [
            'timeoutAt' => $now,
        ]);
        $qb = $this->createQueryBuilder('p')
            ->where('p.type = :type')
            ->andWhere('p.data = :data')
            ->andWhere('p.timeoutAt >= :timeoutAt')
            ->setParameters($parameters);

        if (array_key_exists('userIdentifier', $criteria)) {
            $qb->andWhere('p.userIdentifier = :userIdentifier');
        }

        $query = $qb->getQuery();

        $query->execute();

        return $query->setMaxResults(1)
            ->getOneOrNullResult();
    }

    /**
     * @return PaymentPersistence[]
     */
    public function findUnnotified(): array
    {
        $parameters = [
            'paymentStatus' => Payment::PAYMENT_STATUS_COMPLETED,
        ];

        $qb = $this->createQueryBuilder('p');
        $qb->where('p.paymentStatus = :paymentStatus')
            ->andWhere($qb->expr()->isNull('p.notifiedAt'))
            ->setParameters($parameters);

        $query = $qb->getQuery();
        $items = $query->getResult();

        return $items;
    }
}
