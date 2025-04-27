<?php

namespace App\Repositories\Interfaces;

use App\Entities\User;

/**
 * Interface for User repository
 */
interface UserRepositoryInterface extends DoctrineRepositoryInterface
{
    /**
     * Find a user by email
     * 
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find a user by username
     * 
     * @param string $username
     * @return User|null
     */
    public function findByUsername(string $username): ?User;

    /**
     * Find a user by API key
     * 
     * @param string $apiKey
     * @return User|null
     */
    public function findByApiKey(string $apiKey): ?User;

    /**
     * Find a user by reset token
     * 
     * @param string $resetToken
     * @return User|null
     */
    public function findByResetToken(string $resetToken): ?User;

    /**
     * Create a new user
     * 
     * @param string $username
     * @param string $email
     * @param string $password
     * @param string $role
     * @return User
     */
    public function create(string $username, string $email, string $password, string $role = 'user'): User;

    /**
     * Update user credits
     * 
     * @param int $userId
     * @param int $credits
     * @return bool
     */
    public function updateCredits(int $userId, int $credits): bool;

    /**
     * Find users by multiple criteria
     * 
     * @param array $criteria Associative array of criteria (e.g., ['search' => 'admin'])
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The users
     */
    public function findByCriteria(array $criteria, ?int $limit = null, ?int $offset = 0): array;
}
