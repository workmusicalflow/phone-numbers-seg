<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\SenderName;
use PDO;

/**
 * SenderNameRepository
 * 
 * Repository for sender name request data access operations.
 */
class SenderNameRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Find a sender name request by its ID.
     */
    public function findById(int $id): ?SenderName
    {
        $stmt = $this->db->prepare('SELECT * FROM sender_names WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? SenderName::fromArray($row) : null;
    }

    /**
     * Find all sender name requests for a specific user.
     */
    public function findByUserId(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM sender_names WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $senderNames = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $senderNames[] = SenderName::fromArray($row);
        }
        return $senderNames;
    }

    /**
     * Find all pending sender name requests.
     */
    public function findPending(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('SELECT * FROM sender_names WHERE status = :status ORDER BY created_at ASC LIMIT :limit OFFSET :offset');
        $status = SenderName::STATUS_PENDING;
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $senderNames = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $senderNames[] = SenderName::fromArray($row);
        }
        return $senderNames;
    }

    /**
     * Count all pending sender name requests.
     */
    public function countPending(): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM sender_names WHERE status = :status');
        $status = SenderName::STATUS_PENDING;
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Find all sender name requests (for admin).
     */
    public function findAll(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('SELECT * FROM sender_names ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $senderNames = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $senderNames[] = SenderName::fromArray($row);
        }
        return $senderNames;
    }

    /**
     * Count all sender name requests.
     */
    public function countAll(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM sender_names');
        return (int) $stmt->fetchColumn();
    }

    /**
     * Save a sender name request (insert or update).
     */
    public function save(SenderName $senderName): SenderName
    {
        if ($senderName->getId() === null) {
            // Insert new request
            $stmt = $this->db->prepare('
                INSERT INTO sender_names (user_id, name, status, created_at) 
                VALUES (:user_id, :name, :status, :created_at)
            ');
            $userId = $senderName->getUserId();
            $name = $senderName->getName();
            $status = $senderName->getStatus();
            $createdAt = $senderName->getCreatedAt() ?? date('Y-m-d H:i:s');

            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':created_at', $createdAt, PDO::PARAM_STR);

            $stmt->execute();
            $senderName->setId((int) $this->db->lastInsertId());
        } else {
            // Update existing request
            $stmt = $this->db->prepare('
                UPDATE sender_names SET 
                    user_id = :user_id, 
                    name = :name, 
                    status = :status
                    -- updated_at is handled by MySQL ON UPDATE CURRENT_TIMESTAMP
                WHERE id = :id
            ');
            $id = $senderName->getId();
            $userId = $senderName->getUserId();
            $name = $senderName->getName();
            $status = $senderName->getStatus();

            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);

            $stmt->execute();
        }
        return $senderName;
    }

    /**
     * Update the status of a sender name request.
     */
    public function updateStatus(int $id, string $newStatus): bool
    {
        if (!in_array($newStatus, [SenderName::STATUS_PENDING, SenderName::STATUS_APPROVED, SenderName::STATUS_REJECTED])) {
            throw new \InvalidArgumentException("Invalid status provided.");
        }
        $stmt = $this->db->prepare('UPDATE sender_names SET status = :status WHERE id = :id');
        $stmt->bindParam(':status', $newStatus, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Delete a sender name request by its ID.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM sender_names WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Count approved sender names for a user.
     */
    public function countApprovedByUserId(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM sender_names WHERE user_id = :user_id AND status = :status');
        $status = SenderName::STATUS_APPROVED;
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Find a sender name by its name.
     */
    public function findByName(string $name): ?SenderName
    {
        $stmt = $this->db->prepare('SELECT * FROM sender_names WHERE name = :name');
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? SenderName::fromArray($row) : null;
    }
}
