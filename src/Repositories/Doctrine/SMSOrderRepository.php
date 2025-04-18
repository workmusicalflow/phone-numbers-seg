<?php

namespace App\Repositories\Doctrine;

use App\Entities\SMSOrder;
use App\Repositories\Interfaces\SMSOrderRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * SMSOrder repository using Doctrine ORM
 * 
 * This repository provides methods to access and manipulate SMSOrder entities.
 */
class SMSOrderRepository extends BaseRepository implements SMSOrderRepositoryInterface
{
    /**
     * Constructor
     * 
     * @param EntityManagerInterface $entityManager The entity manager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, SMSOrder::class);
    }

    /**
     * Find an entity by its ID
     * 
     * @param mixed $id The entity ID
     * @param mixed $lockMode The lock mode
     * @param mixed $lockVersion The lock version
     * @return object|null The entity or null if not found
     */
    public function find($id, $lockMode = null, $lockVersion = null): ?object
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    /**
     * Find SMS orders by user ID
     * 
     * @param int $userId The user ID
     * @param int $limit Maximum number of entities to return
     * @param int $offset Number of entities to skip
     * @return array The SMS orders
     */
    public function findByUserId(int $userId, int $limit = 100, int $offset = 0): array
    {
        return $this->findBy(
            ['userId' => $userId],
            ['createdAt' => 'DESC'],
            $limit,
            $offset
        );
    }

    /**
     * Count SMS orders by user ID
     * 
     * @param int $userId The user ID
     * @return int The number of SMS orders
     */
    public function countByUserId(int $userId): int
    {
        return $this->count(['userId' => $userId]);
    }

    /**
     * Find SMS orders by status
     * 
     * @param string $status The status
     * @param int $limit Maximum number of entities to return
     * @param int $offset Number of entities to skip
     * @return array The SMS orders
     */
    public function findByStatus(string $status, int $limit = 100, int $offset = 0): array
    {
        if (!in_array($status, [SMSOrder::STATUS_PENDING, SMSOrder::STATUS_COMPLETED])) {
            throw new \InvalidArgumentException("Invalid status provided.");
        }

        return $this->findBy(
            ['status' => $status],
            ['createdAt' => 'ASC'],
            $limit,
            $offset
        );
    }

    /**
     * Count SMS orders by status
     * 
     * @param string $status The status
     * @return int The number of SMS orders
     */
    public function countByStatus(string $status): int
    {
        if (!in_array($status, [SMSOrder::STATUS_PENDING, SMSOrder::STATUS_COMPLETED])) {
            throw new \InvalidArgumentException("Invalid status provided.");
        }

        return $this->count(['status' => $status]);
    }

    /**
     * Update the status of an SMS order
     * 
     * @param int $id The order ID
     * @param string $newStatus The new status
     * @return bool True if the status was updated
     */
    public function updateStatus(int $id, string $newStatus): bool
    {
        if (!in_array($newStatus, [SMSOrder::STATUS_PENDING, SMSOrder::STATUS_COMPLETED])) {
            throw new \InvalidArgumentException("Invalid status provided.");
        }

        $order = $this->findById($id);

        if ($order === null) {
            return false;
        }

        $order->setStatus($newStatus);
        $this->save($order);

        return true;
    }

    /**
     * Create a new SMS order
     * 
     * @param int $userId The user ID
     * @param int $quantity The quantity
     * @param string $status The status
     * @return SMSOrder The created SMS order
     */
    public function create(int $userId, int $quantity, string $status = SMSOrder::STATUS_PENDING): SMSOrder
    {
        $order = new SMSOrder();
        $order->setUserId($userId);
        $order->setQuantity($quantity);
        $order->setStatus($status);

        return $this->save($order);
    }

    /**
     * Count all SMS orders
     * 
     * @return int The number of SMS orders
     */
    public function countAll(): int
    {
        return $this->count();
    }
}
