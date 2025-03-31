<?php

namespace App\GraphQL\Controllers;

use App\Repositories\PhoneNumberRepository;
use App\Repositories\CustomSegmentRepository;
use App\Repositories\SMSHistoryRepository;
use App\Services\SMSService;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * GraphQL controller for SMS operations
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
     * @var SMSHistoryRepository
     */
    private SMSHistoryRepository $smsHistoryRepository;

    /**
     * Constructor
     * 
     * @param SMSService $smsService
     * @param PhoneNumberRepository $phoneNumberRepository
     * @param CustomSegmentRepository $customSegmentRepository
     * @param SMSHistoryRepository $smsHistoryRepository
     */
    public function __construct(
        SMSService $smsService,
        PhoneNumberRepository $phoneNumberRepository,
        CustomSegmentRepository $customSegmentRepository,
        SMSHistoryRepository $smsHistoryRepository
    ) {
        $this->smsService = $smsService;
        $this->phoneNumberRepository = $phoneNumberRepository;
        $this->customSegmentRepository = $customSegmentRepository;
        $this->smsHistoryRepository = $smsHistoryRepository;
    }

    /**
     * Get segments available for SMS
     * 
     * @Query
     * @return array
     */
    public function segmentsForSMS(): array
    {
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

        return $result;
    }

    /**
     * Get SMS history
     * 
     * @Query
     * @return array
     */
    public function smsHistory(int $limit = 100, int $offset = 0): array
    {
        $history = $this->smsHistoryRepository->findAll($limit, $offset);
        $result = [];

        foreach ($history as $item) {
            $result[] = [
                'id' => $item->getId(),
                'phoneNumber' => $item->getPhoneNumber(),
                'message' => $item->getMessage(),
                'status' => $item->getStatus(),
                'messageId' => $item->getMessageId(),
                'errorMessage' => $item->getErrorMessage(),
                'senderAddress' => $item->getSenderAddress(),
                'senderName' => $item->getSenderName(),
                'createdAt' => $item->getCreatedAt()
            ];
        }

        return $result;
    }

    /**
     * Send an SMS to a single phone number
     * 
     * @Mutation
     * @param string $phoneNumber
     * @param string $message
     * @return array
     */
    public function sendSms(string $phoneNumber, string $message): array
    {
        try {
            $result = $this->smsService->sendSMS($phoneNumber, $message);

            // Format the response
            return [
                'id' => uniqid(), // In a real implementation, this would be a database ID
                'phoneNumber' => $phoneNumber,
                'message' => $message,
                'status' => isset($result['outboundSMSMessageRequest']) ? 'SENT' : 'FAILED',
                'createdAt' => date('c')
            ];
        } catch (\Exception $e) {
            return [
                'id' => uniqid(),
                'phoneNumber' => $phoneNumber,
                'message' => $message,
                'status' => 'FAILED',
                'createdAt' => date('c')
            ];
        }
    }

    /**
     * Send SMS to multiple phone numbers
     * 
     * @Mutation
     * @param string[] $phoneNumbers
     * @param string $message
     * @return array
     */
    public function sendBulkSms(array $phoneNumbers, string $message): array
    {
        try {
            $results = $this->smsService->sendBulkSMS($phoneNumbers, $message);

            // Count successful and failed sends
            $successful = 0;
            $failed = 0;
            $formattedResults = [];

            foreach ($results as $number => $result) {
                if ($result['status'] === 'success') {
                    $successful++;
                } else {
                    $failed++;
                }

                $formattedResults[] = [
                    'phoneNumber' => $number,
                    'status' => $result['status'] === 'success' ? 'success' : 'error',
                    'message' => $result['status'] === 'success' ? 'SMS envoyé avec succès' : $result['message']
                ];
            }

            return [
                'status' => 'success',
                'message' => 'Envoi en masse terminé',
                'summary' => [
                    'total' => count($phoneNumbers),
                    'successful' => $successful,
                    'failed' => $failed
                ],
                'results' => $formattedResults
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur lors de l\'envoi en masse: ' . $e->getMessage(),
                'summary' => [
                    'total' => count($phoneNumbers),
                    'successful' => 0,
                    'failed' => count($phoneNumbers)
                ],
                'results' => []
            ];
        }
    }

    /**
     * Send SMS to all phone numbers in a segment
     * 
     * @Mutation
     * @param ID $segmentId
     * @param string $message
     * @return array
     */
    public function sendSmsToSegment(ID $segmentId, string $message): array
    {
        try {
            // Check if the segment exists
            $segment = $this->customSegmentRepository->findById((int)$segmentId);
            if (!$segment) {
                return [
                    'status' => 'error',
                    'message' => 'Segment non trouvé',
                    'segment' => null,
                    'summary' => [
                        'total' => 0,
                        'successful' => 0,
                        'failed' => 0
                    ],
                    'results' => []
                ];
            }

            // Send the SMS
            $results = $this->smsService->sendSMSToSegment((int)$segmentId, $message);

            // Count successful and failed sends
            $successful = 0;
            $failed = 0;
            $formattedResults = [];

            foreach ($results as $number => $result) {
                if ($result['status'] === 'success') {
                    $successful++;
                } else {
                    $failed++;
                }

                $formattedResults[] = [
                    'phoneNumber' => $number,
                    'status' => $result['status'] === 'success' ? 'success' : 'error',
                    'message' => $result['status'] === 'success' ? 'SMS envoyé avec succès' : $result['message']
                ];
            }

            return [
                'status' => 'success',
                'message' => 'Envoi au segment terminé',
                'segment' => [
                    'id' => $segment->getId(),
                    'name' => $segment->getName()
                ],
                'summary' => [
                    'total' => count($results),
                    'successful' => $successful,
                    'failed' => $failed
                ],
                'results' => $formattedResults
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur lors de l\'envoi au segment: ' . $e->getMessage(),
                'segment' => null,
                'summary' => [
                    'total' => 0,
                    'successful' => 0,
                    'failed' => 0
                ],
                'results' => []
            ];
        }
    }
}
