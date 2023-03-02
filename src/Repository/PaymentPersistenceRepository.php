<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Repository;

use Dbp\Relay\MonoBundle\Entity\PaymentPersistence;
use Dbp\Relay\MonoBundle\Entity\PaymentStatus;
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

    public function countConcurrent(): int
    {
        $now = new \DateTime();
        $qb = $this->createQueryBuilder('p');
        $qb->select('count(p.identifier)')
            ->where('p.timeoutAt >= :timeoutAt')
            ->andWhere('p.completedAt IS NULL')
            ->setParameters([
                'timeoutAt' => $now,
            ]);

        $query = $qb->getQuery();

        $count = (int) $query->getSingleScalarResult();

        return $count;
    }

    public function countAuthConcurrent(string $userIdentifier = null): int
    {
        $now = new \DateTime();
        $qb = $this->createQueryBuilder('p')
            ->select('count(p.identifier)')
            ->where('p.timeoutAt >= :timeoutAt')
            ->andWhere('p.completedAt IS NULL')
            ->andWhere('p.userIdentifier IS NOT NULL');
        $parameters = [
            'timeoutAt' => $now,
        ];
        if ($userIdentifier !== null) {
            $qb->andWhere('p.userIdentifier = :userIdentifier');
            $parameters['userIdentifier'] = $userIdentifier;
        }
        $qb->setParameters($parameters);

        $query = $qb->getQuery();

        $count = (int) $query->getSingleScalarResult();

        return $count;
    }

    public function countUnauthConcurrent(string $clientIp = null): int
    {
        $now = new \DateTime();
        $qb = $this->createQueryBuilder('p')
            ->select('count(p.identifier)')
            ->where('p.timeoutAt >= :timeoutAt')
            ->andWhere('p.completedAt IS NULL')
            ->andWhere('p.userIdentifier IS NULL');
        $parameters = [
            'timeoutAt' => $now,
        ];
        if ($clientIp !== null) {
            $qb->andWhere('p.clientIp = :clientIp');
            $parameters['clientIp'] = $clientIp;
        }
        $qb->setParameters($parameters);

        $query = $qb->getQuery();

        $count = (int) $query->getSingleScalarResult();

        return $count;
    }

    /**
     * @return PaymentPersistence[]
     */
    public function findUnnotified(): array
    {
        $parameters = [
            'paymentStatus' => PaymentStatus::COMPLETED,
        ];

        $qb = $this->createQueryBuilder('p');
        $qb->where('p.paymentStatus = :paymentStatus')
            ->andWhere($qb->expr()->isNull('p.notifiedAt'))
            ->setParameters($parameters);

        $query = $qb->getQuery();
        $items = $query->getResult();

        return $items;
    }

    public function findUnnotifiedByTypeCompletedSince($type, \DateTime $completedSince)
    {
        $parameters = [
            'type' => $type,
            'paymentStatus' => PaymentStatus::COMPLETED,
            'completedSince' => $completedSince,
        ];

        $qb = $this->createQueryBuilder('p');
        $qb->where('p.type = :type')
            ->andWhere('p.paymentStatus = :paymentStatus')
            ->andWhere('p.completedAt >= :completedSince')
            ->andWhere($qb->expr()->isNull('p.notifiedAt'))
            ->setParameters($parameters);

        $query = $qb->getQuery();
        $items = $query->getResult();

        return $items;
    }

    /**
     * @return PaymentPersistence[]
     */
    public function findByPaymentStatusTimeoutBefore(string $paymentStatus, \DateTime $timeoutBefore): array
    {
        $parameters = [
            'paymentStatus' => $paymentStatus,
            'timeoutBefore' => $timeoutBefore,
        ];

        $qb = $this->createQueryBuilder('p');
        $qb->where('p.paymentStatus = :paymentStatus')
            ->andWhere('p.timeoutAt < :timeoutBefore')
            ->setParameters($parameters);

        $query = $qb->getQuery();
        $items = $query->getResult();

        return $items;
    }

    /**
     * @return int[]
     */
    public function countByTypeCreatedSince(string $type, \DateTime $createdSince): array
    {
        $parameters = [
            'type' => $type,
            'createdSince' => $createdSince,
        ];

        $qb = $this->createQueryBuilder('p');
        $qb->select('p.paymentStatus', 'count(p.identifier)')
            ->where('p.type = :type')
            ->andWhere('p.createdAt >= :createdSince')
            ->groupBy('p.paymentStatus')
            ->setParameters($parameters);

        $query = $qb->getQuery();
        $rows = $query->execute();

        $count = [];
        foreach ($rows as $row) {
            $count[$row['paymentStatus']] = $row[1];
        }

        return $count;
    }
}
