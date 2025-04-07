<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\SMSOrder;
use PDO;

/**
 * SMSOrderRepository
 * 
 * Repository for SMS order data access operations.
 */
class SMSOrderRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Find an SMS order by its ID.
     */
    public function findById(int $id): ?SMSOrder
    {
        $stmt = $this->db->prepare('SELECT * FROM sms_orders WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? SMSOrder::fromArray($row) : null;
    }

    /**
     * Find all SMS orders for a specific user.
     */
    public function findByUserId(int $userId, int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('SELECT * FROM sms_orders WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $orders = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $orders[] = SMSOrder::fromArray($row);
        }
        return $orders;
    }

    /**
     * Count all SMS orders for a specific user.
     */
    public function countByUserId(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM sms_orders WHERE user_id = :user_id');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Find all SMS orders by status.
     */
    public function findByStatus(string $status, int $limit = 100, int $offset = 0): array
    {
        if (!in_array($status, [SMSOrder::STATUS_PENDING, SMSOrder::STATUS_COMPLETED])) {
            throw new \InvalidArgumentException("Invalid status provided.");
        }
        $stmt = $this->db->prepare('SELECT * FROM sms_orders WHERE status = :status ORDER BY created_at ASC LIMIT :limit OFFSET :offset');
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $orders = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $orders[] = SMSOrder::fromArray($row);
        }
        return $orders;
    }

    /**
     * Count all SMS orders by status.
     */
    public function countByStatus(string $status): int
    {
        if (!in_array($status, [SMSOrder::STATUS_PENDING, SMSOrder::STATUS_COMPLETED])) {
            throw new \InvalidArgumentException("Invalid status provided.");
        }
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM sms_orders WHERE status = :status');
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Find all SMS orders (for admin).
     */
    public function findAll(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('SELECT * FROM sms_orders ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $orders = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $orders[] = SMSOrder::fromArray($row);
        }
        return $orders;
    }

    /**
     * Count all SMS orders.
     */
    public function countAll(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM sms_orders');
        return (int) $stmt->fetchColumn();
    }

    /**
     * Save an SMS order (insert or update).
     */
    public function save(SMSOrder $order): SMSOrder
    {
        if ($order->getId() === null) {
            // Insert new order
            $stmt = $this->db->prepare('
                INSERT INTO sms_orders (user_id, quantity, status, created_at) 
                VALUES (:user_id, :quantity, :status, :created_at)
            ');
            $userId = $order->getUserId();
            $quantity = $order->getQuantity();
            $status = $order->getStatus();
            $createdAt = $order->getCreatedAt() ?? date('Y-m-d H:i:s');

            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':created_at', $createdAt, PDO::PARAM_STR);

            $stmt->execute();
            $order->setId((int) $this->db->lastInsertId());
        } else {
            // Update existing order
            $stmt = $this->db->prepare('
                UPDATE sms_orders SET 
                    user_id = :user_id, 
                    quantity = :quantity, 
                    status = :status
                    -- updated_at is handled by MySQL ON UPDATE CURRENT_TIMESTAMP
                WHERE id = :id
            ');
            $id = $order->getId();
            $userId = $order->getUserId();
            $quantity = $order->getQuantity();
            $status = $order->getStatus();

            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);

            $stmt->execute();
        }
        return $order;
    }

    /**
     * Update the status of an SMS order.
     */
    public function updateStatus(int $id, string $newStatus): bool
    {
        if (!in_array($newStatus, [SMSOrder::STATUS_PENDING, SMSOrder::STATUS_COMPLETED])) {
            throw new \InvalidArgumentException("Invalid status provided.");
        }
        $stmt = $this->db->prepare('UPDATE sms_orders SET status = :status WHERE id = :id');
        $stmt->bindParam(':status', $newStatus, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Delete an SMS order by its ID.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM sms_orders WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
