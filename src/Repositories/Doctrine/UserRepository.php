<?php

namespace App\Repositories\Doctrine;

use App\Entities\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * User repository using Doctrine ORM
 * 
 * This repository provides methods to access and manipulate User entities.
 */
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * Constructor
     * 
     * @param EntityManagerInterface $entityManager The entity manager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, User::class);
    }

    /**
     * Find a user by their username
     * 
     * @param string $username The username
     * @return User|null The user or null if not found
     */
    public function findByUsername(string $username): ?User
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('u')
            ->from($this->entityClass, 'u')
            ->where('u.username = :username')
            ->setParameter('username', $username)
            ->setMaxResults(1);

        $result = $queryBuilder->getQuery()->getResult();
        return $result ? $result[0] : null;
    }

    /**
     * Find a user by their email
     * 
     * @param string $email The email
     * @return User|null The user or null if not found
     */
    public function findByEmail(string $email): ?User
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('u')
            ->from($this->entityClass, 'u')
            ->where('u.email = :email')
            ->setParameter('email', $email)
            ->setMaxResults(1);

        $result = $queryBuilder->getQuery()->getResult();
        return $result ? $result[0] : null;
    }

    /**
     * Find users by their IDs
     * 
     * @param array $ids The user IDs
     * @return array The users
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('u')
            ->from($this->entityClass, 'u')
            ->where('u.id IN (:ids)')
            ->setParameter('ids', $ids);

        $result = $queryBuilder->getQuery()->getResult();

        // Index by ID for easier access
        $users = [];
        foreach ($result as $user) {
            $users[$user->getId()] = $user;
        }

        return $users;
    }

    /**
     * Update SMS credits for a user
     * 
     * @param int $userId The user ID
     * @param int $newCreditBalance The new credit balance
     * @return bool True if the update was successful
     * @throws \InvalidArgumentException If the credit balance is negative
     */
    public function updateSmsCredits(int $userId, int $newCreditBalance): bool
    {
        if ($newCreditBalance < 0) {
            throw new \InvalidArgumentException("SMS credit balance cannot be negative.");
        }

        $user = $this->findById($userId);
        if ($user === null) {
            return false;
        }

        $user->setSmsCredit($newCreditBalance);
        $this->save($user);

        return true;
    }

    /**
     * Update SMS limit for a user
     * 
     * @param int $userId The user ID
     * @param int|null $newSmsLimit The new SMS limit
     * @return bool True if the update was successful
     * @throws \InvalidArgumentException If the SMS limit is negative
     */
    public function updateSmsLimit(int $userId, ?int $newSmsLimit): bool
    {
        if ($newSmsLimit !== null && $newSmsLimit < 0) {
            throw new \InvalidArgumentException("SMS limit cannot be negative.");
        }

        $user = $this->findById($userId);
        if ($user === null) {
            return false;
        }

        $user->setSmsLimit($newSmsLimit);
        $this->save($user);

        return true;
    }

    /**
     * Find a user by API key
     * 
     * @param string $apiKey The API key
     * @return User|null The user or null if not found
     */
    public function findByApiKey(string $apiKey): ?User
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('u')
            ->from($this->entityClass, 'u')
            ->where('u.apiKey = :apiKey')
            ->setParameter('apiKey', $apiKey)
            ->setMaxResults(1);

        $result = $queryBuilder->getQuery()->getResult();
        return $result ? $result[0] : null;
    }

    /**
     * Find a user by reset token
     * 
     * @param string $resetToken The reset token
     * @return User|null The user or null if not found
     */
    public function findByResetToken(string $resetToken): ?User
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('u')
            ->from($this->entityClass, 'u')
            ->where('u.resetToken = :resetToken')
            ->setParameter('resetToken', $resetToken)
            ->setMaxResults(1);

        $result = $queryBuilder->getQuery()->getResult();
        return $result ? $result[0] : null;
    }

    /**
     * Create a new user
     * 
     * @param string $username The username
     * @param string $email The email
     * @param string $password The password (will be hashed)
     * @param string $role The role (default: 'user')
     * @return User The created user
     */
    public function create(string $username, string $email, string $password, string $role = 'user'): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword(password_hash($password, PASSWORD_DEFAULT));

        // Set admin status based on role
        $isAdmin = ($role === 'admin');
        $user->setIsAdmin($isAdmin);

        // These are already set in the constructor, but we set them explicitly for clarity
        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(new \DateTime());

        return $this->save($user);
    }

    /**
     * Update user credits
     * 
     * @param int $userId The user ID
     * @param int $credits The new credit amount
     * @return bool True if the update was successful
     */
    public function updateCredits(int $userId, int $credits): bool
    {
        return $this->updateSmsCredits($userId, $credits);
    }
}
