<?php

namespace App\Services\Interfaces;

use App\Models\SMSHistory;

/**
 * Interface for SMS history service
 */
interface SMSHistoryServiceInterface
{
    /**
     * Record a new SMS history entry
     * 
     * @param string $phoneNumber Receiver phone number
     * @param string $message SMS message
     * @param string $status Status of the SMS (SENT, FAILED)
     * @param string $senderAddress Sender address
     * @param string $senderName Sender name
     * @param string|null $messageId Message ID from the API
     * @param string|null $errorMessage Error message if failed
     * @param int|null $phoneNumberId Associated phone number ID
     * @param int|null $segmentId Associated segment ID
     * @return SMSHistory The created history record
     */
    public function recordSMSHistory(
        string $phoneNumber,
        string $message,
        string $status,
        string $senderAddress,
        string $senderName,
        ?string $messageId = null,
        ?string $errorMessage = null,
        ?int $phoneNumberId = null,
        ?int $segmentId = null
    ): SMSHistory;

    /**
     * Update segment ID for recent SMS history entries
     * 
     * @param array $phoneNumbers Array of phone numbers
     * @param int $segmentId Segment ID to set
     * @return bool Success status
     */
    public function updateSegmentIdForPhoneNumbers(array $phoneNumbers, int $segmentId): bool;

    /**
     * Get SMS history by phone number
     * 
     * @param string $phoneNumber Phone number to search for
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @return array Array of SMSHistory objects
     */
    public function getHistoryByPhoneNumber(string $phoneNumber, int $limit = 100, int $offset = 0): array;

    /**
     * Get SMS history by status
     * 
     * @param string $status Status to search for (SENT, FAILED)
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @return array Array of SMSHistory objects
     */
    public function getHistoryByStatus(string $status, int $limit = 100, int $offset = 0): array;

    /**
     * Get SMS history by segment ID
     * 
     * @param int $segmentId Segment ID to search for
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @return array Array of SMSHistory objects
     */
    public function getHistoryBySegmentId(int $segmentId, int $limit = 100, int $offset = 0): array;

    /**
     * Get all SMS history
     * 
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @return array Array of SMSHistory objects
     */
    public function getAllHistory(int $limit = 100, int $offset = 0): array;

    /**
     * Get total count of SMS history records
     * 
     * @return int Total count
     */
    public function getHistoryCount(): int;
}
