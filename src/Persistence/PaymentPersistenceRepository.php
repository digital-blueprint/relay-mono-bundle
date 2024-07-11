<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Persistence;

use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<PaymentPersistence>
 */
class PaymentPersistenceRepository extends EntityRepository
{
    public function findOneActive($identifier): ?PaymentPersistence
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $qb = $this->createQueryBuilder('p')
            ->where('p.identifier = :identifier')
            ->andWhere('p.timeoutAt >= :timeoutAt')
            ->setParameter('identifier', $identifier)
            ->setParameter('timeoutAt', $now);

        $query = $qb->getQuery();

        $query->execute();

        return $query->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function countConcurrent(): int
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $qb = $this->createQueryBuilder('p');
        $qb->select('count(p.identifier)')
            ->where('p.timeoutAt >= :timeoutAt')
            ->andWhere('p.completedAt IS NULL')
            ->setParameter('timeoutAt', $now);

        $query = $qb->getQuery();

        $count = (int) $query->getSingleScalarResult();

        return $count;
    }

    public function countAuthConcurrent(?string $userIdentifier = null): int
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $qb = $this->createQueryBuilder('p')
            ->select('count(p.identifier)')
            ->where('p.timeoutAt >= :timeoutAt')
            ->andWhere('p.completedAt IS NULL')
            ->andWhere('p.userIdentifier IS NOT NULL')
            ->setParameter('timeoutAt', $now);

        if ($userIdentifier !== null) {
            $qb->andWhere('p.userIdentifier = :userIdentifier');
            $qb->setParameter('userIdentifier', $userIdentifier);
        }

        $query = $qb->getQuery();

        $count = (int) $query->getSingleScalarResult();

        return $count;
    }

    public function countUnauthConcurrent(?string $clientIp = null): int
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $qb = $this->createQueryBuilder('p')
            ->select('count(p.identifier)')
            ->where('p.timeoutAt >= :timeoutAt')
            ->andWhere('p.completedAt IS NULL')
            ->andWhere('p.userIdentifier IS NULL')
            ->setParameter('timeoutAt', $now);

        if ($clientIp !== null) {
            $qb->andWhere('p.clientIp = :clientIp');
            $qb->setParameter('clientIp', $clientIp);
        }

        $query = $qb->getQuery();

        $count = (int) $query->getSingleScalarResult();

        return $count;
    }

    /**
     * @return PaymentPersistence[]
     */
    public function findUnnotified(): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.paymentStatus = :paymentStatus')
            ->andWhere($qb->expr()->isNull('p.notifiedAt'))
            ->setParameter('paymentStatus', PaymentStatus::COMPLETED);

        $query = $qb->getQuery();
        $items = $query->getResult();

        return $items;
    }

    public function findUnnotifiedByTypeCompletedSince($type, \DateTimeInterface $completedSince)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.type = :type')
            ->andWhere('p.paymentStatus = :paymentStatus')
            ->andWhere('p.completedAt >= :completedSince')
            ->andWhere($qb->expr()->isNull('p.notifiedAt'))
            ->setParameter('type', $type)
            ->setParameter('paymentStatus', PaymentStatus::COMPLETED)
            ->setParameter('completedSince', $completedSince);

        $query = $qb->getQuery();
        $items = $query->getResult();

        return $items;
    }

    /**
     * @return PaymentPersistence[]
     */
    public function findByPaymentStatusTimeoutBefore(string $paymentStatus, \DateTimeInterface $timeoutBefore): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.paymentStatus = :paymentStatus')
            ->andWhere('p.timeoutAt < :timeoutBefore')
            ->setParameter('paymentStatus', $paymentStatus)
            ->setParameter('timeoutBefore', $timeoutBefore);

        $query = $qb->getQuery();
        $items = $query->getResult();

        return $items;
    }

    /**
     * @return int[]
     */
    public function countByTypeCreatedSince(string $type, \DateTimeInterface $createdSince): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('p.paymentStatus', 'count(p.identifier)')
            ->where('p.type = :type')
            ->andWhere('p.createdAt >= :createdSince')
            ->groupBy('p.paymentStatus')
            ->setParameter('type', $type)
            ->setParameter('createdSince', $createdSince);

        $query = $qb->getQuery();
        $rows = $query->execute();

        $count = [];
        foreach ($rows as $row) {
            $count[$row['paymentStatus']] = $row[1];
        }

        return $count;
    }
}
