<?php

namespace App\Services\Interfaces;

use App\Entities\SMSQueue;

/**
 * Interface for SMS queue service
 */
interface SMSQueueServiceInterface
{
    /**
     * Enqueue a single SMS
     *
     * @param string $phoneNumber Recipient phone number
     * @param string $message SMS message
     * @param int|null $userId User ID sending the SMS
     * @param int|null $segmentId Segment ID if part of a segment send
     * @param string|null $senderName Sender name
     * @param int $priority Priority level
     * @param string|null $batchId Batch ID for grouping
     * @return SMSQueue The queued SMS
     */
    public function enqueue(
        string $phoneNumber,
        string $message,
        ?int $userId = null,
        ?int $segmentId = null,
        ?string $senderName = null,
        int $priority = SMSQueue::PRIORITY_NORMAL,
        ?string $batchId = null
    ): SMSQueue;

    /**
     * Enqueue multiple SMS messages
     *
     * @param array $phoneNumbers Array of recipient phone numbers
     * @param string $message SMS message
     * @param int|null $userId User ID sending the SMS
     * @param int|null $segmentId Segment ID if part of a segment send
     * @param string|null $senderName Sender name
     * @param int $priority Priority level
     * @return string Batch ID for the operation
     */
    public function enqueueBulk(
        array $phoneNumbers,
        string $message,
        ?int $userId = null,
        ?int $segmentId = null,
        ?string $senderName = null,
        int $priority = SMSQueue::PRIORITY_NORMAL
    ): string;

    /**
     * Enqueue SMS messages for all numbers in a segment
     *
     * @param int $segmentId Segment ID
     * @param string $message SMS message
     * @param int|null $userId User ID sending the SMS
     * @param string|null $senderName Sender name
     * @param int $priority Priority level
     * @return string Batch ID for the operation
     */
    public function enqueueSegment(
        int $segmentId,
        string $message,
        ?int $userId = null,
        ?string $senderName = null,
        int $priority = SMSQueue::PRIORITY_NORMAL
    ): string;

    /**
     * Enqueue SMS messages for all contacts of a user
     *
     * @param int $userId User ID whose contacts will receive the SMS
     * @param string $message SMS message
     * @param string|null $senderName Sender name
     * @param int $priority Priority level
     * @return string Batch ID for the operation
     */
    public function enqueueAllContacts(
        int $userId,
        string $message,
        ?string $senderName = null,
        int $priority = SMSQueue::PRIORITY_NORMAL
    ): string;

    /**
     * Process the next batch of queued SMS messages
     *
     * @param int $batchSize Maximum number of messages to process
     * @return array Array with counts of results (sent, failed, total)
     */
    public function processNextBatch(int $batchSize = 50): array;

    /**
     * Get status of a batch
     *
     * @param string $batchId Batch ID
     * @return array Status information (total, sent, failed, pending, etc.)
     */
    public function getBatchStatus(string $batchId): array;

    /**
     * Cancel all pending SMS in a batch
     *
     * @param string $batchId Batch ID
     * @param string|null $reason Optional reason for cancellation
     * @return int Number of SMS cancelled
     */
    public function cancelBatch(string $batchId, ?string $reason = null): int;

    /**
     * Get queue statistics
     *
     * @return array Statistics about the queue (counts by status, etc.)
     */
    public function getQueueStats(): array;

    /**
     * Cleanup old queue entries
     *
     * @param int $daysToKeep Number of days to keep history
     * @return int Number of entries deleted
     */
    public function cleanupOldEntries(int $daysToKeep = 30): int;
}