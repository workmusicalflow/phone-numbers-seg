<?php

namespace App\Repositories;

use App\Models\ScheduledSMSLog;
use App\Repositories\Interfaces\RepositoryInterface;
use PDO;
use Exception;

class ScheduledSMSLogRepository implements RepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getEntityClassName(): string
    {
        return ScheduledSMSLog::class;
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
            SELECT * FROM scheduled_sms_logs
            ORDER BY execution_date DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $logs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $logs[] = ScheduledSMSLog::fromPDO($this->pdo, $row);
        }

        return $logs;
    }

    public function findById(int $id): ?ScheduledSMSLog
    {
        $stmt = $this->pdo->prepare("SELECT * FROM scheduled_sms_logs WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return ScheduledSMSLog::fromPDO($this->pdo, $row);
    }

    public function findByScheduledSmsId(int $scheduledSmsId, ?int $limit = 100, ?int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM scheduled_sms_logs
            WHERE scheduled_sms_id = :scheduled_sms_id
            ORDER BY execution_date DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':scheduled_sms_id', $scheduledSmsId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $logs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $logs[] = ScheduledSMSLog::fromPDO($this->pdo, $row);
        }

        return $logs;
    }

    public function create(ScheduledSMSLog $log): ScheduledSMSLog
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO scheduled_sms_logs (
                scheduled_sms_id, execution_date, status, 
                total_recipients, successful_sends, failed_sends, 
                error_details, created_at
            ) VALUES (
                :scheduled_sms_id, :execution_date, :status, 
                :total_recipients, :successful_sends, :failed_sends, 
                :error_details, :created_at
            )
        ");

        $scheduledSmsId = $log->getScheduledSmsId();
        $executionDate = $log->getExecutionDate();
        $status = $log->getStatus();
        $totalRecipients = $log->getTotalRecipients();
        $successfulSends = $log->getSuccessfulSends();
        $failedSends = $log->getFailedSends();
        $errorDetails = $log->getErrorDetails();
        $createdAt = $log->getCreatedAt();

        $stmt->bindParam(':scheduled_sms_id', $scheduledSmsId, PDO::PARAM_INT);
        $stmt->bindParam(':execution_date', $executionDate, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':total_recipients', $totalRecipients, PDO::PARAM_INT);
        $stmt->bindParam(':successful_sends', $successfulSends, PDO::PARAM_INT);
        $stmt->bindParam(':failed_sends', $failedSends, PDO::PARAM_INT);
        $stmt->bindParam(':error_details', $errorDetails, PDO::PARAM_STR);
        $stmt->bindParam(':created_at', $createdAt, PDO::PARAM_STR);

        $stmt->execute();
        $id = (int)$this->pdo->lastInsertId();

        return $this->findById($id);
    }

    public function save($entity)
    {
        if ($entity instanceof ScheduledSMSLog) {
            if ($entity->getId() === 0) {
                return $this->create($entity);
            }
        }

        throw new Exception("Entity must be an instance of ScheduledSMSLog");
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
            $stmt = $this->pdo->prepare("DELETE FROM scheduled_sms_logs WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    public function delete($entity): bool
    {
        if ($entity instanceof ScheduledSMSLog) {
            return $this->deleteById($entity->getId());
        } elseif (is_numeric($entity)) {
            return $this->deleteById((int)$entity);
        }

        throw new Exception("Entity must be an instance of ScheduledSMSLog or an ID");
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

    public function deleteByScheduledSmsId(int $scheduledSmsId): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM scheduled_sms_logs WHERE scheduled_sms_id = :scheduled_sms_id");
            $stmt->bindParam(':scheduled_sms_id', $scheduledSmsId, PDO::PARAM_INT);
            $stmt->execute();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function search(string $query, ?array $fields = null, ?int $limit = null, ?int $offset = null): array
    {
        $searchTerm = "%$query%";

        // Déterminer les champs à rechercher
        $searchFields = $fields ?? ['status', 'error_details'];

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
            SELECT * FROM scheduled_sms_logs
            $whereClause
            ORDER BY execution_date DESC
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

        $logs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $logs[] = ScheduledSMSLog::fromPDO($this->pdo, $row);
        }

        return $logs;
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
            $orderByClause = "ORDER BY execution_date DESC";
        }

        $limitClause = $limit !== null ? "LIMIT :limit" : "";
        $offsetClause = $offset !== null ? "OFFSET :offset" : "";

        $sql = "SELECT * FROM scheduled_sms_logs $whereClause $orderByClause $limitClause $offsetClause";
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

        $logs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $logs[] = ScheduledSMSLog::fromPDO($this->pdo, $row);
        }

        return $logs;
    }

    public function findOneBy(array $criteria, ?array $orderBy = null)
    {
        $results = $this->findBy($criteria, $orderBy, 1, 0);

        return count($results) > 0 ? $results[0] : null;
    }

    public function count(int $scheduledSmsId = null): int
    {
        if ($scheduledSmsId) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM scheduled_sms_logs WHERE scheduled_sms_id = :scheduled_sms_id");
            $stmt->bindParam(':scheduled_sms_id', $scheduledSmsId, PDO::PARAM_INT);
        } else {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM scheduled_sms_logs");
        }

        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function getSuccessRate(int $scheduledSmsId): float
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                SUM(successful_sends) as total_success,
                SUM(total_recipients) as total_recipients
            FROM scheduled_sms_logs
            WHERE scheduled_sms_id = :scheduled_sms_id
        ");
        $stmt->bindParam(':scheduled_sms_id', $scheduledSmsId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result || $result['total_recipients'] == 0) {
            return 0.0;
        }

        return ($result['total_success'] / $result['total_recipients']) * 100;
    }

    public function getLastExecutionDate(int $scheduledSmsId): ?string
    {
        $stmt = $this->pdo->prepare("
            SELECT execution_date
            FROM scheduled_sms_logs
            WHERE scheduled_sms_id = :scheduled_sms_id
            ORDER BY execution_date DESC
            LIMIT 1
        ");
        $stmt->bindParam(':scheduled_sms_id', $scheduledSmsId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result['execution_date'] : null;
    }
}
