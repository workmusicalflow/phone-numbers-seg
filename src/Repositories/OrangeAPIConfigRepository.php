<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\OrangeAPIConfig;
use PDO;

/**
 * OrangeAPIConfigRepository
 * 
 * Repository for Orange API configuration data access operations.
 */
class OrangeAPIConfigRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Find an API configuration by its ID.
     */
    public function findById(int $id): ?OrangeAPIConfig
    {
        $stmt = $this->db->prepare('SELECT * FROM orange_api_configs WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? OrangeAPIConfig::fromArray($row) : null;
    }

    /**
     * Find the API configuration for a specific user.
     */
    public function findByUserId(int $userId): ?OrangeAPIConfig
    {
        $stmt = $this->db->prepare('SELECT * FROM orange_api_configs WHERE user_id = :user_id AND is_admin = false');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? OrangeAPIConfig::fromArray($row) : null;
    }

    /**
     * Find the global admin API configuration.
     */
    public function findAdminConfig(): ?OrangeAPIConfig
    {
        // Assumes only one admin config exists where user_id is NULL or is_admin is true
        $stmt = $this->db->prepare('SELECT * FROM orange_api_configs WHERE is_admin = true LIMIT 1');
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? OrangeAPIConfig::fromArray($row) : null;
    }

    /**
     * Find all user API configurations (excluding admin's).
     */
    public function findAllUserConfigs(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('SELECT * FROM orange_api_configs WHERE is_admin = false ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $configs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $configs[] = OrangeAPIConfig::fromArray($row);
        }
        return $configs;
    }

    /**
     * Find all API configurations (including admin's).
     */
    public function findAll(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('SELECT * FROM orange_api_configs ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $configs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $configs[] = OrangeAPIConfig::fromArray($row);
        }
        return $configs;
    }

    /**
     * Save an API configuration (insert or update).
     */
    public function save(OrangeAPIConfig $config): OrangeAPIConfig
    {
        // Determine if it's an update or insert based on ID
        $existingConfig = null;
        if ($config->getId() !== null) {
            $existingConfig = $this->findById($config->getId());
        } elseif ($config->getUserId() !== null && !$config->isAdminConfig()) {
            // Check if a config already exists for this user
            $existingConfig = $this->findByUserId($config->getUserId());
            if ($existingConfig) $config->setId($existingConfig->getId());
        } elseif ($config->isAdminConfig()) {
            // Check if an admin config already exists
            $existingConfig = $this->findAdminConfig();
            if ($existingConfig) $config->setId($existingConfig->getId());
        }


        if ($config->getId() !== null && $existingConfig) {
            // Update existing config
            $stmt = $this->db->prepare('
                UPDATE orange_api_configs SET 
                    user_id = :user_id, 
                    client_id = :client_id, 
                    client_secret = :client_secret,
                    is_admin = :is_admin
                    -- updated_at is handled by MySQL ON UPDATE CURRENT_TIMESTAMP
                WHERE id = :id
            ');
            $id = $config->getId();
            $userId = $config->getUserId();
            $clientId = $config->getClientId();
            $clientSecret = $config->getClientSecret(); // Consider encryption before saving
            $isAdmin = $config->isAdminConfig();

            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT); // PDO handles null correctly
            $stmt->bindParam(':client_id', $clientId, PDO::PARAM_STR);
            $stmt->bindParam(':client_secret', $clientSecret, PDO::PARAM_STR);
            $stmt->bindParam(':is_admin', $isAdmin, PDO::PARAM_BOOL);

            $stmt->execute();
        } else {
            // Insert new config
            $stmt = $this->db->prepare('
                INSERT INTO orange_api_configs (user_id, client_id, client_secret, is_admin, created_at) 
                VALUES (:user_id, :client_id, :client_secret, :is_admin, :created_at)
            ');
            $userId = $config->getUserId();
            $clientId = $config->getClientId();
            $clientSecret = $config->getClientSecret(); // Consider encryption before saving
            $isAdmin = $config->isAdminConfig();
            $createdAt = $config->getCreatedAt() ?? date('Y-m-d H:i:s');

            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT); // PDO handles null correctly
            $stmt->bindParam(':client_id', $clientId, PDO::PARAM_STR);
            $stmt->bindParam(':client_secret', $clientSecret, PDO::PARAM_STR);
            $stmt->bindParam(':is_admin', $isAdmin, PDO::PARAM_BOOL);
            $stmt->bindParam(':created_at', $createdAt, PDO::PARAM_STR);

            $stmt->execute();
            $config->setId((int) $this->db->lastInsertId());
        }
        return $config;
    }

    /**
     * Delete an API configuration by its ID.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM orange_api_configs WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Delete the API configuration for a specific user.
     */
    public function deleteByUserId(int $userId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM orange_api_configs WHERE user_id = :user_id AND is_admin = false');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
