<?php

namespace App\Entities;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\Table;

/**
 * User entity
 * 
 * This entity represents a user in the system.
 */
#[Entity(repositoryClass: "App\Repositories\Doctrine\UserRepository")]
#[Table(name: "users")]
class User
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: "integer")]
    private ?int $id = null;

    #[Column(type: "string", length: 255, unique: true)]
    private string $username;

    #[Column(type: "string", length: 255)]
    private string $password;

    #[Column(type: "string", length: 255, nullable: true)]
    private ?string $email = null;

    #[Column(type: "integer")]
    private int $smsCredit = 10;

    #[Column(name: "sms_limit", type: "integer", nullable: true)]
    private ?int $smsLimit = null;

    #[Column(name: "is_admin", type: "boolean")]
    private bool $isAdmin = false;

    #[Column(name: "api_key", type: "string", length: 255, nullable: true)]
    private ?string $apiKey = null;

    #[Column(name: "reset_token", type: "string", length: 255, nullable: true)]
    private ?string $resetToken = null;

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
     * Get the username
     * 
     * @return string The username
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Set the username
     * 
     * @param string $username The username
     * @return self
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Get the password (hashed)
     * 
     * @return string The password
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Set the password (should be pre-hashed)
     * 
     * @param string $password The hashed password
     * @return self
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Get the email
     * 
     * @return string|null The email
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Set the email
     * 
     * @param string|null $email The email
     * @return self
     */
    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Get the SMS credit
     * 
     * @return int The SMS credit
     */
    public function getSmsCredit(): int
    {
        return $this->smsCredit;
    }

    /**
     * Set the SMS credit
     * 
     * @param int $smsCredit The SMS credit
     * @return self
     * @throws \InvalidArgumentException If SMS credit is negative
     */
    public function setSmsCredit(int $smsCredit): self
    {
        if ($smsCredit < 0) {
            throw new \InvalidArgumentException("SMS credit cannot be negative.");
        }
        $this->smsCredit = $smsCredit;
        return $this;
    }

    /**
     * Get the SMS limit
     * 
     * @return int|null The SMS limit
     */
    public function getSmsLimit(): ?int
    {
        return $this->smsLimit;
    }

    /**
     * Set the SMS limit
     * 
     * @param int|null $smsLimit The SMS limit
     * @return self
     * @throws \InvalidArgumentException If SMS limit is negative
     */
    public function setSmsLimit(?int $smsLimit): self
    {
        if ($smsLimit !== null && $smsLimit < 0) {
            throw new \InvalidArgumentException("SMS limit cannot be negative.");
        }
        $this->smsLimit = $smsLimit;
        return $this;
    }

    /**
     * Check if the user is an admin
     * 
     * @return bool True if the user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    /**
     * Set whether the user is an admin
     * 
     * @param bool $isAdmin Whether the user is an admin
     * @return self
     */
    public function setIsAdmin(bool $isAdmin): self
    {
        $this->isAdmin = $isAdmin;
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
     * Verify a given plain text password against the stored hash.
     * 
     * @param string $plainPassword The plain text password
     * @return bool True if the password is correct
     */
    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->password);
    }

    /**
     * Add SMS credits to the user's balance.
     * 
     * @param int $amount The amount to add
     * @return self
     * @throws \InvalidArgumentException If amount is not positive
     */
    public function addCredits(int $amount): self
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Amount to add must be positive.");
        }
        $this->smsCredit += $amount;
        return $this;
    }

    /**
     * Deduct SMS credits from the user's balance.
     * 
     * @param int $amount The amount to deduct
     * @return self
     * @throws \InvalidArgumentException If amount is not positive
     * @throws \RuntimeException If insufficient credits
     */
    public function deductCredits(int $amount): self
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Amount to deduct must be positive.");
        }
        if ($this->smsCredit < $amount) {
            throw new \RuntimeException("Insufficient SMS credits.");
        }
        $this->smsCredit -= $amount;
        return $this;
    }

    /**
     * Check if the user has enough credits for a given amount.
     * 
     * @param int $amount The amount to check
     * @return bool True if the user has enough credits
     */
    public function hasEnoughCredits(int $amount): bool
    {
        return $this->smsCredit >= $amount;
    }

    /**
     * Check if sending a certain amount would exceed the limit (if set).
     * 
     * @param int $amountToSend The amount to send
     * @return bool True if the limit would be exceeded
     */
    public function wouldExceedLimit(int $amountToSend): bool
    {
        if ($this->smsLimit === null) {
            return false; // No limit set
        }
        // This is a simplified check. Real limit checking needs usage tracking.
        return false; // Placeholder - actual limit logic needs more context
    }

    /**
     * Get the API key
     * 
     * @return string|null The API key
     */
    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    /**
     * Set the API key
     * 
     * @param string|null $apiKey The API key
     * @return self
     */
    public function setApiKey(?string $apiKey): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * Generate a new API key
     * 
     * @return self
     */
    public function generateApiKey(): self
    {
        $this->apiKey = bin2hex(random_bytes(16));
        return $this;
    }

    /**
     * Get the reset token
     * 
     * @return string|null The reset token
     */
    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    /**
     * Set the reset token
     * 
     * @param string|null $resetToken The reset token
     * @return self
     */
    public function setResetToken(?string $resetToken): self
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    /**
     * Generate a new reset token
     * 
     * @return self
     */
    public function generateResetToken(): self
    {
        $this->resetToken = bin2hex(random_bytes(16));
        return $this;
    }
}
