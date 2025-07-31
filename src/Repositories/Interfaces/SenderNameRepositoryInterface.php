<?php

namespace App\Repositories\Interfaces;

use App\Entities\SenderName;

/**
 * Interface for SenderName repository
 */
interface SenderNameRepositoryInterface extends DoctrineRepositoryInterface
{
    // Currently, inherits all necessary methods (findById, findBy, save, delete)
    // from DoctrineRepositoryInterface. Add specific methods here if needed in the future.

    /**
     * Find a SenderName entity by its primary key / identifier.
     *
     * @param mixed $id The identifier.
     * @return SenderName|null The entity instance or NULL if the entity can not be found.
     */
    public function findById(mixed $id): ?SenderName;

    /**
     * Finds entities by a set of criteria.
     *
     * @param array      $criteria
     * @param array|null $orderBy
     * @param int|null   $limit
     * @param int|null   $offset
     *
     * @return array<int, SenderName> The objects.
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /**
     * Saves a SenderName entity.
     *
     * @param object $entity The entity to save (must be an instance of SenderName).
     * @return object The saved entity.
     */
    public function save($entity); // REMOVED return type hint to match parent

    /**
     * Deletes a SenderName entity.
     *
     * @param object $entity The entity to delete (must be an instance of SenderName).
     * @return bool True if the entity was deleted.
     */
    public function delete($entity): bool; // Match parent signature (no param type hint)
}
