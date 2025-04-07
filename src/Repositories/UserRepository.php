<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use PDO;

/**
 * UserRepository
 * 
 * Repository for user data access operations.
 */
class UserRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Find a user by their ID.
     */
    public function findById(int $id): ?User
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? User::fromArray($row) : null;
    }

    /**
     * Find a user by their username.
     */
    public function findByUsername(string $username): ?User
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? User::fromArray($row) : null;
    }

    /**
     * Find a user by their email address.
     */
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? User::fromArray($row) : null;
    }

    /**
     * Find all users with pagination.
     */
    public function findAll(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('SELECT * FROM users ORDER BY username LIMIT :limit OFFSET :offset');
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $users = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = User::fromArray($row);
        }
        return $users;
    }

    /**
     * Count all users.
     */
    public function countAll(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM users');
        return (int) $stmt->fetchColumn();
    }

    /**
     * Save a user (insert or update).
     */
    public function save(User $user): User
    {
        if ($user->getId() === null) {
            // Insert new user
            $stmt = $this->db->prepare('
                INSERT INTO users (username, password, email, sms_credit, sms_limit, is_admin, created_at) 
                VALUES (:username, :password, :email, :sms_credit, :sms_limit, :is_admin, :created_at)
            ');
            // Bind common parameters
            $username = $user->getUsername();
            $password = $user->getPassword(); // Assumes already hashed
            $email = $user->getEmail();
            $smsCredit = $user->getSmsCredit();
            $smsLimit = $user->getSmsLimit();
            $isAdmin = $user->isAdmin() ? 1 : 0;
            $createdAt = $user->getCreatedAt() ?? date('Y-m-d H:i:s'); // Ensure created_at is set

            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':sms_credit', $smsCredit, PDO::PARAM_INT);
            $stmt->bindParam(':sms_limit', $smsLimit, PDO::PARAM_INT);
            $stmt->bindParam(':is_admin', $isAdmin, PDO::PARAM_INT);
            $stmt->bindParam(':created_at', $createdAt, PDO::PARAM_STR);

            $stmt->execute();
            $user->setId((int) $this->db->lastInsertId());
        } else {
            // Update existing user
            $stmt = $this->db->prepare('
                UPDATE users SET 
                    username = :username, 
                    password = :password, 
                    email = :email, 
                    sms_credit = :sms_credit, 
                    sms_limit = :sms_limit,
                    is_admin = :is_admin
                    -- updated_at is handled by MySQL ON UPDATE CURRENT_TIMESTAMP
                WHERE id = :id
            ');
            // Bind parameters
            $id = $user->getId();
            $username = $user->getUsername();
            $password = $user->getPassword(); // Assumes already hashed
            $email = $user->getEmail();
            $smsCredit = $user->getSmsCredit();
            $smsLimit = $user->getSmsLimit();
            $isAdmin = $user->isAdmin() ? 1 : 0;

            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':sms_credit', $smsCredit, PDO::PARAM_INT);
            $stmt->bindParam(':sms_limit', $smsLimit, PDO::PARAM_INT);
            $stmt->bindParam(':is_admin', $isAdmin, PDO::PARAM_INT);

            $stmt->execute();
        }
        // Re-fetch to get updated timestamps if needed, or rely on model state
        // return $this->findById($user->getId()); 
        return $user; // Return the user object with the ID set/updated
    }

    /**
     * Delete a user by their ID.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Update SMS credits for a user.
     * Consider using optimistic locking or transactions for concurrent updates.
     */
    public function updateSmsCredits(int $userId, int $newCreditBalance): bool
    {
        if ($newCreditBalance < 0) {
            throw new \InvalidArgumentException("SMS credit balance cannot be negative.");
        }
        $stmt = $this->db->prepare('UPDATE users SET sms_credit = :sms_credit WHERE id = :id');
        $stmt->bindParam(':sms_credit', $newCreditBalance, PDO::PARAM_INT);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Update SMS limit for a user.
     */
    public function updateSmsLimit(int $userId, ?int $newSmsLimit): bool
    {
        if ($newSmsLimit !== null && $newSmsLimit < 0) {
            throw new \InvalidArgumentException("SMS limit cannot be negative.");
        }
        $stmt = $this->db->prepare('UPDATE users SET sms_limit = :sms_limit WHERE id = :id');
        $stmt->bindParam(':sms_limit', $newSmsLimit, PDO::PARAM_INT);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
