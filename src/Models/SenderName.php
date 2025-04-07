<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Represents a Sender Name request entity.
 */
class SenderName
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    private ?int $id;
    private int $userId;
    private string $name; // The requested sender name (max 11 chars)
    private string $status;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        ?int $id = null,
        int $userId,
        string $name,
        string $status = self::STATUS_PENDING,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        if (strlen($name) > 11) {
            throw new \InvalidArgumentException("Sender name cannot exceed 11 characters.");
        }
        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED])) {
            throw new \InvalidArgumentException("Invalid status provided for SenderName.");
        }

        $this->id = $id;
        $this->userId = $userId;
        $this->name = $name;
        $this->status = $status;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
        $this->updatedAt = $updatedAt;
    }

    // --- Getters ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStatus(): string
    {
        return $this->status;
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

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function setName(string $name): void
    {
        if (strlen($name) > 11) {
            throw new \InvalidArgumentException("Sender name cannot exceed 11 characters.");
        }
        $this->name = $name;
    }

    public function setStatus(string $status): void
    {
        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED])) {
            throw new \InvalidArgumentException("Invalid status provided for SenderName.");
        }
        $this->status = $status;
    }

    // --- Status Checkers ---

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Create a SenderName object from a database row.
     */
    public static function fromArray(array $row): self
    {
        return new self(
            isset($row['id']) ? (int)$row['id'] : null,
            (int)$row['user_id'],
            $row['name'],
            $row['status'] ?? self::STATUS_PENDING,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
