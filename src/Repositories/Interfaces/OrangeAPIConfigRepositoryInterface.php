<?php

namespace App\Repositories\Interfaces;

use App\Entities\OrangeAPIConfig;

/**
 * Interface for OrangeAPIConfig repository
 */
interface OrangeAPIConfigRepositoryInterface extends DoctrineRepositoryInterface
{
    /**
     * Find the API configuration for a specific user.
     * 
     * @param int $userId The user ID
     * @return OrangeAPIConfig|null The API configuration or null if not found
     */
    public function findByUserId(int $userId): ?OrangeAPIConfig;

    /**
     * Find the global admin API configuration.
     * 
     * @return OrangeAPIConfig|null The admin API configuration or null if not found
     */
    public function findAdminConfig(): ?OrangeAPIConfig;

    /**
     * Find all user API configurations (excluding admin's).
     * 
     * @param int $limit Maximum number of configurations to return
     * @param int $offset Number of configurations to skip
     * @return array The API configurations
     */
    public function findAllUserConfigs(int $limit = 100, int $offset = 0): array;

    /**
     * Create a new API configuration.
     * 
     * @param int|null $userId The user ID
     * @param string $clientId The client ID
     * @param string $clientSecret The client secret
     * @param bool $isAdmin Whether this is an admin configuration
     * @return OrangeAPIConfig The created API configuration
     */
    public function create(?int $userId, string $clientId, string $clientSecret, bool $isAdmin = false): OrangeAPIConfig;

    /**
     * Delete the API configuration for a specific user.
     * 
     * @param int $userId The user ID
     * @return bool True if the API configuration was deleted
     */
    public function deleteByUserId(int $userId): bool;

    /**
     * Finds entities by a set of criteria.
     *
     * @param array      $criteria
     * @param array|null $orderBy
     * @param int|null   $limit
     * @param int|null   $offset
     *
     * @return array The objects.
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;
}
