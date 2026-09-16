<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Persistence;

use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<PaymentPersistence>
 */
class PaymentPersistenceRepository extends EntityRepository
{
    private const DATETIME_TYPE = 'relay_mono_datetime_immutable_utc';

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
            ->setParameter('timeoutAt', $now, self::DATETIME_TYPE);

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
            ->setParameter('timeoutAt', $now, self::DATETIME_TYPE);

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
            ->setParameter('timeoutAt', $now, self::DATETIME_TYPE);

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
    public function findUnnotifiedByTypeCompletedBefore(string $type, \DateTimeInterface $completedBefore): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.type = :type')
            ->andWhere('p.paymentStatus = :paymentStatus')
            ->andWhere('p.completedAt <= :completedBefore')
            ->andWhere($qb->expr()->isNull('p.notifiedAt'))
            ->orderBy('p.completedAt', 'ASC')
            ->setParameter('type', $type)
            ->setParameter('paymentStatus', PaymentStatus::COMPLETED)
            ->setParameter('completedBefore', \DateTimeImmutable::createFromInterface($completedBefore), self::DATETIME_TYPE);

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
            ->setParameter('timeoutBefore', \DateTimeImmutable::createFromInterface($timeoutBefore), self::DATETIME_TYPE);

        $query = $qb->getQuery();
        $items = $query->getResult();

        return $items;
    }

    /**
     * @return array<string, int>
     */
    public function countByTypeCreatedBetween(string $type, \DateTimeInterface $createdFrom, \DateTimeInterface $createdTo): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('p.paymentStatus', 'count(p.identifier)')
            ->where('p.type = :type')
            ->andWhere('p.createdAt >= :createdFrom')
            ->andWhere('p.createdAt < :createdTo')
            ->groupBy('p.paymentStatus')
            ->setParameter('type', $type)
            ->setParameter('createdFrom', \DateTimeImmutable::createFromInterface($createdFrom), self::DATETIME_TYPE)
            ->setParameter('createdTo', \DateTimeImmutable::createFromInterface($createdTo), self::DATETIME_TYPE);

        $query = $qb->getQuery();
        $rows = $query->execute();

        $count = [];
        foreach ($rows as $row) {
            $count[$row['paymentStatus']] = $row[1];
        }

        return $count;
    }

    public function countByTypeStartedBetween(string $type, \DateTimeInterface $startedFrom, \DateTimeInterface $startedTo): int
    {
        return $this->countByTypeDateBetween($type, 'startedAt', $startedFrom, $startedTo);
    }

    public function countByTypeCompletedBetween(string $type, \DateTimeInterface $completedFrom, \DateTimeInterface $completedTo): int
    {
        return $this->countByTypeDateBetween($type, 'completedAt', $completedFrom, $completedTo);
    }

    public function countByTypeNotifiedBetween(string $type, \DateTimeInterface $notifiedFrom, \DateTimeInterface $notifiedTo): int
    {
        return $this->countByTypeDateBetween($type, 'notifiedAt', $notifiedFrom, $notifiedTo);
    }

    /**
     * Counts sessions created in the period that were prepared, never started and have timed out
     * (i.e. their timeout has passed while still in the prepared state).
     */
    public function countTimedOutPreparedByTypeCreatedBetween(string $type, \DateTimeInterface $createdFrom, \DateTimeInterface $createdTo, \DateTimeInterface $now): int
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('count(p.identifier)')
            ->where('p.type = :type')
            ->andWhere('p.createdAt >= :createdFrom')
            ->andWhere('p.createdAt < :createdTo')
            ->andWhere('p.paymentStatus = :paymentStatus')
            ->andWhere('p.timeoutAt < :now')
            ->setParameter('type', $type)
            ->setParameter('createdFrom', \DateTimeImmutable::createFromInterface($createdFrom), self::DATETIME_TYPE)
            ->setParameter('createdTo', \DateTimeImmutable::createFromInterface($createdTo), self::DATETIME_TYPE)
            ->setParameter('paymentStatus', PaymentStatus::PREPARED)
            ->setParameter('now', \DateTimeImmutable::createFromInterface($now), self::DATETIME_TYPE);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countNotifiedCompletedByTypeCreatedBetween(string $type, \DateTimeInterface $createdFrom, \DateTimeInterface $createdTo): int
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('count(p.identifier)')
            ->where('p.type = :type')
            ->andWhere('p.createdAt >= :createdFrom')
            ->andWhere('p.createdAt < :createdTo')
            ->andWhere('p.paymentStatus = :paymentStatus')
            ->andWhere('p.notifiedAt IS NOT NULL')
            ->setParameter('type', $type)
            ->setParameter('createdFrom', \DateTimeImmutable::createFromInterface($createdFrom), self::DATETIME_TYPE)
            ->setParameter('createdTo', \DateTimeImmutable::createFromInterface($createdTo), self::DATETIME_TYPE)
            ->setParameter('paymentStatus', PaymentStatus::COMPLETED);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function countByTypeDateBetween(string $type, string $field, \DateTimeInterface $from, \DateTimeInterface $to): int
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('count(p.identifier)')
            ->where('p.type = :type')
            ->andWhere("p.$field >= :from")
            ->andWhere("p.$field < :to")
            ->setParameter('type', $type)
            ->setParameter('from', \DateTimeImmutable::createFromInterface($from), self::DATETIME_TYPE)
            ->setParameter('to', \DateTimeImmutable::createFromInterface($to), self::DATETIME_TYPE);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
