<?php

namespace App\Repositories\Doctrine;

use App\Repositories\Interfaces\DoctrineRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Repository\RepositoryFactory;

/**
 * Custom repository factory for Doctrine ORM
 * 
 * This factory creates repository instances for entities.
 */
class CustomRepositoryFactory implements RepositoryFactory
{
    /**
     * @var array<string, EntityRepository> The list of repositories
     */
    private array $repositoryList = [];

    /**
     * Get the repository for an entity class
     * 
     * @param EntityManagerInterface $entityManager The entity manager
     * @param string $entityName The entity class name
     * @return EntityRepository The repository
     */
    public function getRepository(EntityManagerInterface $entityManager, string $entityName): EntityRepository
    {
        $repositoryHash = $entityManager->getClassMetadata($entityName)->getName() . spl_object_hash($entityManager);

        if (isset($this->repositoryList[$repositoryHash])) {
            return $this->repositoryList[$repositoryHash];
        }

        $metadata = $entityManager->getClassMetadata($entityName);
        $repositoryClassName = $metadata->customRepositoryClassName;

        if ($repositoryClassName === null) {
            // Use a default repository if no custom repository is specified
            $repository = new DefaultRepository($entityManager, $metadata);
        } else {
            // Create an instance of the custom repository
            $repository = new $repositoryClassName($entityManager, $metadata->getName());
        }

        $this->repositoryList[$repositoryHash] = $repository;

        return $repository;
    }
}

/**
 * Default repository class
 * 
 * This class is used when no custom repository is specified for an entity.
 * It extends EntityRepository to be compatible with Doctrine's repository factory.
 */
class DefaultRepository extends EntityRepository implements DoctrineRepositoryInterface
{
    /**
     * Constructor
     * 
     * @param EntityManagerInterface $entityManager The entity manager
     * @param \Doctrine\ORM\Mapping\ClassMetadata $classMetadata The class metadata
     */
    public function __construct(EntityManagerInterface $entityManager, $classMetadata)
    {
        parent::__construct($entityManager, $classMetadata);
    }

    /**
     * Find an entity by its ID
     * 
     * @param int|string $id The entity ID
     * @return object|null The entity or null if not found
     */
    public function findById($id)
    {
        return $this->find($id);
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
        if ($limit === null && $offset === null) {
            return parent::findAll();
        }

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
