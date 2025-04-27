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
 * 
 * Note: This class extends Doctrine\ORM\EntityRepository to be compatible with
 * Doctrine's repository factory.
 */
abstract class BaseRepository extends EntityRepository implements DoctrineRepositoryInterface
{
    /**
     * Constructor
     * 
     * @param EntityManagerInterface $entityManager The entity manager
     * @param string $entityClass The entity class name
     */
    public function __construct(EntityManagerInterface $entityManager, string $entityClass)
    {
        $metadata = $entityManager->getClassMetadata($entityClass);
        parent::__construct($entityManager, $metadata);
    }

    /**
     * Find an entity by its ID
     * 
     * @param mixed $id The entity ID
     * @return object|null The entity or null if not found
     */
    public function findById(mixed $id): ?object // Added mixed param type and ?object return type
    {
        // We return the result of the parent find method, which aligns with ?object
        return parent::find($id);
    }

    /**
     * Find all entities with optional limit and offset
     * 
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The entities
     */
    public function findAll(?int $limit = null, ?int $offset = null): array
    {
        $queryBuilder = $this->createQueryBuilder('e');

        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        if ($offset !== null) {
            $queryBuilder->setFirstResult($offset);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Count entities
     * 
     * @param array $criteria Optional criteria to filter entities
     * @return int The number of entities
     */
    public function count(array $criteria = []): int
    {
        $queryBuilder = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)');

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
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

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
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();

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
        $entity = $this->find($id);

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
        $this->getEntityManager()->getConnection()->beginTransaction();
    }

    /**
     * Commit a transaction
     * 
     * @return void
     */
    public function commit(): void
    {
        $this->getEntityManager()->getConnection()->commit();
    }

    /**
     * Rollback a transaction
     * 
     * @return void
     */
    public function rollback(): void
    {
        $this->getEntityManager()->getConnection()->rollBack();
    }

    /**
     * Clear the entity manager
     * 
     * @return void
     */
    public function clear(): void
    {
        $this->getEntityManager()->clear();
    }
}
