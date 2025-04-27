<?php

namespace App\Services;

use App\Entities\SMSHistory; // Use Doctrine Entity
use App\Repositories\Interfaces\SMSHistoryRepositoryInterface; // Use Interface
use App\Services\Interfaces\SMSHistoryServiceInterface;

/**
 * Service for SMS history management
 */
class SMSHistoryService implements SMSHistoryServiceInterface
{
    /**
     * @var SMSHistoryRepositoryInterface
     */
    private SMSHistoryRepositoryInterface $smsHistoryRepository; // Use Interface

    /**
     * Constructor
     * 
     * @param SMSHistoryRepositoryInterface $smsHistoryRepository // Use Interface
     */
    public function __construct(SMSHistoryRepositoryInterface $smsHistoryRepository) // Use Interface
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
     * @return \App\Entities\SMSHistory The created history record // Return Doctrine Entity
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
    ): \App\Entities\SMSHistory { // Return Doctrine Entity
        $smsHistory = new \App\Entities\SMSHistory(); // Instantiate Doctrine Entity
        $smsHistory->setPhoneNumber($phoneNumber);
        $smsHistory->setMessage($message);
        $smsHistory->setStatus($status);
        $smsHistory->setSenderAddress($senderAddress);
        $smsHistory->setSenderName($senderName);
        $smsHistory->setMessageId($messageId);
        $smsHistory->setErrorMessage($errorMessage);
        $smsHistory->setPhoneNumberId($phoneNumberId);
        $smsHistory->setSegmentId($segmentId);
        $smsHistory->setCreatedAt(new \DateTime()); // Assuming createdAt is set here or in save

        return $this->smsHistoryRepository->save($smsHistory); // Save Doctrine Entity
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
     * Get SMS history by user ID
     * 
     * @param int $userId User ID to search for
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @return array Array of SMSHistory objects
     */
    public function getHistoryByUserId(int $userId, int $limit = 100, int $offset = 0): array
    {
        return $this->smsHistoryRepository->findByUserId($userId, $limit, $offset);
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

    /**
     * Get total count of SMS history records for a specific user
     * 
     * @param int $userId User ID to count records for
     * @return int Total count for the user
     */
    public function getHistoryCountByUserId(int $userId): int
    {
        return $this->smsHistoryRepository->countByUserId($userId);
    }
}
