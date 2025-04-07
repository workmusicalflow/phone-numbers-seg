<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Represents an SMS Order entity.
 */
class SMSOrder
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';

    private ?int $id;
    private int $userId;
    private int $quantity; // Number of SMS credits ordered
    private string $status;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        ?int $id = null,
        int $userId,
        int $quantity,
        string $status = self::STATUS_PENDING,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException("Order quantity must be positive.");
        }
        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_COMPLETED])) {
            throw new \InvalidArgumentException("Invalid status provided for SMSOrder.");
        }

        $this->id = $id;
        $this->userId = $userId;
        $this->quantity = $quantity;
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

    public function getQuantity(): int
    {
        return $this->quantity;
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

    public function setQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException("Order quantity must be positive.");
        }
        $this->quantity = $quantity;
    }

    public function setStatus(string $status): void
    {
        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_COMPLETED])) {
            throw new \InvalidArgumentException("Invalid status provided for SMSOrder.");
        }
        $this->status = $status;
    }

    // --- Status Checkers ---

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Create an SMSOrder object from a database row.
     */
    public static function fromArray(array $row): self
    {
        return new self(
            isset($row['id']) ? (int)$row['id'] : null,
            (int)$row['user_id'],
            (int)$row['quantity'],
            $row['status'] ?? self::STATUS_PENDING,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
