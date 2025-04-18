<?php

namespace App\Entities;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\Table;

/**
 * SenderName entity
 * 
 * This entity represents a sender name requested by a user.
 */
#[Entity(repositoryClass: "App\Repositories\Doctrine\SenderNameRepository")]
#[Table(name: "sender_names")]
class SenderName
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: "integer")]
    private int $id;

    #[Column(type: "integer")]
    private int $userId;

    #[Column(type: "string", length: 255)]
    private string $name;

    #[Column(type: "string", length: 20)]
    private string $status = 'pending';

    #[Column(type: "datetime")]
    private \DateTime $createdAt;

    #[Column(type: "datetime")]
    private \DateTime $updatedAt;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
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
     * Get the name
     * 
     * @return string The name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name
     * 
     * @param string $name The name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
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
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;
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
     * @return \DateTime The updated at date
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Set the updated at date
     * 
     * @param \DateTime $updatedAt The updated at date
     * @return self
     */
    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Update the updated at date
     * 
     * @return void
     */
    #[PreUpdate]
    public function updateUpdatedAt(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
