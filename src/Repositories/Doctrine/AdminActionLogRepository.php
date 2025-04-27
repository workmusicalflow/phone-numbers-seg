<?php

namespace App\Repositories\Doctrine;

use App\Entities\AdminActionLog;
use App\Entities\User;
use App\Repositories\Interfaces\AdminActionLogRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Doctrine implementation of AdminActionLogRepositoryInterface
 */
class AdminActionLogRepository extends BaseRepository implements AdminActionLogRepositoryInterface
{
    private EntityRepository $entityRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, AdminActionLog::class);
        $this->entityRepository = $entityManager->getRepository(AdminActionLog::class);
    }

    /**
     * @inheritDoc
     */
    public function log(
        User $admin,
        string $actionType,
        ?int $targetId = null,
        ?string $targetType = null,
        array $details = []
    ): AdminActionLog {
        $log = new AdminActionLog();
        $log->setAdmin($admin);
        $log->setActionType($actionType);
        $log->setTargetId($targetId);
        $log->setTargetType($targetType);
        $log->setDetails($details);

        $this->getEntityManager()->persist($log);
        $this->getEntityManager()->flush();

        return $log;
    }

    /**
     * @inheritDoc
     */
    public function getRecentLogs(int $limit = 100): array
    {
        return $this->entityRepository->createQueryBuilder('al')
            ->orderBy('al.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @inheritDoc
     */
    public function getLogsByAdmin(User $admin, int $limit = 100): array
    {
        return $this->entityRepository->createQueryBuilder('al')
            ->where('al.admin = :admin')
            ->setParameter('admin', $admin)
            ->orderBy('al.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @inheritDoc
     */
    public function getLogsByActionType(string $actionType, int $limit = 100): array
    {
        return $this->entityRepository->createQueryBuilder('al')
            ->where('al.actionType = :actionType')
            ->setParameter('actionType', $actionType)
            ->orderBy('al.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @inheritDoc
     */
    public function getLogsByTarget(int $targetId, ?string $targetType = null, int $limit = 100): array
    {
        $qb = $this->entityRepository->createQueryBuilder('al')
            ->where('al.targetId = :targetId')
            ->setParameter('targetId', $targetId)
            ->orderBy('al.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($targetType !== null) {
            $qb->andWhere('al.targetType = :targetType')
                ->setParameter('targetType', $targetType);
        }

        return $qb->getQuery()->getResult();
    }

    // Implement methods from ReadRepositoryInterface if needed, or rely on BaseRepository
    // For now, assuming BaseRepository provides basic read operations like findById, findAll, etc.

    /**
     * @inheritDoc
     */
    public function getEntityClassName(): string
    {
        return AdminActionLog::class;
    }

    /**
     * @inheritDoc
     */
    public function saveMany(array $entities): array
    {
        $entityManager = $this->getEntityManager();
        foreach ($entities as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();
        return $entities;
    }

    /**
     * @inheritDoc
     */
    public function deleteMany(array $entities): bool
    {
        $entityManager = $this->getEntityManager();
        foreach ($entities as $entity) {
            $entityManager->remove($entity);
        }
        $entityManager->flush();
        return true; // Assuming flush is successful if no exception is thrown
    }

    /**
     * @inheritDoc
     */
    public function search(string $query, ?array $fields = null, ?int $limit = null, ?int $offset = null): array
    {
        $qb = $this->entityRepository->createQueryBuilder('al');
        $orX = $qb->expr()->orX();

        if (empty($fields)) {
            // Default fields to search if none are provided
            $fields = ['actionType', 'targetType', 'details']; // Example fields, adjust as needed
        }

        foreach ($fields as $field) {
            // Add a LIKE condition for each field
            // Using JSON_EXTRACT for the 'details' field if it's JSON
            if ($field === 'details') {
                $orX->add($qb->expr()->like('JSON_EXTRACT(al.details, \'$\')', ':query'));
            } else {
                $orX->add($qb->expr()->like('al.' . $field, ':query'));
            }
        }

        $qb->where($orX)
            ->setParameter('query', '%' . $query . '%');

        // The SearchRepositoryInterface does not include an orderBy parameter,
        // so we will use a default order by createdAt DESC.
        $qb->orderBy('al.createdAt', 'DESC');


        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }
}
