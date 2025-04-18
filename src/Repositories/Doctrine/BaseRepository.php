<?php

namespace App\Repositories\Doctrine;

use App\Repositories\Interfaces\DoctrineRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

/**
 * Base repository class for Doctrine ORM
 * 
 * This class provides common repository methods for all entity repositories.
 * It serves as a base class for all Doctrine repositories in the application.
 */
abstract class BaseRepository implements DoctrineRepositoryInterface
{
    /**
     * @var EntityManagerInterface The entity manager
     */
    protected EntityManagerInterface $entityManager;

    /**
     * @var string The entity class name
     */
    protected string $entityClass;

    /**
     * Constructor
     * 
     * @param EntityManagerInterface $entityManager The entity manager
     * @param string $entityClass The entity class name
     */
    public function __construct(EntityManagerInterface $entityManager, string $entityClass)
    {
        $this->entityManager = $entityManager;
        $this->entityClass = $entityClass;
    }

    /**
     * Find an entity by its ID
     * 
     * @param int|string $id The entity ID
     * @return object|null The entity or null if not found
     */
    public function findById($id)
    {
        return $this->entityManager->find($this->entityClass, $id);
    }

    /**
     * Find all entities
     * 
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The entities
     */
    public function findAll(?int $limit = null, ?int $offset = null): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('e')
            ->from($this->entityClass, 'e');

        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        if ($offset !== null) {
            $queryBuilder->setFirstResult($offset);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Find entities by criteria
     * 
     * @param array $criteria The criteria
     * @param array|null $orderBy The order by criteria
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The entities
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('e')
            ->from($this->entityClass, 'e');

        foreach ($criteria as $field => $value) {
            $queryBuilder->andWhere("e.$field = :$field")
                ->setParameter($field, $value);
        }

        if ($orderBy !== null) {
            foreach ($orderBy as $field => $direction) {
                $queryBuilder->addOrderBy("e.$field", $direction);
            }
        }

        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        if ($offset !== null) {
            $queryBuilder->setFirstResult($offset);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Find one entity by criteria
     * 
     * @param array $criteria The criteria
     * @return object|null The entity or null if not found
     */
    public function findOneBy(array $criteria)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('e')
            ->from($this->entityClass, 'e')
            ->setMaxResults(1);

        foreach ($criteria as $field => $value) {
            $queryBuilder->andWhere("e.$field = :$field")
                ->setParameter($field, $value);
        }

        $result = $queryBuilder->getQuery()->getResult();
        return $result ? $result[0] : null;
    }

    /**
     * Count entities
     * 
     * @param array $criteria Optional criteria to filter entities
     * @return int The number of entities
     */
    public function count(array $criteria = []): int
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('COUNT(e.id)')
            ->from($this->entityClass, 'e');

        foreach ($criteria as $field => $value) {
            $queryBuilder->andWhere("e.$field = :$field")
                ->setParameter($field, $value);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * Save an entity
     * 
     * @param object $entity The entity to save
     * @return object The saved entity
     */
    public function save($entity)
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $entity;
    }

    /**
     * Delete an entity
     * 
     * @param object $entity The entity to delete
     * @return bool True if the entity was deleted
     */
    public function delete($entity): bool
    {
        $this->entityManager->remove($entity);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Delete an entity by its ID
     * 
     * @param int|string $id The entity ID
     * @return bool True if the entity was deleted, false if not found
     */
    public function deleteById($id): bool
    {
        $entity = $this->findById($id);

        if ($entity === null) {
            return false;
        }

        return $this->delete($entity);
    }

    /**
     * Begin a transaction
     * 
     * @return void
     */
    public function beginTransaction(): void
    {
        $this->entityManager->getConnection()->beginTransaction();
    }

    /**
     * Commit a transaction
     * 
     * @return void
     */
    public function commit(): void
    {
        $this->entityManager->getConnection()->commit();
    }

    /**
     * Rollback a transaction
     * 
     * @return void
     */
    public function rollback(): void
    {
        $this->entityManager->getConnection()->rollBack();
    }

    /**
     * Clear the entity manager
     * 
     * @return void
     */
    public function clear(): void
    {
        $this->entityManager->clear();
    }

    /**
     * Get the entity manager
     * 
     * @return EntityManagerInterface The entity manager
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }
}
