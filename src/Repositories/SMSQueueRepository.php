<?php

namespace App\Repositories;

use App\Entities\SMSQueue;
use App\Repositories\Interfaces\SMSQueueRepositoryInterface;
use PDO;
use Psr\Log\LoggerInterface;

/**
 * PDO implementation of SMSQueueRepositoryInterface
 */
class SMSQueueRepository implements SMSQueueRepositoryInterface
{
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param PDO $pdo
     * @param LoggerInterface|null $logger
     */
    public function __construct(PDO $pdo, ?LoggerInterface $logger = null)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function findById($id): ?SMSQueue
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM sms_queue WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return null;
            }

            return $this->createEntityFromRow($row);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error finding SMS queue entry by ID: ' . $e->getMessage(), [
                    'id' => $id,
                    'exception' => $e
                ]);
            }
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findByStatus(string $status, int $limit = 100, int $offset = 0): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM sms_queue WHERE status = :status ORDER BY priority DESC, next_attempt_at ASC, created_at ASC LIMIT :limit OFFSET :offset");
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $entries = [];
            
            foreach ($rows as $row) {
                $entries[] = $this->createEntityFromRow($row);
            }
            
            return $entries;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error finding SMS queue entries by status: ' . $e->getMessage(), [
                    'status' => $status,
                    'limit' => $limit,
                    'offset' => $offset,
                    'exception' => $e
                ]);
            }
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findByBatchId(string $batchId): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM sms_queue WHERE batch_id = :batch_id");
            $stmt->bindParam(':batch_id', $batchId, PDO::PARAM_STR);
            $stmt->execute();
            
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $entries = [];
            
            foreach ($rows as $row) {
                $entries[] = $this->createEntityFromRow($row);
            }
            
            return $entries;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error finding SMS queue entries by batch ID: ' . $e->getMessage(), [
                    'batchId' => $batchId,
                    'exception' => $e
                ]);
            }
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findNextBatch(int $limit = 50, array $statuses = [SMSQueue::STATUS_PENDING]): array
    {
        try {
            $now = date('Y-m-d H:i:s');
            $placeholders = implode(',', array_fill(0, count($statuses), '?'));
            
            $sql = "SELECT * FROM sms_queue WHERE status IN ($placeholders) AND (next_attempt_at IS NULL OR next_attempt_at <= ?) ORDER BY priority DESC, next_attempt_at ASC, created_at ASC LIMIT ?";
            $stmt = $this->pdo->prepare($sql);
            
            $i = 1;
            foreach ($statuses as $status) {
                $stmt->bindValue($i++, $status, PDO::PARAM_STR);
            }
            $stmt->bindValue($i++, $now, PDO::PARAM_STR);
            $stmt->bindValue($i, $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $entries = [];
            
            foreach ($rows as $row) {
                $entries[] = $this->createEntityFromRow($row);
            }
            
            return $entries;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error finding next batch of SMS queue entries: ' . $e->getMessage(), [
                    'limit' => $limit,
                    'statuses' => $statuses,
                    'exception' => $e
                ]);
            }
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findExpiredProcessing(\DateTime $threshold): array
    {
        try {
            $thresholdStr = $threshold->format('Y-m-d H:i:s');
            $status = SMSQueue::STATUS_PROCESSING;
            
            $stmt = $this->pdo->prepare("SELECT * FROM sms_queue WHERE status = :status AND last_attempt_at < :threshold");
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':threshold', $thresholdStr, PDO::PARAM_STR);
            $stmt->execute();
            
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $entries = [];
            
            foreach ($rows as $row) {
                $entries[] = $this->createEntityFromRow($row);
            }
            
            return $entries;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error finding expired processing SMS queue entries: ' . $e->getMessage(), [
                    'threshold' => $threshold->format('Y-m-d H:i:s'),
                    'exception' => $e
                ]);
            }
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findByUserId(int $userId, int $limit = 100, int $offset = 0): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM sms_queue WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $entries = [];
            
            foreach ($rows as $row) {
                $entries[] = $this->createEntityFromRow($row);
            }
            
            return $entries;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error finding SMS queue entries by user ID: ' . $e->getMessage(), [
                    'userId' => $userId,
                    'limit' => $limit,
                    'offset' => $offset,
                    'exception' => $e
                ]);
            }
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findBySegmentId(int $segmentId, int $limit = 100, int $offset = 0): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM sms_queue WHERE segment_id = :segment_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
            $stmt->bindParam(':segment_id', $segmentId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $entries = [];
            
            foreach ($rows as $row) {
                $entries[] = $this->createEntityFromRow($row);
            }
            
            return $entries;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error finding SMS queue entries by segment ID: ' . $e->getMessage(), [
                    'segmentId' => $segmentId,
                    'limit' => $limit,
                    'offset' => $offset,
                    'exception' => $e
                ]);
            }
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function countByStatus(string $status): int
    {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM sms_queue WHERE status = :status");
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->execute();
            
            return (int) $stmt->fetchColumn();
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error counting SMS queue entries by status: ' . $e->getMessage(), [
                    'status' => $status,
                    'exception' => $e
                ]);
            }
            return 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save(SMSQueue $smsQueue): SMSQueue
    {
        try {
            if ($smsQueue->getId() === null) {
                // Insert
                $sql = "INSERT INTO sms_queue (
                    phone_number, message, user_id, segment_id, status, created_at, 
                    last_attempt_at, next_attempt_at, attempts, priority, error_message, 
                    message_id, sender_name, sender_address, batch_id
                ) VALUES (
                    :phone_number, :message, :user_id, :segment_id, :status, :created_at, 
                    :last_attempt_at, :next_attempt_at, :attempts, :priority, :error_message, 
                    :message_id, :sender_name, :sender_address, :batch_id
                )";
                
                $stmt = $this->pdo->prepare($sql);
                $createdAt = $smsQueue->getCreatedAt()->format('Y-m-d H:i:s');
                $lastAttemptAt = $smsQueue->getLastAttemptAt() ? $smsQueue->getLastAttemptAt()->format('Y-m-d H:i:s') : null;
                $nextAttemptAt = $smsQueue->getNextAttemptAt() ? $smsQueue->getNextAttemptAt()->format('Y-m-d H:i:s') : null;
                
                $phoneNumber = $smsQueue->getPhoneNumber();
                $message = $smsQueue->getMessage();
                $userId = $smsQueue->getUserId();
                $segmentId = $smsQueue->getSegmentId();
                $status = $smsQueue->getStatus();
                $attempts = $smsQueue->getAttempts();
                $priority = $smsQueue->getPriority();
                $errorMessage = $smsQueue->getErrorMessage();
                $messageId = $smsQueue->getMessageId();
                $senderName = $smsQueue->getSenderName();
                $senderAddress = $smsQueue->getSenderAddress();
                $batchId = $smsQueue->getBatchId();
                
                $stmt->bindParam(':phone_number', $phoneNumber, PDO::PARAM_STR);
                $stmt->bindParam(':message', $message, PDO::PARAM_STR);
                $stmt->bindParam(':user_id', $userId, $userId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $stmt->bindParam(':segment_id', $segmentId, $segmentId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $stmt->bindParam(':status', $status, PDO::PARAM_STR);
                $stmt->bindParam(':created_at', $createdAt, PDO::PARAM_STR);
                $stmt->bindParam(':last_attempt_at', $lastAttemptAt, PDO::PARAM_STR);
                $stmt->bindParam(':next_attempt_at', $nextAttemptAt, PDO::PARAM_STR);
                $stmt->bindParam(':attempts', $attempts, PDO::PARAM_INT);
                $stmt->bindParam(':priority', $priority, PDO::PARAM_INT);
                $stmt->bindParam(':error_message', $errorMessage, PDO::PARAM_STR);
                $stmt->bindParam(':message_id', $messageId, PDO::PARAM_STR);
                $stmt->bindParam(':sender_name', $senderName, PDO::PARAM_STR);
                $stmt->bindParam(':sender_address', $senderAddress, PDO::PARAM_STR);
                $stmt->bindParam(':batch_id', $batchId, PDO::PARAM_STR);
                
                $stmt->execute();
                $id = $this->pdo->lastInsertId();
                $smsQueue->setId((int) $id);
            } else {
                // Update
                $sql = "UPDATE sms_queue SET
                    phone_number = :phone_number,
                    message = :message,
                    user_id = :user_id,
                    segment_id = :segment_id,
                    status = :status,
                    last_attempt_at = :last_attempt_at,
                    next_attempt_at = :next_attempt_at,
                    attempts = :attempts,
                    priority = :priority,
                    error_message = :error_message,
                    message_id = :message_id,
                    sender_name = :sender_name,
                    sender_address = :sender_address,
                    batch_id = :batch_id
                WHERE id = :id";
                
                $stmt = $this->pdo->prepare($sql);
                $lastAttemptAt = $smsQueue->getLastAttemptAt() ? $smsQueue->getLastAttemptAt()->format('Y-m-d H:i:s') : null;
                $nextAttemptAt = $smsQueue->getNextAttemptAt() ? $smsQueue->getNextAttemptAt()->format('Y-m-d H:i:s') : null;
                $id = $smsQueue->getId();
                
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->bindParam(':phone_number', $smsQueue->getPhoneNumber(), PDO::PARAM_STR);
                $stmt->bindParam(':message', $smsQueue->getMessage(), PDO::PARAM_STR);
                $stmt->bindParam(':user_id', $smsQueue->getUserId(), PDO::PARAM_INT);
                $stmt->bindParam(':segment_id', $smsQueue->getSegmentId(), PDO::PARAM_INT);
                $stmt->bindParam(':status', $smsQueue->getStatus(), PDO::PARAM_STR);
                $stmt->bindParam(':last_attempt_at', $lastAttemptAt, PDO::PARAM_STR);
                $stmt->bindParam(':next_attempt_at', $nextAttemptAt, PDO::PARAM_STR);
                $stmt->bindParam(':attempts', $smsQueue->getAttempts(), PDO::PARAM_INT);
                $stmt->bindParam(':priority', $smsQueue->getPriority(), PDO::PARAM_INT);
                $stmt->bindParam(':error_message', $smsQueue->getErrorMessage(), PDO::PARAM_STR);
                $stmt->bindParam(':message_id', $smsQueue->getMessageId(), PDO::PARAM_STR);
                $stmt->bindParam(':sender_name', $smsQueue->getSenderName(), PDO::PARAM_STR);
                $stmt->bindParam(':sender_address', $smsQueue->getSenderAddress(), PDO::PARAM_STR);
                $stmt->bindParam(':batch_id', $smsQueue->getBatchId(), PDO::PARAM_STR);
                
                $stmt->execute();
            }
            
            return $smsQueue;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error saving SMS queue entry: ' . $e->getMessage(), [
                    'smsQueueId' => $smsQueue->getId(),
                    'exception' => $e
                ]);
            }
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function saveBatch(array $smsQueues): bool
    {
        if (empty($smsQueues)) {
            return true;
        }

        try {
            $this->pdo->beginTransaction();
            
            foreach ($smsQueues as $smsQueue) {
                if (!$smsQueue instanceof SMSQueue) {
                    throw new \InvalidArgumentException('Array must contain only SMSQueue objects.');
                }
                $this->save($smsQueue);
            }
            
            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            
            if ($this->logger) {
                $this->logger->error('Error saving batch of SMS queue entries: ' . $e->getMessage(), [
                    'count' => count($smsQueues),
                    'exception' => $e
                ]);
            }
            
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateStatus(int $id, string $status, ?string $errorMessage = null): bool
    {
        try {
            $sql = "UPDATE sms_queue SET status = :status";
            $params = [
                ':id' => $id,
                ':status' => $status
            ];
            
            if ($errorMessage !== null) {
                $sql .= ", error_message = :error_message";
                $params[':error_message'] = $errorMessage;
            }
            
            $sql .= " WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $param => $value) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($param, $value, $type);
            }
            
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error updating SMS queue entry status: ' . $e->getMessage(), [
                    'id' => $id,
                    'status' => $status,
                    'exception' => $e
                ]);
            }
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function increaseAttemptCount(int $id, ?\DateTime $nextAttemptAt = null): bool
    {
        try {
            $sql = "UPDATE sms_queue SET 
                attempts = attempts + 1, 
                last_attempt_at = :now";
            
            $params = [
                ':id' => $id,
                ':now' => date('Y-m-d H:i:s')
            ];
            
            if ($nextAttemptAt !== null) {
                $sql .= ", next_attempt_at = :next_attempt_at";
                $params[':next_attempt_at'] = $nextAttemptAt->format('Y-m-d H:i:s');
            }
            
            $sql .= " WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $param => $value) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($param, $value, $type);
            }
            
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error increasing SMS queue entry attempt count: ' . $e->getMessage(), [
                    'id' => $id,
                    'exception' => $e
                ]);
            }
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cancelPendingByUserId(int $userId, ?string $reason = null): int
    {
        try {
            $status = SMSQueue::STATUS_CANCELLED;
            $pendingStatus = SMSQueue::STATUS_PENDING;
            
            $sql = "UPDATE sms_queue SET status = :status";
            $params = [
                ':status' => $status,
                ':user_id' => $userId,
                ':pending_status' => $pendingStatus
            ];
            
            if ($reason !== null) {
                $sql .= ", error_message = :reason";
                $params[':reason'] = $reason;
            }
            
            $sql .= " WHERE user_id = :user_id AND status = :pending_status";
            
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $param => $value) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($param, $value, $type);
            }
            
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error cancelling pending SMS queue entries by user ID: ' . $e->getMessage(), [
                    'userId' => $userId,
                    'exception' => $e
                ]);
            }
            return 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cancelPendingBySegmentId(int $segmentId, ?string $reason = null): int
    {
        try {
            $status = SMSQueue::STATUS_CANCELLED;
            $pendingStatus = SMSQueue::STATUS_PENDING;
            
            $sql = "UPDATE sms_queue SET status = :status";
            $params = [
                ':status' => $status,
                ':segment_id' => $segmentId,
                ':pending_status' => $pendingStatus
            ];
            
            if ($reason !== null) {
                $sql .= ", error_message = :reason";
                $params[':reason'] = $reason;
            }
            
            $sql .= " WHERE segment_id = :segment_id AND status = :pending_status";
            
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $param => $value) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($param, $value, $type);
            }
            
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error cancelling pending SMS queue entries by segment ID: ' . $e->getMessage(), [
                    'segmentId' => $segmentId,
                    'exception' => $e
                ]);
            }
            return 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cancelPendingByBatchId(string $batchId, ?string $reason = null): int
    {
        try {
            $status = SMSQueue::STATUS_CANCELLED;
            $pendingStatus = SMSQueue::STATUS_PENDING;
            
            $sql = "UPDATE sms_queue SET status = :status";
            $params = [
                ':status' => $status,
                ':batch_id' => $batchId,
                ':pending_status' => $pendingStatus
            ];
            
            if ($reason !== null) {
                $sql .= ", error_message = :reason";
                $params[':reason'] = $reason;
            }
            
            $sql .= " WHERE batch_id = :batch_id AND status = :pending_status";
            
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $param => $value) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($param, $value, $type);
            }
            
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error cancelling pending SMS queue entries by batch ID: ' . $e->getMessage(), [
                    'batchId' => $batchId,
                    'exception' => $e
                ]);
            }
            return 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteOldEntries(\DateTime $olderThan, array $statuses = [SMSQueue::STATUS_SENT, SMSQueue::STATUS_FAILED, SMSQueue::STATUS_CANCELLED]): int
    {
        try {
            $olderThanStr = $olderThan->format('Y-m-d H:i:s');
            $placeholders = implode(',', array_fill(0, count($statuses), '?'));
            
            $sql = "DELETE FROM sms_queue WHERE created_at < ? AND status IN ($placeholders)";
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->bindValue(1, $olderThanStr, PDO::PARAM_STR);
            foreach ($statuses as $i => $status) {
                $stmt->bindValue($i + 2, $status, PDO::PARAM_STR);
            }
            
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error deleting old SMS queue entries: ' . $e->getMessage(), [
                    'olderThan' => $olderThan->format('Y-m-d H:i:s'),
                    'statuses' => $statuses,
                    'exception' => $e
                ]);
            }
            return 0;
        }
    }

    /**
     * Create an entity from a database row
     *
     * @param array $row The database row
     * @return SMSQueue The entity
     */
    private function createEntityFromRow(array $row): SMSQueue
    {
        $entity = new SMSQueue();
        
        // Set ID
        if (isset($row['id'])) {
            $entity->setId((int)$row['id']);
        }
        
        // Set properties from row
        if (isset($row['phone_number'])) {
            $entity->setPhoneNumber($row['phone_number']);
        }
        
        if (isset($row['message'])) {
            $entity->setMessage($row['message']);
        }
        
        if (isset($row['user_id'])) {
            $entity->setUserId($row['user_id'] !== null ? (int)$row['user_id'] : null);
        }
        
        if (isset($row['segment_id'])) {
            $entity->setSegmentId($row['segment_id'] !== null ? (int)$row['segment_id'] : null);
        }
        
        if (isset($row['status'])) {
            $entity->setStatus($row['status']);
        }
        
        if (isset($row['created_at']) && $row['created_at']) {
            $entity->setCreatedAt(new \DateTime($row['created_at']));
        }
        
        if (isset($row['last_attempt_at']) && $row['last_attempt_at']) {
            $entity->setLastAttemptAt(new \DateTime($row['last_attempt_at']));
        }
        
        if (isset($row['next_attempt_at']) && $row['next_attempt_at']) {
            $entity->setNextAttemptAt(new \DateTime($row['next_attempt_at']));
        }
        
        if (isset($row['attempts'])) {
            $entity->setAttempts((int)$row['attempts']);
        }
        
        if (isset($row['priority'])) {
            $entity->setPriority((int)$row['priority']);
        }
        
        if (isset($row['error_message'])) {
            $entity->setErrorMessage($row['error_message']);
        }
        
        if (isset($row['message_id'])) {
            $entity->setMessageId($row['message_id']);
        }
        
        if (isset($row['sender_name'])) {
            $entity->setSenderName($row['sender_name']);
        }
        
        if (isset($row['sender_address'])) {
            $entity->setSenderAddress($row['sender_address']);
        }
        
        if (isset($row['batch_id'])) {
            $entity->setBatchId($row['batch_id']);
        }
        
        return $entity;
    }
}