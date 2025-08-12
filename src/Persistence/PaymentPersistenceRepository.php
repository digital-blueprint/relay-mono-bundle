<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Persistence;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<PaymentPersistence>
 */
class PaymentPersistenceRepository extends EntityRepository
{
    public function findOne(string $identifier): ?PaymentPersistence
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.identifier = :identifier')
            ->setParameter('identifier', $identifier);

        $query = $qb->getQuery();

        $query->execute();

        return $query->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function countConcurrent(string $type): int
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $qb = $this->createQueryBuilder('p');
        $qb->select('count(p.identifier)')
            ->where('p.type = :type')
            ->andWhere('p.timeoutAt >= :timeoutAt')
            ->andWhere('p.completedAt IS NULL')
            ->setParameter('type', $type)
            ->setParameter('timeoutAt', $now, Types::DATETIME_IMMUTABLE);

        $query = $qb->getQuery();

        $count = (int) $query->getSingleScalarResult();

        return $count;
    }

    public function countAuthConcurrent(string $type, ?string $userIdentifier = null): int
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $qb = $this->createQueryBuilder('p')
            ->select('count(p.identifier)')
            ->where('p.type = :type')
            ->andWhere('p.timeoutAt >= :timeoutAt')
            ->andWhere('p.completedAt IS NULL')
            ->andWhere('p.userIdentifier IS NOT NULL')
            ->setParameter('type', $type)
            ->setParameter('timeoutAt', $now, Types::DATETIME_IMMUTABLE);

        if ($userIdentifier !== null) {
            $qb->andWhere('p.userIdentifier = :userIdentifier');
            $qb->setParameter('userIdentifier', $userIdentifier);
        }

        $query = $qb->getQuery();

        $count = (int) $query->getSingleScalarResult();

        return $count;
    }

    public function countUnauthConcurrent(string $type, ?string $clientIp = null): int
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $qb = $this->createQueryBuilder('p')
            ->select('count(p.identifier)')
            ->where('p.type = :type')
            ->andWhere('p.timeoutAt >= :timeoutAt')
            ->andWhere('p.completedAt IS NULL')
            ->andWhere('p.userIdentifier IS NULL')
            ->setParameter('type', $type)
            ->setParameter('timeoutAt', $now, Types::DATETIME_IMMUTABLE);

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

    /**
     * @return PaymentPersistence[]
     */
    public function findUnnotifiedByTypeCompletedSince(string $type, \DateTimeInterface $completedSince): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.type = :type')
            ->andWhere('p.paymentStatus = :paymentStatus')
            ->andWhere('p.completedAt >= :completedSince')
            ->andWhere($qb->expr()->isNull('p.notifiedAt'))
            ->setParameter('type', $type)
            ->setParameter('paymentStatus', PaymentStatus::COMPLETED)
            ->setParameter('completedSince', \DateTimeImmutable::createFromInterface($completedSince), Types::DATETIME_IMMUTABLE);

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
            ->setParameter('timeoutBefore', \DateTimeImmutable::createFromInterface($timeoutBefore), Types::DATETIME_IMMUTABLE);

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
            ->setParameter('createdSince', \DateTimeImmutable::createFromInterface($createdSince), Types::DATETIME_IMMUTABLE);

        $query = $qb->getQuery();
        $rows = $query->execute();

        $count = [];
        foreach ($rows as $row) {
            $count[$row['paymentStatus']] = $row[1];
        }

        return $count;
    }
}
