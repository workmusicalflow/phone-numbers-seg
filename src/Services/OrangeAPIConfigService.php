<?php

namespace App\Services;

use App\Entities\OrangeAPIConfig;
use App\Entities\User;
use App\Repositories\Interfaces\OrangeAPIConfigRepositoryInterface; // Use specific interface

/**
 * Service for managing Orange API configurations
 */
class OrangeAPIConfigService
{
    /**
     * @var OrangeAPIConfigRepositoryInterface // Use specific interface
     */
    private OrangeAPIConfigRepositoryInterface $orangeAPIConfigRepository; // Use specific interface

    /**
     * Constructor
     * 
     * @param OrangeAPIConfigRepositoryInterface $orangeAPIConfigRepository // Use specific interface
     */
    public function __construct(OrangeAPIConfigRepositoryInterface $orangeAPIConfigRepository) // Use specific interface
    {
        $this->orangeAPIConfigRepository = $orangeAPIConfigRepository;
    }

    /**
     * Get the Orange API configuration for a user
     * 
     * @param int $userId
     * @return OrangeAPIConfig|null
     */
    public function getConfigForUser(int $userId): ?OrangeAPIConfig
    {
        $configs = $this->orangeAPIConfigRepository->findBy(['userId' => $userId]);
        return !empty($configs) ? $configs[0] : null;
    }

    /**
     * Get the admin Orange API configuration
     * 
     * @return OrangeAPIConfig|null
     */
    public function getAdminConfig(): ?OrangeAPIConfig
    {
        $configs = $this->orangeAPIConfigRepository->findBy(['isAdmin' => true]);
        return !empty($configs) ? $configs[0] : null;
    }

    /**
     * Create or update an Orange API configuration for a user
     * 
     * @param int $userId
     * @param string $clientId
     * @param string $clientSecret
     * @param User $currentUser
     * @return OrangeAPIConfig|null
     */
    public function createOrUpdateConfig(int $userId, string $clientId, string $clientSecret, User $currentUser): ?OrangeAPIConfig
    {
        // Only admin users can create or update configurations
        if (!$currentUser->isAdmin()) {
            return null;
        }

        // Get the existing configuration for the user
        $config = $this->getConfigForUser($userId);

        // If no configuration exists, create a new one
        if (!$config) {
            $config = new OrangeAPIConfig();
            $config->setUserId($userId);
        }

        // Update the configuration
        $config->setClientId($clientId);
        $config->setClientSecret($clientSecret);
        $config->setUpdatedAt(new \DateTime());

        // Save the configuration
        $this->orangeAPIConfigRepository->save($config);

        return $config;
    }

    /**
     * Create or update the admin Orange API configuration
     * 
     * @param string $clientId
     * @param string $clientSecret
     * @param User $currentUser
     * @return OrangeAPIConfig|null
     */
    public function createOrUpdateAdminConfig(string $clientId, string $clientSecret, User $currentUser): ?OrangeAPIConfig
    {
        // Only admin users can create or update configurations
        if (!$currentUser->isAdmin()) {
            return null;
        }

        // Get the existing admin configuration
        $config = $this->getAdminConfig();

        // If no admin configuration exists, create a new one
        if (!$config) {
            $config = new OrangeAPIConfig();
            $config->setIsAdmin(true);
        }

        // Update the configuration
        $config->setClientId($clientId);
        $config->setClientSecret($clientSecret);
        $config->setUpdatedAt(new \DateTime());

        // Save the configuration
        $this->orangeAPIConfigRepository->save($config);

        return $config;
    }

    /**
     * Delete an Orange API configuration
     * 
     * @param int $configId
     * @param User $currentUser
     * @return bool
     */
    public function deleteConfig(int $configId, User $currentUser): bool
    {
        // Only admin users can delete configurations
        if (!$currentUser->isAdmin()) {
            return false;
        }

        // Get the configuration
        $config = $this->orangeAPIConfigRepository->findById($configId);
        if (!$config) {
            return false;
        }

        // Delete the configuration
        $this->orangeAPIConfigRepository->delete($config);

        return true;
    }

    /**
     * Get the effective Orange API configuration for a user
     * 
     * This method returns the user's configuration if it exists,
     * otherwise it returns the admin configuration.
     * 
     * @param int $userId
     * @return OrangeAPIConfig|null
     */
    public function getEffectiveConfigForUser(int $userId): ?OrangeAPIConfig
    {
        // Try to get the user's configuration
        $config = $this->getConfigForUser($userId);

        // If the user doesn't have a configuration, use the admin configuration
        if (!$config) {
            $config = $this->getAdminConfig();
        }

        return $config;
    }
}
