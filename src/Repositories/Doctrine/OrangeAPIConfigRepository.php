<?php

namespace App\Repositories\Doctrine;

use App\Entities\OrangeAPIConfig;
use App\Repositories\Interfaces\OrangeAPIConfigRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * OrangeAPIConfig repository using Doctrine ORM
 * 
 * This repository provides methods to access and manipulate OrangeAPIConfig entities.
 */
class OrangeAPIConfigRepository extends BaseRepository implements OrangeAPIConfigRepositoryInterface
{
    /**
     * Constructor
     * 
     * @param EntityManagerInterface $entityManager The entity manager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, OrangeAPIConfig::class);
    }

    /**
     * Find the API configuration for a specific user.
     * 
     * @param int $userId The user ID
     * @return OrangeAPIConfig|null The API configuration or null if not found
     */
    public function findByUserId(int $userId): ?OrangeAPIConfig
    {
        return $this->findOneBy(['userId' => $userId, 'isAdmin' => false]);
    }

    /**
     * Find the global admin API configuration.
     * 
     * @return OrangeAPIConfig|null The admin API configuration or null if not found
     */
    public function findAdminConfig(): ?OrangeAPIConfig
    {
        return $this->findOneBy(['isAdmin' => true]);
    }

    /**
     * Find all user API configurations (excluding admin's).
     * 
     * @param int $limit Maximum number of configurations to return
     * @param int $offset Number of configurations to skip
     * @return array The API configurations
     */
    public function findAllUserConfigs(int $limit = 100, int $offset = 0): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('c')
            ->from($this->entityClass, 'c')
            ->where('c.isAdmin = :isAdmin')
            ->setParameter('isAdmin', false)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Create a new API configuration.
     * 
     * @param int|null $userId The user ID
     * @param string $clientId The client ID
     * @param string $clientSecret The client secret
     * @param bool $isAdmin Whether this is an admin configuration
     * @return OrangeAPIConfig The created API configuration
     */
    public function create(?int $userId, string $clientId, string $clientSecret, bool $isAdmin = false): OrangeAPIConfig
    {
        $config = new OrangeAPIConfig();
        $config->setUserId($userId);
        $config->setClientId($clientId);
        $config->setClientSecret($clientSecret);
        $config->setIsAdmin($isAdmin);

        return $this->save($config);
    }

    /**
     * Delete the API configuration for a specific user.
     * 
     * @param int $userId The user ID
     * @return bool True if the API configuration was deleted
     */
    public function deleteByUserId(int $userId): bool
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->delete($this->entityClass, 'c')
            ->where('c.userId = :userId')
            ->andWhere('c.isAdmin = :isAdmin')
            ->setParameter('userId', $userId)
            ->setParameter('isAdmin', false);

        $result = $queryBuilder->getQuery()->execute();

        return $result > 0;
    }
}
