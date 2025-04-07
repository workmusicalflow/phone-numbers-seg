<?php

namespace App\Services\Interfaces;

/**
 * Interface for SMS business service
 * This service coordinates the other specialized services and provides business logic
 */
interface SMSBusinessServiceInterface
{
    /**
     * Get segments available for SMS
     * 
     * @return array Array of segments with phone number counts
     */
    public function getSegmentsForSMS(): array;

    /**
     * Get SMS history with filtering options
     * 
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @param string|null $search Search term for phone number
     * @param string|null $status Status filter (SENT, FAILED)
     * @param int|null $segmentId Segment ID filter
     * @return array Array of SMS history records
     */
    public function getSMSHistory(
        int $limit = 100,
        int $offset = 0,
        ?string $search = null,
        ?string $status = null,
        ?int $segmentId = null
    ): array;

    /**
     * Get total count of SMS history records
     * 
     * @return int Total count
     */
    public function getSMSHistoryCount(): int;

    /**
     * Send an SMS to a single phone number
     * 
     * @param string $phoneNumber Receiver phone number
     * @param string $message SMS message
     * @return array Result with status and details
     */
    public function sendSMS(string $phoneNumber, string $message): array;

    /**
     * Send SMS to multiple phone numbers
     * 
     * @param array $phoneNumbers Array of receiver phone numbers
     * @param string $message SMS message
     * @return array Result with status, summary and details
     */
    public function sendBulkSMS(array $phoneNumbers, string $message): array;

    /**
     * Send SMS to all phone numbers in a segment
     * 
     * @param int $segmentId Segment ID
     * @param string $message SMS message
     * @return array Result with status, segment info, summary and details
     */
    public function sendSMSToSegment(int $segmentId, string $message): array;
}
