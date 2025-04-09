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
     * Trouve des entités selon des critères spécifiques
     * 
     * @param array $criteria Critères de recherche
     * @param array|null $orderBy Critères de tri
     * @param int|null $limit Nombre maximum d'entités à retourner
     * @param int|null $offset Décalage pour la pagination
     * @return array
     */
    public function findBy(array $criteria = [], array $orderBy = null, int $limit = null, int $offset = null): array
    {
        $sql = 'SELECT * FROM sms_orders WHERE 1=1';
        $params = [];

        // Ajouter les critères de recherche
        foreach ($criteria as $field => $value) {
            $sql .= " AND $field = :$field";
            $params[":$field"] = $value;
        }

        // Ajouter les critères de tri
        if ($orderBy !== null && !empty($orderBy)) {
            $sql .= ' ORDER BY';
            $first = true;
            foreach ($orderBy as $field => $direction) {
                if (!$first) {
                    $sql .= ',';
                }
                $sql .= " $field $direction";
                $first = false;
            }
        } else {
            // Tri par défaut
            $sql .= ' ORDER BY created_at DESC';
        }

        // Ajouter la limite et l'offset
        if ($limit !== null) {
            $sql .= ' LIMIT :limit';
            if ($offset !== null) {
                $sql .= ' OFFSET :offset';
            }
        }

        $stmt = $this->db->prepare($sql);

        // Lier les paramètres des critères
        foreach ($params as $param => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($param, $value, $type);
        }

        // Lier les paramètres de limite et d'offset
        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            if ($offset !== null) {
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            }
        }

        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = SMSOrder::fromArray($row);
        }

        return $results;
    }

    /**
     * Trouve une entité par son identifiant
     * 
     * @param int $id Identifiant de l'entité
     * @return object|null
     */
    public function find(int $id): ?object
    {
        return $this->findById($id);
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
