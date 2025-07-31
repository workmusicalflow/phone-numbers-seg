<?php

namespace App\Entities;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;

/**
 * OrangeAPIConfig entity
 */
#[Entity]
#[Table(name: "orange_api_configs")]
class OrangeAPIConfig
{
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue]
    private ?int $id = null;

    #[Column(type: "integer", nullable: true)]
    private ?int $userId = null;

    #[Column(type: "string", length: 255)]
    private string $clientId;

    #[Column(type: "string", length: 255)]
    private string $clientSecret;

    #[Column(type: "boolean")]
    private bool $isAdmin = false;

    #[Column(type: "datetime")]
    private \DateTime $createdAt;

    #[Column(type: "datetime", nullable: true)]
    private ?\DateTime $updatedAt = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
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
     * @return int|null The user ID
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * Set the user ID
     * 
     * @param int|null $userId The user ID
     * @return self
     */
    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Get the client ID
     * 
     * @return string The client ID
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * Set the client ID
     * 
     * @param string $clientId The client ID
     * @return self
     */
    public function setClientId(string $clientId): self
    {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * Get the client secret
     * 
     * @return string The client secret
     */
    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    /**
     * Set the client secret
     * 
     * @param string $clientSecret The client secret
     * @return self
     */
    public function setClientSecret(string $clientSecret): self
    {
        $this->clientSecret = $clientSecret;
        return $this;
    }

    /**
     * Check if this is an admin configuration
     * 
     * @return bool True if this is an admin configuration
     */
    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    /**
     * Set whether this is an admin configuration
     * 
     * @param bool $isAdmin True if this is an admin configuration
     * @return self
     */
    public function setIsAdmin(bool $isAdmin): self
    {
        $this->isAdmin = $isAdmin;
        return $this;
    }

    /**
     * Get the creation date
     * 
     * @return \DateTime The creation date
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * Set the creation date
     * 
     * @param \DateTime $createdAt The creation date
     * @return self
     */
    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Get the update date
     * 
     * @return \DateTime|null The update date
     */
    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Set the update date
     * 
     * @param \DateTime|null $updatedAt The update date
     * @return self
     */
    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
