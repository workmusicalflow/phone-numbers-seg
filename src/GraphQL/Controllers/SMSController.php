<?php

namespace App\GraphQL\Controllers;

use App\Services\Interfaces\SMSBusinessServiceInterface;
use App\Services\Interfaces\SMSValidationServiceInterface;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * GraphQL controller for SMS operations
 */
class SMSController
{
    /**
     * @var SMSBusinessServiceInterface
     */
    private SMSBusinessServiceInterface $smsBusinessService;

    /**
     * @var SMSValidationServiceInterface
     */
    private SMSValidationServiceInterface $validationService;

    /**
     * Constructor
     * 
     * @param SMSBusinessServiceInterface $smsBusinessService
     * @param SMSValidationServiceInterface $validationService
     */
    public function __construct(
        SMSBusinessServiceInterface $smsBusinessService,
        SMSValidationServiceInterface $validationService
    ) {
        $this->smsBusinessService = $smsBusinessService;
        $this->validationService = $validationService;
    }

    /**
     * Get segments available for SMS
     * 
     * @Query
     * @return array
     */
    public function segmentsForSMS(): array
    {
        try {
            return $this->smsBusinessService->getSegmentsForSMS();
        } catch (\Exception $e) {
            // En cas d'erreur, retourner un tableau vide plutôt que de laisser l'exception se propager
            error_log('Error in segmentsForSMS: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get SMS history
     * 
     * @Query
     * @return array
     */
    public function smsHistory(int $limit = 100, int $offset = 0, ?string $search = null, ?string $status = null, ?int $segmentId = null): array
    {
        try {
            // If search is provided, normalize the phone number
            if ($search !== null && !empty($search)) {
                $search = $this->validationService->convertToInternationalFormat($search);
            }

            $history = $this->smsBusinessService->getSMSHistory($limit, $offset, $search, $status, $segmentId);

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
        } catch (\Exception $e) {
            // En cas d'erreur, retourner un tableau vide plutôt que de laisser l'exception se propager
            error_log('Error in smsHistory: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get SMS history count
     * 
     * @Query
     * @return int
     */
    public function smsHistoryCount(): int
    {
        try {
            return $this->smsBusinessService->getSMSHistoryCount();
        } catch (\Exception $e) {
            error_log('Error in smsHistoryCount: ' . $e->getMessage());
            return 0;
        }
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
            $result = $this->smsBusinessService->sendSMS($phoneNumber, $message);

            // Format the response
            return [
                'id' => uniqid(), // In a real implementation, this would be a database ID
                'phoneNumber' => $phoneNumber,
                'message' => $message,
                'status' => $result['success'] ? 'SENT' : 'FAILED',
                'createdAt' => date('c')
            ];
        } catch (\Exception $e) {
            error_log('Error in sendSms: ' . $e->getMessage());
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
            $result = $this->smsBusinessService->sendBulkSMS($phoneNumbers, $message);

            // Format the results
            $formattedResults = [];
            if (isset($result['details']) && is_array($result['details'])) {
                foreach ($result['details'] as $detail) {
                    $formattedResults[] = [
                        'phoneNumber' => $detail['number'] ?? '',
                        'status' => $detail['success'] ? 'success' : 'error',
                        'message' => $detail['message'] ?? ($detail['success'] ? 'SMS envoyé avec succès' : 'Échec de l\'envoi')
                    ];
                }
            }

            return [
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'] ?? 'Envoi en masse terminé',
                'summary' => $result['summary'] ?? [
                    'total' => count($phoneNumbers),
                    'successful' => 0,
                    'failed' => 0
                ],
                'results' => $formattedResults
            ];
        } catch (\Exception $e) {
            error_log('Error in sendBulkSms: ' . $e->getMessage());
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
            $result = $this->smsBusinessService->sendSMSToSegment((int)$segmentId, $message);

            // Format the results
            $formattedResults = [];
            if (isset($result['details']) && is_array($result['details'])) {
                foreach ($result['details'] as $detail) {
                    $formattedResults[] = [
                        'phoneNumber' => $detail['number'] ?? '',
                        'status' => $detail['success'] ? 'success' : 'error',
                        'message' => $detail['message'] ?? ($detail['success'] ? 'SMS envoyé avec succès' : 'Échec de l\'envoi')
                    ];
                }
            }

            return [
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'] ?? 'Envoi au segment terminé',
                'segment' => $result['segment'] ?? ['id' => (int)$segmentId],
                'summary' => $result['summary'] ?? [
                    'total' => 0,
                    'successful' => 0,
                    'failed' => 0
                ],
                'results' => $formattedResults
            ];
        } catch (\Exception $e) {
            error_log('Error in sendSmsToSegment: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Erreur lors de l\'envoi au segment: ' . $e->getMessage(),
                'segment' => ['id' => (int)$segmentId],
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
