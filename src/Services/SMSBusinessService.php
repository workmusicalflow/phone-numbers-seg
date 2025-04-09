<?php

namespace App\Services;

use App\Repositories\CustomSegmentRepository;
use App\Repositories\PhoneNumberRepository;
use App\Services\Interfaces\SMSBusinessServiceInterface;
use App\Services\Interfaces\SMSHistoryServiceInterface;
use App\Services\Interfaces\SMSSenderServiceInterface;

/**
 * Service for SMS business logic
 * This service coordinates the other specialized services and provides business logic
 */
class SMSBusinessService implements SMSBusinessServiceInterface
{
    /**
     * @var SMSSenderServiceInterface
     */
    private SMSSenderServiceInterface $senderService;

    /**
     * @var SMSHistoryServiceInterface
     */
    private SMSHistoryServiceInterface $historyService;

    /**
     * @var CustomSegmentRepository
     */
    private CustomSegmentRepository $customSegmentRepository;

    /**
     * @var PhoneNumberRepository
     */
    private PhoneNumberRepository $phoneNumberRepository;

    /**
     * Constructor
     * 
     * @param SMSSenderServiceInterface $senderService
     * @param SMSHistoryServiceInterface $historyService
     * @param CustomSegmentRepository $customSegmentRepository
     * @param PhoneNumberRepository $phoneNumberRepository
     */
    public function __construct(
        SMSSenderServiceInterface $senderService,
        SMSHistoryServiceInterface $historyService,
        CustomSegmentRepository $customSegmentRepository,
        PhoneNumberRepository $phoneNumberRepository
    ) {
        $this->senderService = $senderService;
        $this->historyService = $historyService;
        $this->customSegmentRepository = $customSegmentRepository;
        $this->phoneNumberRepository = $phoneNumberRepository;
    }

    /**
     * Get segments available for SMS
     * 
     * @return array Array of segments with phone number counts
     */
    public function getSegmentsForSMS(): array
    {
        $segments = $this->customSegmentRepository->findAll();
        $result = [];

        foreach ($segments as $segment) {
            $phoneNumberCount = $this->phoneNumberRepository->countByCustomSegment($segment->getId());
            $result[] = [
                'id' => $segment->getId(),
                'name' => $segment->getName(),
                'description' => $segment->getDescription(),
                'phoneNumberCount' => $phoneNumberCount
            ];
        }

        return $result;
    }

    /**
     * Get SMS history with filtering options
     * 
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @param string|null $search Search term for phone number
     * @param string|null $status Status filter (SENT, FAILED)
     * @param int|null $segmentId Segment ID filter
     * @param int|null $userId User ID filter
     * @return array Array of SMS history records
     */
    public function getSMSHistory(
        int $limit = 100,
        int $offset = 0,
        ?string $search = null,
        ?string $status = null,
        ?int $segmentId = null,
        ?int $userId = null
    ): array {
        // Apply filters based on parameters
        if ($userId !== null) {
            // If userId is provided, filter by user ID first
            return $this->historyService->getHistoryByUserId($userId, $limit, $offset);
        } elseif ($search !== null) {
            return $this->historyService->getHistoryByPhoneNumber($search, $limit, $offset);
        } elseif ($status !== null) {
            return $this->historyService->getHistoryByStatus($status, $limit, $offset);
        } elseif ($segmentId !== null) {
            return $this->historyService->getHistoryBySegmentId($segmentId, $limit, $offset);
        } else {
            return $this->historyService->getAllHistory($limit, $offset);
        }
    }

    /**
     * Get total count of SMS history records
     * 
     * @param int|null $userId User ID filter
     * @return int Total count
     */
    public function getSMSHistoryCount(?int $userId = null): int
    {
        if ($userId !== null) {
            return $this->historyService->getHistoryCountByUserId($userId);
        }
        return $this->historyService->getHistoryCount();
    }

    /**
     * Send an SMS to a single phone number
     * 
     * @param string $phoneNumber Receiver phone number
     * @param string $message SMS message
     * @return array Result with status and details
     */
    public function sendSMS(string $phoneNumber, string $message): array
    {
        try {
            $result = $this->senderService->sendSMS($phoneNumber, $message);
            return [
                'success' => true,
                'message' => 'SMS sent successfully',
                'details' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'details' => null
            ];
        }
    }

    /**
     * Send SMS to multiple phone numbers
     * 
     * @param array $phoneNumbers Array of receiver phone numbers
     * @param string $message SMS message
     * @return array Result with status, summary and details
     */
    public function sendBulkSMS(array $phoneNumbers, string $message): array
    {
        try {
            $result = $this->senderService->sendBulkSMS($phoneNumbers, $message);
            return [
                'success' => $result['success'],
                'message' => $result['message'],
                'summary' => [
                    'total' => $result['total'],
                    'successful' => $result['successful'],
                    'failed' => $result['failed']
                ],
                'details' => $result['results']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'summary' => [
                    'total' => count($phoneNumbers),
                    'successful' => 0,
                    'failed' => count($phoneNumbers)
                ],
                'details' => null
            ];
        }
    }

    /**
     * Send SMS to all phone numbers in a segment
     * 
     * @param int $segmentId Segment ID
     * @param string $message SMS message
     * @return array Result with status, segment info, summary and details
     */
    public function sendSMSToSegment(int $segmentId, string $message): array
    {
        try {
            $result = $this->senderService->sendSMSToSegment($segmentId, $message);
            return [
                'success' => $result['success'],
                'message' => $result['message'],
                'segment' => $result['segment'] ?? null,
                'summary' => [
                    'total' => $result['total'],
                    'successful' => $result['successful'],
                    'failed' => $result['failed']
                ],
                'details' => $result['results']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'segment' => ['id' => $segmentId],
                'summary' => [
                    'total' => 0,
                    'successful' => 0,
                    'failed' => 0
                ],
                'details' => null
            ];
        }
    }
}
