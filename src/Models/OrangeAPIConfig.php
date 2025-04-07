<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Represents an Orange API Configuration entity.
 */
class OrangeAPIConfig
{
    private ?int $id;
    private ?int $userId; // Nullable for global admin config
    private string $clientId;
    private string $clientSecret; // Consider encrypting this in a real application
    private bool $isAdmin; // Flag to distinguish admin's own config
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        ?int $id = null,
        ?int $userId = null,
        string $clientId,
        string $clientSecret,
        bool $isAdmin = false,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->isAdmin = $isAdmin;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
        $this->updatedAt = $updatedAt;
    }

    // --- Getters ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getClientSecret(): string
    {
        // In a real app, you might decrypt here if stored encrypted
        return $this->clientSecret;
    }

    public function isAdminConfig(): bool
    {
        return $this->isAdmin;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    // --- Setters ---

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function setClientSecret(string $clientSecret): void
    {
        // In a real app, you might encrypt here before setting
        $this->clientSecret = $clientSecret;
    }

    public function setIsAdmin(bool $isAdmin): void
    {
        $this->isAdmin = $isAdmin;
    }

    /**
     * Create an OrangeAPIConfig object from a database row.
     */
    public static function fromArray(array $row): self
    {
        return new self(
            isset($row['id']) ? (int)$row['id'] : null,
            isset($row['user_id']) ? (int)$row['user_id'] : null,
            $row['client_id'],
            $row['client_secret'],
            isset($row['is_admin']) ? (bool)$row['is_admin'] : false,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
