<?php

namespace App\Repositories\Doctrine;

use App\Entities\SMSQueue;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Doctrine repository for SMSQueue entity
 */
class SMSQueueRepository extends BaseRepository
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        parent::__construct($entityManager, SMSQueue::class);
        $this->logger = $logger;
    }

    /**
     * Find an SMS queue entry by its ID
     * 
     * @param mixed $id The SMS queue entry ID
     * @return SMSQueue|null The entity or null if not found
     */
    public function findById($id): ?object
    {
        try {
            /** @var SMSQueue|null $entity */
            $entity = $this->find($id);
            return $entity;
        } catch (\Exception $e) {
            $this->logger->error('Error finding SMS queue entry by ID: ' . $e->getMessage(), [
                'id' => $id,
                'exception' => $e
            ]);
            return null;
        }
    }

    /**
     * Find SMS queue entries by status
     * 
     * @param string $status The status to search for
     * @param int $limit Maximum number of entries to return
     * @param int $offset Number of entries to skip
     * @return array Array of SMS queue entries
     */
    public function findByStatus(string $status, int $limit = 100, int $offset = 0): array
    {
        try {
            return $this->findBy(
                ['status' => $status],
                ['priority' => 'DESC', 'nextAttemptAt' => 'ASC', 'createdAt' => 'ASC'],
                $limit,
                $offset
            );
        } catch (\Exception $e) {
            $this->logger->error('Error finding SMS queue entries by status: ' . $e->getMessage(), [
                'status' => $status,
                'limit' => $limit,
                'offset' => $offset,
                'exception' => $e
            ]);
            return [];
        }
    }

    /**
     * Find SMS queue entries by batch ID
     * 
     * @param string $batchId The batch ID to search for
     * @return array Array of SMS queue entries
     */
    public function findByBatchId(string $batchId): array
    {
        try {
            return $this->findBy(['batchId' => $batchId]);
        } catch (\Exception $e) {
            $this->logger->error('Error finding SMS queue entries by batch ID: ' . $e->getMessage(), [
                'batchId' => $batchId,
                'exception' => $e
            ]);
            return [];
        }
    }

    /**
     * Find SMS queue entries that are ready to be processed
     * 
     * @param int $limit Maximum number of entries to return
     * @param array $statuses Array of statuses to include (defaults to PENDING)
     * @return array Array of SMS queue entries
     */
    public function findNextBatch(int $limit = 50, array $statuses = [SMSQueue::STATUS_PENDING]): array
    {
        try {
            $now = new \DateTime();
            
            $queryBuilder = $this->getEntityManager()->createQueryBuilder();
            $queryBuilder->select('q')
                ->from($this->getClassName(), 'q')
                ->where('q.status IN (:statuses)')
                ->andWhere('q.nextAttemptAt IS NULL OR q.nextAttemptAt <= :now')
                ->setParameter('statuses', $statuses)
                ->setParameter('now', $now)
                ->orderBy('q.priority', 'DESC')
                ->addOrderBy('q.nextAttemptAt', 'ASC')
                ->addOrderBy('q.createdAt', 'ASC')
                ->setMaxResults($limit);
                
            return $queryBuilder->getQuery()->getResult();
        } catch (\Exception $e) {
            $this->logger->error('Error finding next batch of SMS queue entries: ' . $e->getMessage(), [
                'exception' => $e,
                'limit' => $limit,
                'statuses' => $statuses
            ]);
            return [];
        }
    }

    /**
     * Find SMS queue entries that have been stuck in processing for too long
     * 
     * @param \DateTime $threshold Entries in processing state since before this time
     * @return array Array of stuck SMS queue entries
     */
    public function findExpiredProcessing(\DateTime $threshold): array
    {
        try {
            $queryBuilder = $this->getEntityManager()->createQueryBuilder();
            $queryBuilder->select('q')
                ->from($this->getClassName(), 'q')
                ->where('q.status = :status')
                ->andWhere('q.lastAttemptAt < :threshold')
                ->setParameter('status', SMSQueue::STATUS_PROCESSING)
                ->setParameter('threshold', $threshold);
                
            return $queryBuilder->getQuery()->getResult();
        } catch (\Exception $e) {
            $this->logger->error('Error finding expired processing SMS queue entries: ' . $e->getMessage(), [
                'exception' => $e,
                'threshold' => $threshold->format('Y-m-d H:i:s')
            ]);
            return [];
        }
    }

    /**
     * Find SMS queue entries by user ID
     * 
     * @param int $userId The user ID
     * @param int $limit Maximum number of entries to return
     * @param int $offset Number of entries to skip
     * @return array Array of SMS queue entries
     */
    public function findByUserId(int $userId, int $limit = 100, int $offset = 0): array
    {
        try {
            return $this->findBy(
                ['userId' => $userId],
                ['createdAt' => 'DESC'],
                $limit,
                $offset
            );
        } catch (\Exception $e) {
            $this->logger->error('Error finding SMS queue entries by user ID: ' . $e->getMessage(), [
                'userId' => $userId,
                'limit' => $limit,
                'offset' => $offset,
                'exception' => $e
            ]);
            return [];
        }
    }

    /**
     * Find SMS queue entries by segment ID
     * 
     * @param int $segmentId The segment ID
     * @param int $limit Maximum number of entries to return
     * @param int $offset Number of entries to skip
     * @return array Array of SMS queue entries
     */
    public function findBySegmentId(int $segmentId, int $limit = 100, int $offset = 0): array
    {
        try {
            return $this->findBy(
                ['segmentId' => $segmentId],
                ['createdAt' => 'DESC'],
                $limit,
                $offset
            );
        } catch (\Exception $e) {
            $this->logger->error('Error finding SMS queue entries by segment ID: ' . $e->getMessage(), [
                'segmentId' => $segmentId,
                'limit' => $limit,
                'offset' => $offset,
                'exception' => $e
            ]);
            return [];
        }
    }

    /**
     * Count SMS queue entries by status
     * 
     * @param string $status The status to count
     * @return int The number of entries with the given status
     */
    public function countByStatus(string $status): int
    {
        try {
            $queryBuilder = $this->getEntityManager()->createQueryBuilder();
            $queryBuilder->select('COUNT(q.id)')
                ->from($this->getClassName(), 'q')
                ->where('q.status = :status')
                ->setParameter('status', $status);
                
            return (int) $queryBuilder->getQuery()->getSingleScalarResult();
        } catch (\Exception $e) {
            $this->logger->error('Error counting SMS queue entries by status: ' . $e->getMessage(), [
                'exception' => $e,
                'status' => $status
            ]);
            return 0;
        }
    }

    /**
     * Save an SMS queue entry
     *
     * @param SMSQueue $smsQueue The SMS queue entry to save
     * @return SMSQueue The saved SMS queue entry
     */
    public function save($entity)
    {
        try {
            $em = $this->getEntityManager();
            $em->persist($entity);
            $em->flush();
            return $entity;
        } catch (\Exception $e) {
            $this->logger->error('Error saving SMS queue entry: ' . $e->getMessage(), [
                'exception' => $e,
                'smsQueueId' => $entity->getId()
            ]);
            throw $e;
        }
    }

    /**
     * Save multiple SMS queue entries at once
     * 
     * @param array $smsQueues Array of SMS queue entries to save
     * @return bool True if successful
     */
    public function saveBatch(array $smsQueues): bool
    {
        if (empty($smsQueues)) {
            return true;
        }

        try {
            $em = $this->getEntityManager();
            $em->beginTransaction();
            
            foreach ($smsQueues as $smsQueue) {
                if (!$smsQueue instanceof SMSQueue) {
                    throw new \InvalidArgumentException('Array must contain only SMSQueue objects.');
                }
                $em->persist($smsQueue);
            }
            
            $em->flush();
            $em->commit();
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error saving batch of SMS queue entries: ' . $e->getMessage(), [
                'exception' => $e,
                'count' => count($smsQueues)
            ]);
            
            // Try to rollback if transaction is active
            if ($em->getConnection()->isTransactionActive()) {
                $em->rollback();
            }
            
            throw $e;
        }
    }

    /**
     * Update the status of an SMS queue entry
     * 
     * @param int $id The ID of the SMS queue entry
     * @param string $status The new status
     * @param string|null $errorMessage Optional error message for failed entries
     * @return bool True if successful
     */
    public function updateStatus(int $id, string $status, ?string $errorMessage = null): bool
    {
        try {
            $queryBuilder = $this->getEntityManager()->createQueryBuilder();
            $queryBuilder->update($this->getClassName(), 'q')
                ->set('q.status', ':status')
                ->where('q.id = :id')
                ->setParameter('status', $status)
                ->setParameter('id', $id);
                
            if ($errorMessage !== null) {
                $queryBuilder->set('q.errorMessage', ':errorMessage')
                    ->setParameter('errorMessage', $errorMessage);
            }
                
            $result = $queryBuilder->getQuery()->execute();
            return $result > 0;
        } catch (\Exception $e) {
            $this->logger->error('Error updating SMS queue entry status: ' . $e->getMessage(), [
                'exception' => $e,
                'id' => $id,
                'status' => $status
            ]);
            return false;
        }
    }

    /**
     * Increase the attempt count for an SMS queue entry
     * 
     * @param int $id The ID of the SMS queue entry
     * @param \DateTime|null $nextAttemptAt When to try again (null for no retry)
     * @return bool True if successful
     */
    public function increaseAttemptCount(int $id, ?\DateTime $nextAttemptAt = null): bool
    {
        try {
            $smsQueue = $this->findById($id);
            if ($smsQueue === null) {
                return false;
            }
            
            $smsQueue->incrementAttempts();
            $smsQueue->setLastAttemptAt(new \DateTime());
            
            if ($nextAttemptAt !== null) {
                $smsQueue->setNextAttemptAt($nextAttemptAt);
            }
            
            $this->getEntityManager()->flush();
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error increasing SMS queue entry attempt count: ' . $e->getMessage(), [
                'exception' => $e,
                'id' => $id
            ]);
            return false;
        }
    }

    /**
     * Cancel all pending SMS queue entries for a user
     * 
     * @param int $userId The user ID
     * @param string|null $reason Optional reason for cancellation
     * @return int Number of entries cancelled
     */
    public function cancelPendingByUserId(int $userId, ?string $reason = null): int
    {
        try {
            $queryBuilder = $this->getEntityManager()->createQueryBuilder();
            $queryBuilder->update($this->getClassName(), 'q')
                ->set('q.status', ':newStatus')
                ->where('q.userId = :userId')
                ->andWhere('q.status = :pendingStatus')
                ->setParameter('newStatus', SMSQueue::STATUS_CANCELLED)
                ->setParameter('userId', $userId)
                ->setParameter('pendingStatus', SMSQueue::STATUS_PENDING);
                
            if ($reason !== null) {
                $queryBuilder->set('q.errorMessage', ':reason')
                    ->setParameter('reason', $reason);
            }
                
            return $queryBuilder->getQuery()->execute();
        } catch (\Exception $e) {
            $this->logger->error('Error cancelling pending SMS queue entries by user ID: ' . $e->getMessage(), [
                'exception' => $e,
                'userId' => $userId
            ]);
            return 0;
        }
    }

    /**
     * Cancel all pending SMS queue entries for a segment
     * 
     * @param int $segmentId The segment ID
     * @param string|null $reason Optional reason for cancellation
     * @return int Number of entries cancelled
     */
    public function cancelPendingBySegmentId(int $segmentId, ?string $reason = null): int
    {
        try {
            $queryBuilder = $this->getEntityManager()->createQueryBuilder();
            $queryBuilder->update($this->getClassName(), 'q')
                ->set('q.status', ':newStatus')
                ->where('q.segmentId = :segmentId')
                ->andWhere('q.status = :pendingStatus')
                ->setParameter('newStatus', SMSQueue::STATUS_CANCELLED)
                ->setParameter('segmentId', $segmentId)
                ->setParameter('pendingStatus', SMSQueue::STATUS_PENDING);
                
            if ($reason !== null) {
                $queryBuilder->set('q.errorMessage', ':reason')
                    ->setParameter('reason', $reason);
            }
                
            return $queryBuilder->getQuery()->execute();
        } catch (\Exception $e) {
            $this->logger->error('Error cancelling pending SMS queue entries by segment ID: ' . $e->getMessage(), [
                'exception' => $e,
                'segmentId' => $segmentId
            ]);
            return 0;
        }
    }

    /**
     * Cancel pending SMS queue entries by batch ID
     * 
     * @param string $batchId The batch ID
     * @param string|null $reason Optional reason for cancellation
     * @return int Number of entries cancelled
     */
    public function cancelPendingByBatchId(string $batchId, ?string $reason = null): int
    {
        try {
            $queryBuilder = $this->getEntityManager()->createQueryBuilder();
            $queryBuilder->update($this->getClassName(), 'q')
                ->set('q.status', ':newStatus')
                ->where('q.batchId = :batchId')
                ->andWhere('q.status = :pendingStatus')
                ->setParameter('newStatus', SMSQueue::STATUS_CANCELLED)
                ->setParameter('batchId', $batchId)
                ->setParameter('pendingStatus', SMSQueue::STATUS_PENDING);
                
            if ($reason !== null) {
                $queryBuilder->set('q.errorMessage', ':reason')
                    ->setParameter('reason', $reason);
            }
                
            return $queryBuilder->getQuery()->execute();
        } catch (\Exception $e) {
            $this->logger->error('Error cancelling pending SMS queue entries by batch ID: ' . $e->getMessage(), [
                'exception' => $e,
                'batchId' => $batchId
            ]);
            return 0;
        }
    }

    /**
     * Delete old entries from the SMS queue
     * 
     * @param \DateTime $olderThan Delete entries created before this time
     * @param array $statuses Only delete entries with these statuses
     * @return int Number of entries deleted
     */
    public function deleteOldEntries(\DateTime $olderThan, array $statuses = [SMSQueue::STATUS_SENT, SMSQueue::STATUS_FAILED, SMSQueue::STATUS_CANCELLED]): int
    {
        try {
            $queryBuilder = $this->getEntityManager()->createQueryBuilder();
            $queryBuilder->delete($this->getClassName(), 'q')
                ->where('q.createdAt < :olderThan')
                ->andWhere('q.status IN (:statuses)')
                ->setParameter('olderThan', $olderThan)
                ->setParameter('statuses', $statuses);
                
            return $queryBuilder->getQuery()->execute();
        } catch (\Exception $e) {
            $this->logger->error('Error deleting old SMS queue entries: ' . $e->getMessage(), [
                'exception' => $e,
                'olderThan' => $olderThan->format('Y-m-d H:i:s'),
                'statuses' => $statuses
            ]);
            return 0;
        }
    }
}