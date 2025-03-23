<?php

namespace App\Controllers;

use App\Services\SMSService;
use App\Repositories\PhoneNumberRepository;
use App\Repositories\CustomSegmentRepository;
use PDO;

/**
 * SMSController
 * 
 * Controller for SMS operations
 */
class SMSController
{
    /**
     * @var SMSService
     */
    private SMSService $smsService;

    /**
     * @var PhoneNumberRepository
     */
    private PhoneNumberRepository $phoneNumberRepository;

    /**
     * @var CustomSegmentRepository
     */
    private CustomSegmentRepository $customSegmentRepository;

    /**
     * Constructor
     * 
     * @param PDO $db
     */
    public function __construct(PDO $db)
    {
        // Initialize repositories
        $this->customSegmentRepository = new CustomSegmentRepository($db);
        $this->phoneNumberRepository = new PhoneNumberRepository(
            $db,
            null,
            $this->customSegmentRepository
        );

        // Initialize SMS service with Orange API credentials
        $this->smsService = new SMSService(
            'DGxbQKd9JHXLdFaWGtv0FfqFFI7Gu03a',  // Client ID
            'S4ywfdZUjNvOXErMr5NyQwgliBCdXIAYp1DcibKThBXs',  // Client Secret
            'tel:+2250595016840',  // Sender address
            'Qualitas CI',  // Sender name
            $this->phoneNumberRepository,
            $this->customSegmentRepository
        );
    }

    /**
     * Send an SMS to a single phone number
     * 
     * @param string $number
     * @param string $message
     * @return array
     */
    public function sendSMS(string $number, string $message): array
    {
        try {
            $result = $this->smsService->sendSMS($number, $message);
            return [
                'status' => 'success',
                'result' => $result
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Send an SMS to multiple phone numbers
     * 
     * @param array $numbers
     * @param string $message
     * @return array
     */
    public function sendBulkSMS(array $numbers, string $message): array
    {
        try {
            $results = $this->smsService->sendBulkSMS($numbers, $message);
            return [
                'status' => 'success',
                'results' => $results,
                'summary' => [
                    'total' => count($numbers),
                    'successful' => count(array_filter($results, function ($result) {
                        return $result['status'] === 'success';
                    })),
                    'failed' => count(array_filter($results, function ($result) {
                        return $result['status'] === 'error';
                    }))
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Send an SMS to all phone numbers in a segment
     * 
     * @param int $segmentId
     * @param string $message
     * @return array
     */
    public function sendSMSToSegment(int $segmentId, string $message): array
    {
        try {
            // Check if the segment exists
            $segment = $this->customSegmentRepository->findById($segmentId);
            if (!$segment) {
                return [
                    'status' => 'error',
                    'message' => 'Segment not found'
                ];
            }

            // Get the results
            $results = $this->smsService->sendSMSToSegment($segmentId, $message);

            // Count successful and failed sends
            $successful = count(array_filter($results, function ($result) {
                return $result['status'] === 'success';
            }));
            $failed = count(array_filter($results, function ($result) {
                return $result['status'] === 'error';
            }));

            return [
                'status' => 'success',
                'segment' => [
                    'id' => $segment->getId(),
                    'name' => $segment->getName()
                ],
                'results' => $results,
                'summary' => [
                    'total' => count($results),
                    'successful' => $successful,
                    'failed' => $failed
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get all segments with phone number counts
     * 
     * @return array
     */
    public function getSegmentsForSMS(): array
    {
        try {
            $segments = $this->customSegmentRepository->findAll();
            $result = [];

            foreach ($segments as $segment) {
                $phoneNumbers = $this->phoneNumberRepository->findByCustomSegment($segment->getId());
                $result[] = [
                    'id' => $segment->getId(),
                    'name' => $segment->getName(),
                    'description' => $segment->getDescription(),
                    'phoneNumberCount' => count($phoneNumbers)
                ];
            }

            return [
                'status' => 'success',
                'segments' => $result
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
