<?php

namespace App\Repositories\Interfaces;

use App\Entities\SMSQueue;

/**
 * Interface for SMS Queue Repository
 */
interface SMSQueueRepositoryInterface
{
    /**
     * Find an SMS queue entry by its ID
     *
     * @param int $id The SMS queue entry ID
     * @return SMSQueue|null The SMS queue entry or null if not found
     */
    public function findById($id): ?SMSQueue;

    /**
     * Find SMS queue entries by status
     *
     * @param string $status The status to search for
     * @param int $limit Maximum number of entries to return
     * @param int $offset Number of entries to skip
     * @return array Array of SMS queue entries
     */
    public function findByStatus(string $status, int $limit = 100, int $offset = 0): array;

    /**
     * Find SMS queue entries by batch ID
     *
     * @param string $batchId The batch ID to search for
     * @return array Array of SMS queue entries
     */
    public function findByBatchId(string $batchId): array;

    /**
     * Find SMS queue entries that are ready to be processed
     *
     * @param int $limit Maximum number of entries to return
     * @param array $statuses Array of statuses to include (defaults to PENDING)
     * @return array Array of SMS queue entries
     */
    public function findNextBatch(int $limit = 50, array $statuses = [SMSQueue::STATUS_PENDING]): array;

    /**
     * Find SMS queue entries that have been stuck in processing for too long
     *
     * @param \DateTime $threshold Entries in processing state since before this time
     * @return array Array of stuck SMS queue entries
     */
    public function findExpiredProcessing(\DateTime $threshold): array;

    /**
     * Find SMS queue entries by user ID
     *
     * @param int $userId The user ID
     * @param int $limit Maximum number of entries to return
     * @param int $offset Number of entries to skip
     * @return array Array of SMS queue entries
     */
    public function findByUserId(int $userId, int $limit = 100, int $offset = 0): array;

    /**
     * Find SMS queue entries by segment ID
     *
     * @param int $segmentId The segment ID
     * @param int $limit Maximum number of entries to return
     * @param int $offset Number of entries to skip
     * @return array Array of SMS queue entries
     */
    public function findBySegmentId(int $segmentId, int $limit = 100, int $offset = 0): array;

    /**
     * Count SMS queue entries by status
     *
     * @param string $status The status to count
     * @return int The number of entries with the given status
     */
    public function countByStatus(string $status): int;

    /**
     * Save an SMS queue entry
     *
     * @param SMSQueue $smsQueue The SMS queue entry to save
     * @return SMSQueue The saved SMS queue entry
     */
    public function save(SMSQueue $smsQueue): SMSQueue;

    /**
     * Save multiple SMS queue entries at once
     *
     * @param array $smsQueues Array of SMS queue entries to save
     * @return bool True if successful
     */
    public function saveBatch(array $smsQueues): bool;

    /**
     * Update the status of an SMS queue entry
     *
     * @param int $id The ID of the SMS queue entry
     * @param string $status The new status
     * @param string|null $errorMessage Optional error message for failed entries
     * @return bool True if successful
     */
    public function updateStatus(int $id, string $status, ?string $errorMessage = null): bool;

    /**
     * Increase the attempt count for an SMS queue entry
     *
     * @param int $id The ID of the SMS queue entry
     * @param \DateTime|null $nextAttemptAt When to try again (null for no retry)
     * @return bool True if successful
     */
    public function increaseAttemptCount(int $id, ?\DateTime $nextAttemptAt = null): bool;

    /**
     * Cancel all pending SMS queue entries for a user
     *
     * @param int $userId The user ID
     * @param string|null $reason Optional reason for cancellation
     * @return int Number of entries cancelled
     */
    public function cancelPendingByUserId(int $userId, ?string $reason = null): int;

    /**
     * Cancel all pending SMS queue entries for a segment
     *
     * @param int $segmentId The segment ID
     * @param string|null $reason Optional reason for cancellation
     * @return int Number of entries cancelled
     */
    public function cancelPendingBySegmentId(int $segmentId, ?string $reason = null): int;

    /**
     * Cancel pending SMS queue entries by batch ID
     *
     * @param string $batchId The batch ID
     * @param string|null $reason Optional reason for cancellation
     * @return int Number of entries cancelled
     */
    public function cancelPendingByBatchId(string $batchId, ?string $reason = null): int;

    /**
     * Delete old entries from the SMS queue
     *
     * @param \DateTime $olderThan Delete entries created before this time
     * @param array $statuses Only delete entries with these statuses
     * @return int Number of entries deleted
     */
    public function deleteOldEntries(\DateTime $olderThan, array $statuses = [SMSQueue::STATUS_SENT, SMSQueue::STATUS_FAILED, SMSQueue::STATUS_CANCELLED]): int;
}