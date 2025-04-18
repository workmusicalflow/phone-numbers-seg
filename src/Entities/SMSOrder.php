<?php

namespace App\Entities;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 * SMSOrder entity
 * 
 * This entity represents an SMS order record in the system.
 */
#[Entity(repositoryClass: "App\Repositories\Doctrine\SMSOrderRepository")]
#[Table(name: "sms_orders")]
class SMSOrder
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';

    #[Id]
    #[GeneratedValue]
    #[Column(type: "integer")]
    private ?int $id = null;

    #[Column(name: "user_id", type: "integer")]
    private int $userId;

    #[Column(type: "integer")]
    private int $quantity;

    #[Column(type: "string", length: 50)]
    private string $status;

    #[Column(name: "created_at", type: "datetime")]
    private \DateTime $createdAt;

    #[Column(name: "updated_at", type: "datetime", nullable: true)]
    private ?\DateTime $updatedAt = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = self::STATUS_PENDING;
    }

    /**
     * Get the ID
     * 
     * @return int|null The ID
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the ID
     * 
     * @param int|null $id The ID
     * @return self
     */
    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get the user ID
     * 
     * @return int The user ID
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Set the user ID
     * 
     * @param int $userId The user ID
     * @return self
     */
    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Get the quantity
     * 
     * @return int The quantity
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * Set the quantity
     * 
     * @param int $quantity The quantity
     * @return self
     * @throws \InvalidArgumentException If quantity is not positive
     */
    public function setQuantity(int $quantity): self
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException("Order quantity must be positive.");
        }
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * Get the status
     * 
     * @return string The status
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Set the status
     * 
     * @param string $status The status
     * @return self
     * @throws \InvalidArgumentException If status is invalid
     */
    public function setStatus(string $status): self
    {
        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_COMPLETED])) {
            throw new \InvalidArgumentException("Invalid status provided for SMSOrder.");
        }
        $this->status = $status;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    /**
     * Get the created at date
     * 
     * @return \DateTime The created at date
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * Set the created at date
     * 
     * @param \DateTime $createdAt The created at date
     * @return self
     */
    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Get the updated at date
     * 
     * @return \DateTime|null The updated at date
     */
    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Set the updated at date
     * 
     * @param \DateTime|null $updatedAt The updated at date
     * @return self
     */
    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Check if the order is pending
     * 
     * @return bool True if the order is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the order is completed
     * 
     * @return bool True if the order is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Convert the entity to an array
     * 
     * @return array The entity as an array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'quantity' => $this->quantity,
            'status' => $this->status,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt ? $this->updatedAt->format('Y-m-d H:i:s') : null
        ];
    }
}
