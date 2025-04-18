<?php

namespace App\Repositories\Interfaces;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Interface for Doctrine repositories
 * 
 * This interface defines the common methods that all Doctrine repositories should implement.
 */
interface DoctrineRepositoryInterface
{
    /**
     * Find an entity by its ID
     * 
     * @param int|string $id The entity ID
     * @return object|null The entity or null if not found
     */
    public function findById($id);

    /**
     * Find all entities
     * 
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The entities
     */
    public function findAll(?int $limit = null, ?int $offset = null): array;

    /**
     * Find entities by criteria
     * 
     * @param array $criteria The criteria
     * @param array|null $orderBy The order by criteria
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The entities
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /**
     * Find one entity by criteria
     * 
     * @param array $criteria The criteria
     * @return object|null The entity or null if not found
     */
    public function findOneBy(array $criteria);

    /**
     * Count entities
     * 
     * @param array $criteria Optional criteria to filter entities
     * @return int The number of entities
     */
    public function count(array $criteria = []): int;

    /**
     * Save an entity
     * 
     * @param object $entity The entity to save
     * @return object The saved entity
     */
    public function save($entity);

    /**
     * Delete an entity
     * 
     * @param object $entity The entity to delete
     * @return bool True if the entity was deleted
     */
    public function delete($entity): bool;

    /**
     * Delete an entity by its ID
     * 
     * @param int|string $id The entity ID
     * @return bool True if the entity was deleted, false if not found
     */
    public function deleteById($id): bool;

    /**
     * Begin a transaction
     * 
     * @return void
     */
    public function beginTransaction(): void;

    /**
     * Commit a transaction
     * 
     * @return void
     */
    public function commit(): void;

    /**
     * Rollback a transaction
     * 
     * @return void
     */
    public function rollback(): void;

    /**
     * Clear the entity manager
     * 
     * @return void
     */
    public function clear(): void;

    /**
     * Get the entity manager
     * 
     * @return EntityManagerInterface The entity manager
     */
    public function getEntityManager(): EntityManagerInterface;
}
