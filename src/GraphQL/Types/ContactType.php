<?php

namespace App\GraphQL\Types;

use App\Models\Contact;
use App\Repositories\Interfaces\SMSHistoryRepositoryInterface;
use Psr\Log\LoggerInterface;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;
use Exception;

/**
 * @Type(class=Contact::class)
 */
class ContactType
{
    private SMSHistoryRepositoryInterface $smsHistoryRepository;
    private LoggerInterface $logger;
    
    public function __construct(
        SMSHistoryRepositoryInterface $smsHistoryRepository,
        LoggerInterface $logger
    ) {
        $this->smsHistoryRepository = $smsHistoryRepository;
        $this->logger = $logger;
    }
    /**
     * @Field
     */
    public function getId(Contact $contact): ID
    {
        return new ID($contact->getId());
    }

    /**
     * @Field
     */
    public function getUserId(Contact $contact): ID
    {
        return new ID($contact->getUserId());
    }

    /**
     * @Field
     */
    public function getName(Contact $contact): string
    {
        return $contact->getName();
    }

    /**
     * @Field
     */
    public function getPhoneNumber(Contact $contact): string
    {
        return $contact->getPhoneNumber();
    }

    /**
     * @Field
     */
    public function getEmail(Contact $contact): ?string
    {
        return $contact->getEmail();
    }

    /**
     * @Field
     */
    public function getNotes(Contact $contact): ?string
    {
        return $contact->getNotes();
    }

    /**
     * @Field
     */
    public function getCreatedAt(Contact $contact): string
    {
        return $contact->getCreatedAt();
    }

    /**
     * @Field
     */
    public function getUpdatedAt(Contact $contact): string
    {
        return $contact->getUpdatedAt();
    }

    /**
     * @Field
     */
    public function getGroups(Contact $contact): array
    {
        // Cette méthode nécessite d'injecter le repository des groupes
        // Pour l'instant, on retourne un tableau vide
        // Dans une implémentation complète, on utiliserait le DI container
        return [];
    }
    
    /**
     * @Field
     */
    public function smsHistory(Contact $contact, ?int $limit = 10): array
    {
        try {
            return $this->smsHistoryRepository->findByPhoneNumber($contact->getPhoneNumber(), $limit);
        } catch (Exception $e) {
            $this->logger->error('Error fetching SMS history for contact: ' . $e->getMessage(), ['contactId' => $contact->getId()]);
            return [];
        }
    }

    /**
     * @Field
     */
    public function smsTotalCount(Contact $contact): int
    {
        if (!$contact || !$contact->getPhoneNumber()) {
            $this->logger->info('Empty phone number for contact ID: ' . ($contact ? $contact->getId() : 'null') . ', returning 0');
            return 0; // Return 0 for new contacts or when phoneNumber is empty
        }
        
        try {
            $phoneNumber = $contact->getPhoneNumber();
            $this->logger->info('Counting SMS for phone number: ' . $phoneNumber);
            
            $count = $this->smsHistoryRepository->countByPhoneNumber($phoneNumber);
            
            // Handle null result explicitly
            if ($count === null) {
                $this->logger->warning('countByPhoneNumber returned NULL for ' . $phoneNumber);
                return 0;
            }
            
            // Always cast to int to ensure correct type
            $countInt = (int)$count;
            $this->logger->info('SMS count for phone number ' . $phoneNumber . ': ' . $countInt);
            return $countInt;
        } catch (Exception $e) {
            $this->logger->error('Error counting SMS for contact: ' . $e->getMessage(), [
                'contactId' => $contact->getId(),
                'phoneNumber' => $contact->getPhoneNumber(),
                'exception' => $e->getTraceAsString()
            ]);
            return 0;
        }
    }
    
    /**
     * @Field
     */
    public function smsSentCount(Contact $contact): int
    {
        if (!$contact || !$contact->getPhoneNumber()) {
            return 0;
        }
        
        try {
            $count = $this->smsHistoryRepository->countByPhoneNumberAndStatus($contact->getPhoneNumber(), 'SENT');
            return $count ?? 0;
        } catch (Exception $e) {
            $this->logger->error('Error counting sent SMS for contact: ' . $e->getMessage(), ['contactId' => $contact->getId()]);
            return 0;
        }
    }
    
    /**
     * @Field
     */
    public function smsFailedCount(Contact $contact): int
    {
        if (!$contact || !$contact->getPhoneNumber()) {
            return 0;
        }
        
        try {
            $count = $this->smsHistoryRepository->countByPhoneNumberAndStatus($contact->getPhoneNumber(), 'FAILED');
            return $count ?? 0;
        } catch (Exception $e) {
            $this->logger->error('Error counting failed SMS for contact: ' . $e->getMessage(), ['contactId' => $contact->getId()]);
            return 0;
        }
    }
    
    /**
     * @Field
     */
    public function smsScore(Contact $contact): float
    {
        if (!$contact || !$contact->getPhoneNumber()) {
            return 0.0;
        }
        
        try {
            $total = $this->smsHistoryRepository->countByPhoneNumber($contact->getPhoneNumber());
            
            if ($total === 0) {
                return 0.0; // Avoid division by zero
            }
            
            $sent = $this->smsHistoryRepository->countByPhoneNumberAndStatus($contact->getPhoneNumber(), 'SENT');
            $score = round(($sent / $total) * 100, 2) / 100; // Normalize to 0-1 range
            
            return $score;
        } catch (Exception $e) {
            $this->logger->error('Error calculating SMS score for contact: ' . $e->getMessage(), ['contactId' => $contact->getId()]);
            return 0.0;
        }
    }
}
