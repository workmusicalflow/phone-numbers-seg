<?php

namespace App\Services;

use App\Models\SMSHistory;
use App\Repositories\SMSHistoryRepository;
use App\Services\Interfaces\SMSHistoryServiceInterface;

/**
 * Service for SMS history management
 */
class SMSHistoryService implements SMSHistoryServiceInterface
{
    /**
     * @var SMSHistoryRepository
     */
    private SMSHistoryRepository $smsHistoryRepository;

    /**
     * Constructor
     * 
     * @param SMSHistoryRepository $smsHistoryRepository
     */
    public function __construct(SMSHistoryRepository $smsHistoryRepository)
    {
        $this->smsHistoryRepository = $smsHistoryRepository;
    }

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
    ): SMSHistory {
        $smsHistory = new SMSHistory(
            $phoneNumber,
            $message,
            $status,
            $senderAddress,
            $senderName,
            $messageId,
            $errorMessage,
            $phoneNumberId,
            $segmentId
        );

        return $this->smsHistoryRepository->save($smsHistory);
    }

    /**
     * Update segment ID for recent SMS history entries
     * 
     * @param array $phoneNumbers Array of phone numbers
     * @param int $segmentId Segment ID to set
     * @return bool Success status
     */
    public function updateSegmentIdForPhoneNumbers(array $phoneNumbers, int $segmentId): bool
    {
        return $this->smsHistoryRepository->updateSegmentIdForPhoneNumbers($phoneNumbers, $segmentId);
    }

    /**
     * Get SMS history by phone number
     * 
     * @param string $phoneNumber Phone number to search for
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @return array Array of SMSHistory objects
     */
    public function getHistoryByPhoneNumber(string $phoneNumber, int $limit = 100, int $offset = 0): array
    {
        return $this->smsHistoryRepository->findByPhoneNumber($phoneNumber, $limit, $offset);
    }

    /**
     * Get SMS history by status
     * 
     * @param string $status Status to search for (SENT, FAILED)
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @return array Array of SMSHistory objects
     */
    public function getHistoryByStatus(string $status, int $limit = 100, int $offset = 0): array
    {
        return $this->smsHistoryRepository->findByStatus($status, $limit, $offset);
    }

    /**
     * Get SMS history by segment ID
     * 
     * @param int $segmentId Segment ID to search for
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @return array Array of SMSHistory objects
     */
    public function getHistoryBySegmentId(int $segmentId, int $limit = 100, int $offset = 0): array
    {
        return $this->smsHistoryRepository->findBySegmentId($segmentId, $limit, $offset);
    }

    /**
     * Get all SMS history
     * 
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @return array Array of SMSHistory objects
     */
    public function getAllHistory(int $limit = 100, int $offset = 0): array
    {
        return $this->smsHistoryRepository->findAll($limit, $offset);
    }

    /**
     * Get total count of SMS history records
     * 
     * @return int Total count
     */
    public function getHistoryCount(): int
    {
        return $this->smsHistoryRepository->count();
    }
}
