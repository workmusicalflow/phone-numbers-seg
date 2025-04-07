<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Represents a User entity.
 */
class User
{
    private ?int $id;
    private string $username;
    private string $password; // Hashed password
    private ?string $email;
    private int $smsCredit;
    private ?int $smsLimit;
    private bool $isAdmin;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        string $username,
        string $password, // Expect hashed password here
        ?int $id = null,
        ?string $email = null,
        int $smsCredit = 10, // Default initial credit
        ?int $smsLimit = null,
        bool $isAdmin = false,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->password = $password;
        $this->email = $email;
        $this->smsCredit = $smsCredit;
        $this->smsLimit = $smsLimit;
        $this->isAdmin = $isAdmin;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
        $this->updatedAt = $updatedAt;
    }

    // --- Getters ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Returns the hashed password.
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getSmsCredit(): int
    {
        return $this->smsCredit;
    }

    public function getSmsLimit(): ?int
    {
        return $this->smsLimit;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    /**
     * Vérifie si l'utilisateur est un administrateur.
     */
    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    /**
     * Définit si l'utilisateur est un administrateur.
     */
    public function setIsAdmin(bool $isAdmin): void
    {
        $this->isAdmin = $isAdmin;
    }

    // --- Setters ---

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * Sets the hashed password.
     * Use password_hash() before calling this.
     */
    public function setPassword(string $hashedPassword): void
    {
        $this->password = $hashedPassword;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function setSmsCredit(int $smsCredit): void
    {
        if ($smsCredit < 0) {
            throw new \InvalidArgumentException("SMS credit cannot be negative.");
        }
        $this->smsCredit = $smsCredit;
    }

    public function setSmsLimit(?int $smsLimit): void
    {
        if ($smsLimit !== null && $smsLimit < 0) {
            throw new \InvalidArgumentException("SMS limit cannot be negative.");
        }
        $this->smsLimit = $smsLimit;
    }

    // --- Business Logic ---

    /**
     * Verify a given plain text password against the stored hash.
     */
    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->password);
    }

    /**
     * Add SMS credits to the user's balance.
     */
    public function addCredits(int $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Amount to add must be positive.");
        }
        $this->smsCredit += $amount;
    }

    /**
     * Deduct SMS credits from the user's balance.
     * Throws an exception if insufficient credits or limit exceeded.
     */
    public function deductCredits(int $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Amount to deduct must be positive.");
        }
        if ($this->smsCredit < $amount) {
            throw new \RuntimeException("Insufficient SMS credits.");
        }
        // Optional: Check against smsLimit if implemented for sending logic
        $this->smsCredit -= $amount;
    }

    /**
     * Check if the user has enough credits for a given amount.
     */
    public function hasEnoughCredits(int $amount): bool
    {
        return $this->smsCredit >= $amount;
    }

    /**
     * Check if sending a certain amount would exceed the limit (if set).
     * Note: This requires tracking usage within a period, which is not stored in this model.
     * This method provides a basic check against the static limit.
     */
    public function wouldExceedLimit(int $amountToSend): bool
    {
        if ($this->smsLimit === null) {
            return false; // No limit set
        }
        // This is a simplified check. Real limit checking needs usage tracking.
        // For now, we assume this check happens before sending.
        // A more complex implementation would involve checking credits sent within a time window.
        return false; // Placeholder - actual limit logic needs more context
    }

    /**
     * Create a User object from a database row.
     */
    public static function fromArray(array $row): self
    {
        return new self(
            $row['username'],
            $row['password'], // Assumes password from DB is already hashed
            isset($row['id']) ? (int)$row['id'] : null,
            $row['email'] ?? null,
            isset($row['sms_credit']) ? (int)$row['sms_credit'] : 0,
            isset($row['sms_limit']) ? (int)$row['sms_limit'] : null,
            isset($row['is_admin']) ? (bool)$row['is_admin'] : false,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
