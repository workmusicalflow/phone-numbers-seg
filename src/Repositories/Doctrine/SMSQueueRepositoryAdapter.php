<?php

namespace App\Repositories\Doctrine;

use App\Entities\SMSQueue;
use App\Repositories\Interfaces\SMSQueueRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Adapter class for SMSQueueRepository
 * 
 * This class adapts the Doctrine SMSQueueRepository to the SMSQueueRepositoryInterface.
 * It implements all required methods and ensures type compatibility.
 */
class SMSQueueRepositoryAdapter implements SMSQueueRepositoryInterface
{
    /**
     * @var SMSQueueRepository
     */
    private $repository;

    /**
     * Constructor
     *
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->repository = new SMSQueueRepository($entityManager, $logger);
    }

    /**
     * {@inheritdoc}
     */
    public function findById($id): ?SMSQueue
    {
        /** @var SMSQueue|null $entity */
        $entity = $this->repository->findById($id);
        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function findByStatus(string $status, int $limit = 100, int $offset = 0): array
    {
        return $this->repository->findByStatus($status, $limit, $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function findByBatchId(string $batchId): array
    {
        return $this->repository->findByBatchId($batchId);
    }

    /**
     * {@inheritdoc}
     */
    public function findNextBatch(int $limit = 50, array $statuses = [SMSQueue::STATUS_PENDING]): array
    {
        return $this->repository->findNextBatch($limit, $statuses);
    }

    /**
     * {@inheritdoc}
     */
    public function findExpiredProcessing(\DateTime $threshold): array
    {
        return $this->repository->findExpiredProcessing($threshold);
    }

    /**
     * {@inheritdoc}
     */
    public function findByUserId(int $userId, int $limit = 100, int $offset = 0): array
    {
        return $this->repository->findByUserId($userId, $limit, $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function findBySegmentId(int $segmentId, int $limit = 100, int $offset = 0): array
    {
        return $this->repository->findBySegmentId($segmentId, $limit, $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function countByStatus(string $status): int
    {
        return $this->repository->countByStatus($status);
    }

    /**
     * {@inheritdoc}
     */
    public function save(SMSQueue $smsQueue): SMSQueue
    {
        /** @var SMSQueue $result */
        $result = $this->repository->save($smsQueue);
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function saveBatch(array $smsQueues): bool
    {
        return $this->repository->saveBatch($smsQueues);
    }

    /**
     * {@inheritdoc}
     */
    public function updateStatus(int $id, string $status, ?string $errorMessage = null): bool
    {
        return $this->repository->updateStatus($id, $status, $errorMessage);
    }

    /**
     * {@inheritdoc}
     */
    public function increaseAttemptCount(int $id, ?\DateTime $nextAttemptAt = null): bool
    {
        return $this->repository->increaseAttemptCount($id, $nextAttemptAt);
    }

    /**
     * {@inheritdoc}
     */
    public function cancelPendingByUserId(int $userId, ?string $reason = null): int
    {
        return $this->repository->cancelPendingByUserId($userId, $reason);
    }

    /**
     * {@inheritdoc}
     */
    public function cancelPendingBySegmentId(int $segmentId, ?string $reason = null): int
    {
        return $this->repository->cancelPendingBySegmentId($segmentId, $reason);
    }

    /**
     * {@inheritdoc}
     */
    public function cancelPendingByBatchId(string $batchId, ?string $reason = null): int
    {
        return $this->repository->cancelPendingByBatchId($batchId, $reason);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteOldEntries(\DateTime $olderThan, array $statuses = [SMSQueue::STATUS_SENT, SMSQueue::STATUS_FAILED, SMSQueue::STATUS_CANCELLED]): int
    {
        return $this->repository->deleteOldEntries($olderThan, $statuses);
    }
}