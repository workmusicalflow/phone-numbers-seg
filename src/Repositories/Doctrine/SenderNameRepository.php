<?php

namespace App\Repositories\Doctrine;

use App\Entities\SenderName;
use App\Repositories\Interfaces\DoctrineRepositoryInterface;
use App\Repositories\Interfaces\SenderNameRepositoryInterface; // Added correct interface
use Doctrine\ORM\EntityManagerInterface;

/**
 * SenderName repository using Doctrine ORM
 * 
 * This repository provides methods to access and manipulate SenderName entities.
 */
class SenderNameRepository extends BaseRepository implements SenderNameRepositoryInterface // Changed to correct interface
{
    /**
     * Constructor
     * 
     * @param EntityManagerInterface $entityManager The entity manager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, SenderName::class);
    }

    /**
     * Find a SenderName entity by its primary key / identifier.
     * Overrides BaseRepository method to match SenderNameRepositoryInterface signature.
     *
     * @param mixed $id The identifier.
     * @return SenderName|null The entity instance or NULL if the entity can not be found.
     */
    public function findById(mixed $id): ?SenderName
    {
        // Call the parent find method which returns object|null, compatible with ?SenderName
        /** @var SenderName|null $result */
        $result = parent::findById($id);
        return $result;
    }

    /**
     * Find sender names by user ID
     * 
     * @param int $userId The user ID
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The sender names
     */
    public function findByUserId(int $userId, ?int $limit = null, ?int $offset = null): array
    {
        return $this->findBy(['userId' => $userId], ['createdAt' => 'DESC'], $limit, $offset);
    }

    /**
     * Find sender names by status
     * 
     * @param string $status The status
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The sender names
     */
    public function findByStatus(string $status, ?int $limit = null, ?int $offset = null): array
    {
        return $this->findBy(['status' => $status], ['createdAt' => 'DESC'], $limit, $offset);
    }

    /**
     * Count sender names by user ID
     * 
     * @param int $userId The user ID
     * @return int The number of sender names
     */
    public function countByUserId(int $userId): int
    {
        return $this->count(['userId' => $userId]);
    }

    /**
     * Count sender names by status
     * 
     * @param string $status The status
     * @return int The number of sender names
     */
    public function countByStatus(string $status): int
    {
        return $this->count(['status' => $status]);
    }

    /**
     * Approve a sender name
     * 
     * @param int $id The sender name ID
     * @return SenderName|null The approved sender name or null if not found
     */
    public function approve(int $id): ?SenderName
    {
        $senderName = $this->findById($id);

        if ($senderName === null) {
            return null;
        }

        $senderName->setStatus('approved');
        $this->save($senderName);

        return $senderName;
    }

    /**
     * Reject a sender name
     * 
     * @param int $id The sender name ID
     * @return SenderName|null The rejected sender name or null if not found
     */
    public function reject(int $id): ?SenderName
    {
        $senderName = $this->findById($id);

        if ($senderName === null) {
            return null;
        }

        $senderName->setStatus('rejected');
        $this->save($senderName);

        return $senderName;
    }
}
