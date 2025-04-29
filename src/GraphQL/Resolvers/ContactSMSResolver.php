<?php

namespace App\GraphQL\Resolvers;

use App\Entities\Contact;
use App\Repositories\Interfaces\SMSHistoryRepositoryInterface;
use App\Services\Interfaces\PhoneNumberNormalizerInterface;
use Psr\Log\LoggerInterface;

/**
 * Resolver specifically for Contact-SMS related fields
 * 
 * This resolver handles the SMS-related fields on the Contact type, 
 * using a uniform approach with Webonyx resolver pattern.
 */
class ContactSMSResolver
{
    private SMSHistoryRepositoryInterface $smsHistoryRepository;
    private LoggerInterface $logger;
    private PhoneNumberNormalizerInterface $phoneNumberNormalizer;
    
    /**
     * Constructor
     * 
     * @param SMSHistoryRepositoryInterface $smsHistoryRepository SMS History repository
     * @param LoggerInterface $logger Logger interface
     * @param PhoneNumberNormalizerInterface $phoneNumberNormalizer Phone number normalizer
     */
    public function __construct(
        SMSHistoryRepositoryInterface $smsHistoryRepository,
        LoggerInterface $logger,
        PhoneNumberNormalizerInterface $phoneNumberNormalizer
    ) {
        $this->smsHistoryRepository = $smsHistoryRepository;
        $this->logger = $logger;
        $this->phoneNumberNormalizer = $phoneNumberNormalizer;
    }
    
    /**
     * Resolve SMS history for a contact
     * 
     * @param array|Contact $contact The contact entity or array
     * @param array $args Arguments containing limit and offset
     * @return array SMS history entries
     */
    public function resolveSmsHistory($contact, array $args = []): array
    {
        $phoneNumber = is_array($contact) ? ($contact['phoneNumber'] ?? '') : $contact->getPhoneNumber();
        $contactId = is_array($contact) ? ($contact['id'] ?? 'unknown') : $contact->getId();
        $this->logger->info('Resolving SMS history for contact ID: ' . $contactId . ', phone: ' . $phoneNumber);
        
        if (empty($phoneNumber)) {
            return [];
        }
        
        try {
            // Extract arguments
            $limit = isset($args['limit']) ? (int)$args['limit'] : 10;
            $offset = isset($args['offset']) ? (int)$args['offset'] : 0;
            
            // Normalize phone number
            $normalizedNumber = $this->phoneNumberNormalizer->normalize($phoneNumber);
            
            // Fetch SMS history
            $history = $this->smsHistoryRepository->findByPhoneNumber($normalizedNumber, $limit, $offset);
            
            // Convert to array representation for GraphQL
            $result = [];
            foreach ($history as $sms) {
                $result[] = [
                    'id' => $sms->getId(),
                    'phoneNumber' => $sms->getPhoneNumber(),
                    'message' => $sms->getMessage(),
                    'status' => $sms->getStatus(),
                    'messageId' => $sms->getMessageId(),
                    'errorMessage' => $sms->getErrorMessage(),
                    'senderAddress' => $sms->getSenderAddress(),
                    'senderName' => $sms->getSenderName(),
                    'createdAt' => $sms->getCreatedAt()->format('Y-m-d H:i:s'),
                    'sentAt' => $sms->getSentAt() ? $sms->getSentAt()->format('Y-m-d H:i:s') : null,
                    'deliveredAt' => $sms->getDeliveredAt() ? $sms->getDeliveredAt()->format('Y-m-d H:i:s') : null,
                    'failedAt' => $sms->getFailedAt() ? $sms->getFailedAt()->format('Y-m-d H:i:s') : null,
                    'userId' => $sms->getUserId()
                ];
            }
            
            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Error resolving SMS history for contact: ' . $e->getMessage(), [
                'contactId' => $contactId,
                'exception' => $e
            ]);
            return [];
        }
    }
    
    /**
     * Resolve total SMS count for a contact
     * 
     * @param array|Contact $contact The contact entity or array
     * @return int The total SMS count
     */
    public function resolveSmsTotalCount($contact): int
    {
        $phoneNumber = is_array($contact) ? ($contact['phoneNumber'] ?? '') : $contact->getPhoneNumber();
        $contactId = is_array($contact) ? ($contact['id'] ?? 'unknown') : $contact->getId();
        $this->logger->info('Resolving SMS total count for contact ID: ' . $contactId . ', phone: ' . $phoneNumber);
        
        if (empty($phoneNumber)) {
            return 0;
        }
        
        try {
            // Normalize phone number
            $normalizedNumber = $this->phoneNumberNormalizer->normalize($phoneNumber);
            
            // Count SMS
            $count = $this->smsHistoryRepository->countByPhoneNumber($normalizedNumber);
            
            // Always return an integer, never null
            return $count ?? 0;
        } catch (\Throwable $e) {
            $this->logger->error('Error resolving SMS total count for contact: ' . $e->getMessage(), [
                'contactId' => $contactId,
                'exception' => $e
            ]);
            return 0;
        }
    }
    
    /**
     * Resolve sent SMS count for a contact
     * 
     * @param array|Contact $contact The contact entity or array
     * @return int The sent SMS count
     */
    public function resolveSmsSentCount($contact): int
    {
        $phoneNumber = is_array($contact) ? ($contact['phoneNumber'] ?? '') : $contact->getPhoneNumber();
        $contactId = is_array($contact) ? ($contact['id'] ?? 'unknown') : $contact->getId();
        $this->logger->info('Resolving SMS sent count for contact ID: ' . $contactId . ', phone: ' . $phoneNumber);
        
        if (empty($phoneNumber)) {
            return 0;
        }
        
        try {
            // Normalize phone number
            $normalizedNumber = $this->phoneNumberNormalizer->normalize($phoneNumber);
            
            // Count SMS with status SENT
            $count = $this->smsHistoryRepository->countByPhoneNumberAndStatus($normalizedNumber, 'SENT');
            
            // Always return an integer, never null
            return $count ?? 0;
        } catch (\Throwable $e) {
            $this->logger->error('Error resolving SMS sent count for contact: ' . $e->getMessage(), [
                'contactId' => $contactId,
                'exception' => $e
            ]);
            return 0;
        }
    }
    
    /**
     * Resolve failed SMS count for a contact
     * 
     * @param array|Contact $contact The contact entity or array
     * @return int The failed SMS count
     */
    public function resolveSmsFailedCount($contact): int
    {
        $phoneNumber = is_array($contact) ? ($contact['phoneNumber'] ?? '') : $contact->getPhoneNumber();
        $contactId = is_array($contact) ? ($contact['id'] ?? 'unknown') : $contact->getId();
        $this->logger->info('Resolving SMS failed count for contact ID: ' . $contactId . ', phone: ' . $phoneNumber);
        
        if (empty($phoneNumber)) {
            return 0;
        }
        
        try {
            // Normalize phone number
            $normalizedNumber = $this->phoneNumberNormalizer->normalize($phoneNumber);
            
            // Count SMS with status FAILED
            $count = $this->smsHistoryRepository->countByPhoneNumberAndStatus($normalizedNumber, 'FAILED');
            
            // Always return an integer, never null
            return $count ?? 0;
        } catch (\Throwable $e) {
            $this->logger->error('Error resolving SMS failed count for contact: ' . $e->getMessage(), [
                'contactId' => $contactId,
                'exception' => $e
            ]);
            return 0;
        }
    }
    
    /**
     * Resolve SMS score for a contact
     * 
     * @param array|Contact $contact The contact entity or array
     * @return float The SMS score (success rate)
     */
    public function resolveSmsScore($contact): float
    {
        $phoneNumber = is_array($contact) ? ($contact['phoneNumber'] ?? '') : $contact->getPhoneNumber();
        $contactId = is_array($contact) ? ($contact['id'] ?? 'unknown') : $contact->getId();
        $this->logger->info('Resolving SMS score for contact ID: ' . $contactId . ', phone: ' . $phoneNumber);
        
        if (empty($phoneNumber)) {
            return 0.0;
        }
        
        try {
            // Normalize phone number
            $normalizedNumber = $this->phoneNumberNormalizer->normalize($phoneNumber);
            
            // Get counts
            $total = $this->smsHistoryRepository->countByPhoneNumber($normalizedNumber);
            
            if ($total === 0) {
                return 0.0;
            }
            
            $sent = $this->smsHistoryRepository->countByPhoneNumberAndStatus($normalizedNumber, 'SENT');
            
            // Calculate score as percentage of successful SMS
            $score = round(($sent / $total), 2);
            
            return $score;
        } catch (\Throwable $e) {
            $this->logger->error('Error resolving SMS score for contact: ' . $e->getMessage(), [
                'contactId' => $contactId,
                'exception' => $e
            ]);
            return 0.0;
        }
    }
}