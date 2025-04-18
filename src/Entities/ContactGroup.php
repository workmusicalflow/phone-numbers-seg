<?php

namespace App\Entities;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\Table;

/**
 * ContactGroup entity
 * 
 * This entity represents a contact group in the system.
 */
#[Entity(repositoryClass: "App\Repositories\Doctrine\ContactGroupRepository")]
#[Table(name: "contact_groups")]
class ContactGroup
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: "integer")]
    private ?int $id = null;

    #[Column(name: "user_id", type: "integer")]
    private int $userId;

    #[Column(type: "string", length: 255)]
    private string $name;

    #[Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[Column(name: "created_at", type: "datetime")]
    private \DateTime $createdAt;

    #[Column(name: "updated_at", type: "datetime")]
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
     * Get the description
     * 
     * @return string|null The description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set the description
     * 
     * @param string|null $description The description
     * @return self
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;
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
            'name' => $this->name,
            'description' => $this->description,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s')
        ];
    }
}
