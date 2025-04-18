<?php

namespace App\Repositories\Doctrine;

use App\Entities\SenderName;
use App\Repositories\Interfaces\DoctrineRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * SenderName repository using Doctrine ORM
 * 
 * This repository provides methods to access and manipulate SenderName entities.
 */
class SenderNameRepository extends BaseRepository implements DoctrineRepositoryInterface
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
