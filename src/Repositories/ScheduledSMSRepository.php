<?php

namespace App\Repositories;

use App\Models\ScheduledSMS;
use App\Repositories\Interfaces\RepositoryInterface;
use PDO;
use Exception;
use DateTime;

class ScheduledSMSRepository implements RepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getEntityClassName(): string
    {
        return ScheduledSMS::class;
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        $this->pdo->rollBack();
    }

    public function findAll(?int $limit = null, ?int $offset = null): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM scheduled_sms
            ORDER BY scheduled_date DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $scheduledSMSs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $scheduledSMSs[] = ScheduledSMS::fromPDO($this->pdo, $row);
        }

        return $scheduledSMSs;
    }

    public function findById(int $id): ?ScheduledSMS
    {
        $stmt = $this->pdo->prepare("SELECT * FROM scheduled_sms WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return ScheduledSMS::fromPDO($this->pdo, $row);
    }

    public function findByUserId(int $userId, ?int $limit = 100, ?int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM scheduled_sms
            WHERE user_id = :user_id
            ORDER BY scheduled_date DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $scheduledSMSs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $scheduledSMSs[] = ScheduledSMS::fromPDO($this->pdo, $row);
        }

        return $scheduledSMSs;
    }

    public function findDueSMS(?int $limit = 100): array
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare("
            SELECT * FROM scheduled_sms
            WHERE status = 'pending'
            AND next_run_at <= :now
            ORDER BY next_run_at ASC
            LIMIT :limit
        ");
        $stmt->bindParam(':now', $now, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $scheduledSMSs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $scheduledSMSs[] = ScheduledSMS::fromPDO($this->pdo, $row);
        }

        return $scheduledSMSs;
    }

    public function search(string $query, ?array $fields = null, ?int $limit = null, ?int $offset = null): array
    {
        $searchTerm = "%$query%";

        // Déterminer les champs à rechercher
        $searchFields = $fields ?? ['name', 'message'];

        // Construire la clause WHERE pour la recherche
        $whereConditions = [];
        foreach ($searchFields as $field) {
            $whereConditions[] = "$field LIKE :search_term";
        }

        $whereClause = count($whereConditions) > 0
            ? "WHERE " . implode(" OR ", $whereConditions)
            : "";

        $limitClause = $limit !== null ? "LIMIT :limit" : "";
        $offsetClause = $offset !== null ? "OFFSET :offset" : "";

        $sql = "
            SELECT * FROM scheduled_sms
            $whereClause
            ORDER BY scheduled_date DESC
            $limitClause $offsetClause
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':search_term', $searchTerm, PDO::PARAM_STR);

        if ($limit !== null) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        }

        if ($offset !== null) {
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        }

        $stmt->execute();

        $scheduledSMSs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $scheduledSMSs[] = ScheduledSMS::fromPDO($this->pdo, $row);
        }

        return $scheduledSMSs;
    }

    public function searchByUserId(string $query, int $userId, int $limit = 100, int $offset = 0): array
    {
        $searchTerm = "%$query%";
        $stmt = $this->pdo->prepare("
            SELECT * FROM scheduled_sms
            WHERE user_id = :user_id
            AND (name LIKE :search_term OR message LIKE :search_term)
            ORDER BY scheduled_date DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':search_term', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $scheduledSMSs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $scheduledSMSs[] = ScheduledSMS::fromPDO($this->pdo, $row);
        }

        return $scheduledSMSs;
    }

    public function create(ScheduledSMS $scheduledSMS): ScheduledSMS
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO scheduled_sms (
                user_id, name, message, sender_name_id, scheduled_date, 
                status, is_recurring, recurrence_pattern, recurrence_config, 
                recipients_type, recipients_data, created_at, updated_at, 
                last_run_at, next_run_at
            ) VALUES (
                :user_id, :name, :message, :sender_name_id, :scheduled_date, 
                :status, :is_recurring, :recurrence_pattern, :recurrence_config, 
                :recipients_type, :recipients_data, :created_at, :updated_at, 
                :last_run_at, :next_run_at
            )
        ");

        $userId = $scheduledSMS->getUserId();
        $name = $scheduledSMS->getName();
        $message = $scheduledSMS->getMessage();
        $senderNameId = $scheduledSMS->getSenderNameId();
        $scheduledDate = $scheduledSMS->getScheduledDate();
        $status = $scheduledSMS->getStatus();
        $isRecurring = $scheduledSMS->isRecurring() ? 1 : 0;
        $recurrencePattern = $scheduledSMS->getRecurrencePattern();
        $recurrenceConfig = $scheduledSMS->getRecurrenceConfig();
        $recipientsType = $scheduledSMS->getRecipientsType();
        $recipientsData = $scheduledSMS->getRecipientsData();
        $createdAt = $scheduledSMS->getCreatedAt();
        $updatedAt = $scheduledSMS->getUpdatedAt();
        $lastRunAt = $scheduledSMS->getLastRunAt();
        $nextRunAt = $scheduledSMS->getNextRunAt();

        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);
        $stmt->bindParam(':sender_name_id', $senderNameId, PDO::PARAM_INT);
        $stmt->bindParam(':scheduled_date', $scheduledDate, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':is_recurring', $isRecurring, PDO::PARAM_INT);
        $stmt->bindParam(':recurrence_pattern', $recurrencePattern, PDO::PARAM_STR);
        $stmt->bindParam(':recurrence_config', $recurrenceConfig, PDO::PARAM_STR);
        $stmt->bindParam(':recipients_type', $recipientsType, PDO::PARAM_STR);
        $stmt->bindParam(':recipients_data', $recipientsData, PDO::PARAM_STR);
        $stmt->bindParam(':created_at', $createdAt, PDO::PARAM_STR);
        $stmt->bindParam(':updated_at', $updatedAt, PDO::PARAM_STR);
        $stmt->bindParam(':last_run_at', $lastRunAt, PDO::PARAM_STR);
        $stmt->bindParam(':next_run_at', $nextRunAt, PDO::PARAM_STR);

        $stmt->execute();
        $id = (int)$this->pdo->lastInsertId();

        return $this->findById($id);
    }

    public function update(ScheduledSMS $scheduledSMS): ScheduledSMS
    {
        $stmt = $this->pdo->prepare("
            UPDATE scheduled_sms SET
                name = :name,
                message = :message,
                sender_name_id = :sender_name_id,
                scheduled_date = :scheduled_date,
                status = :status,
                is_recurring = :is_recurring,
                recurrence_pattern = :recurrence_pattern,
                recurrence_config = :recurrence_config,
                recipients_type = :recipients_type,
                recipients_data = :recipients_data,
                updated_at = :updated_at,
                last_run_at = :last_run_at,
                next_run_at = :next_run_at
            WHERE id = :id
        ");

        $id = $scheduledSMS->getId();
        $name = $scheduledSMS->getName();
        $message = $scheduledSMS->getMessage();
        $senderNameId = $scheduledSMS->getSenderNameId();
        $scheduledDate = $scheduledSMS->getScheduledDate();
        $status = $scheduledSMS->getStatus();
        $isRecurring = $scheduledSMS->isRecurring() ? 1 : 0;
        $recurrencePattern = $scheduledSMS->getRecurrencePattern();
        $recurrenceConfig = $scheduledSMS->getRecurrenceConfig();
        $recipientsType = $scheduledSMS->getRecipientsType();
        $recipientsData = $scheduledSMS->getRecipientsData();
        $updatedAt = date('Y-m-d H:i:s');
        $lastRunAt = $scheduledSMS->getLastRunAt();
        $nextRunAt = $scheduledSMS->getNextRunAt();

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);
        $stmt->bindParam(':sender_name_id', $senderNameId, PDO::PARAM_INT);
        $stmt->bindParam(':scheduled_date', $scheduledDate, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':is_recurring', $isRecurring, PDO::PARAM_INT);
        $stmt->bindParam(':recurrence_pattern', $recurrencePattern, PDO::PARAM_STR);
        $stmt->bindParam(':recurrence_config', $recurrenceConfig, PDO::PARAM_STR);
        $stmt->bindParam(':recipients_type', $recipientsType, PDO::PARAM_STR);
        $stmt->bindParam(':recipients_data', $recipientsData, PDO::PARAM_STR);
        $stmt->bindParam(':updated_at', $updatedAt, PDO::PARAM_STR);
        $stmt->bindParam(':last_run_at', $lastRunAt, PDO::PARAM_STR);
        $stmt->bindParam(':next_run_at', $nextRunAt, PDO::PARAM_STR);

        $stmt->execute();

        return $this->findById($id);
    }

    public function save($entity)
    {
        if ($entity instanceof ScheduledSMS) {
            if ($entity->getId() === 0) {
                return $this->create($entity);
            } else {
                return $this->update($entity);
            }
        }

        throw new Exception("Entity must be an instance of ScheduledSMS");
    }

    public function saveMany(array $entities): array
    {
        $savedEntities = [];

        $this->beginTransaction();

        try {
            foreach ($entities as $entity) {
                $savedEntities[] = $this->save($entity);
            }

            $this->commit();
            return $savedEntities;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function deleteById(int $id): bool
    {
        try {
            // Supprimer d'abord les logs associés
            $stmt = $this->pdo->prepare("DELETE FROM scheduled_sms_logs WHERE scheduled_sms_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            // Puis supprimer le SMS planifié
            $stmt = $this->pdo->prepare("DELETE FROM scheduled_sms WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    public function delete($entity): bool
    {
        if ($entity instanceof ScheduledSMS) {
            return $this->deleteById($entity->getId());
        } elseif (is_numeric($entity)) {
            return $this->deleteById((int)$entity);
        }

        throw new Exception("Entity must be an instance of ScheduledSMS or an ID");
    }

    public function deleteMany(array $entities): bool
    {
        $this->beginTransaction();

        try {
            $success = true;

            foreach ($entities as $entity) {
                $result = $this->delete($entity);
                if (!$result) {
                    $success = false;
                }
            }

            if ($success) {
                $this->commit();
            } else {
                $this->rollback();
            }

            return $success;
        } catch (Exception $e) {
            $this->rollback();
            return false;
        }
    }

    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        $conditions = [];
        $params = [];

        foreach ($criteria as $field => $value) {
            $conditions[] = "$field = :$field";
            $params[":$field"] = $value;
        }

        $whereClause = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

        $orderByClause = "";
        if ($orderBy) {
            $orderParts = [];
            foreach ($orderBy as $field => $direction) {
                $orderParts[] = "$field $direction";
            }
            $orderByClause = "ORDER BY " . implode(", ", $orderParts);
        } else {
            $orderByClause = "ORDER BY scheduled_date DESC";
        }

        $limitClause = $limit !== null ? "LIMIT :limit" : "";
        $offsetClause = $offset !== null ? "OFFSET :offset" : "";

        $sql = "SELECT * FROM scheduled_sms $whereClause $orderByClause $limitClause $offsetClause";
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }

        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }

        if ($offset !== null) {
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }

        $stmt->execute();

        $scheduledSMSs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $scheduledSMSs[] = ScheduledSMS::fromPDO($this->pdo, $row);
        }

        return $scheduledSMSs;
    }

    public function findOneBy(array $criteria, ?array $orderBy = null)
    {
        $results = $this->findBy($criteria, $orderBy, 1, 0);

        return count($results) > 0 ? $results[0] : null;
    }

    public function count(int $userId = null): int
    {
        if ($userId) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM scheduled_sms WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        } else {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM scheduled_sms");
        }

        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function updateStatus(int $id, string $status): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE scheduled_sms 
                SET status = :status, updated_at = :updated_at
                WHERE id = :id
            ");

            $updatedAt = date('Y-m-d H:i:s');

            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':updated_at', $updatedAt, PDO::PARAM_STR);

            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    public function updateAfterExecution(int $id, ?string $nextRunAt = null): bool
    {
        try {
            $now = date('Y-m-d H:i:s');

            if ($nextRunAt) {
                // Mise à jour pour les SMS récurrents
                $stmt = $this->pdo->prepare("
                    UPDATE scheduled_sms 
                    SET last_run_at = :last_run_at, 
                        next_run_at = :next_run_at, 
                        updated_at = :updated_at
                    WHERE id = :id
                ");

                $stmt->bindParam(':next_run_at', $nextRunAt, PDO::PARAM_STR);
            } else {
                // Mise à jour pour les SMS non récurrents
                $stmt = $this->pdo->prepare("
                    UPDATE scheduled_sms 
                    SET last_run_at = :last_run_at, 
                        status = 'sent',
                        updated_at = :updated_at
                    WHERE id = :id
                ");
            }

            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':last_run_at', $now, PDO::PARAM_STR);
            $stmt->bindParam(':updated_at', $now, PDO::PARAM_STR);

            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
}
