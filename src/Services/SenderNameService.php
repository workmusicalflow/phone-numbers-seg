<?php

namespace App\Services;

use App\Entities\SenderName;
use App\Repositories\Interfaces\SenderNameRepositoryInterface; // Use specific interface

/**
 * Service for managing sender names
 */
class SenderNameService
{
    /**
     * @var SenderNameRepositoryInterface // Use specific interface
     */
    private SenderNameRepositoryInterface $senderNameRepository; // Use specific interface

    /**
     * Maximum number of approved sender names per user
     * 
     * @var int
     */
    private $maxApprovedSenderNames = 2;

    /**
     * Constructor
     * 
     * @param SenderNameRepositoryInterface $senderNameRepository // Use specific interface
     */
    public function __construct(SenderNameRepositoryInterface $senderNameRepository) // Use specific interface
    {
        $this->senderNameRepository = $senderNameRepository;
    }

    /**
     * Get all sender names for a user
     * 
     * @param int $userId
     * @return array
     */
    public function getSenderNamesForUser(int $userId): array
    {
        return $this->senderNameRepository->findBy(['userId' => $userId]);
    }

    /**
     * Get approved sender names for a user
     * 
     * @param int $userId
     * @return array
     */
    public function getApprovedSenderNamesForUser(int $userId): array
    {
        return $this->senderNameRepository->findBy([
            'userId' => $userId,
            'status' => 'approved'
        ]);
    }

    /**
     * Check if a user can request a new sender name
     * 
     * @param int $userId
     * @return bool
     */
    public function canRequestSenderName(int $userId): bool
    {
        $approvedSenderNames = $this->getApprovedSenderNamesForUser($userId);
        return count($approvedSenderNames) < $this->maxApprovedSenderNames;
    }

    /**
     * Request a new sender name
     * 
     * @param int $userId
     * @param string $name
     * @return SenderName|null
     */
    public function requestSenderName(int $userId, string $name): ?SenderName
    {
        // Check if the user already has the maximum number of approved sender names
        if (!$this->canRequestSenderName($userId)) {
            return null;
        }

        // Create a new sender name
        $senderName = new SenderName();
        $senderName->setUserId($userId);
        $senderName->setName($name);
        $senderName->setStatus('pending');

        // Save the sender name
        $this->senderNameRepository->save($senderName);

        return $senderName;
    }

    /**
     * Approve a sender name
     * 
     * @param int $senderNameId
     * @return bool
     */
    public function approveSenderName(int $senderNameId): bool
    {
        // Get the sender name
        $senderName = $this->senderNameRepository->findById($senderNameId);
        if (!$senderName) {
            return false;
        }

        // Check if the user already has the maximum number of approved sender names
        $approvedSenderNames = $this->getApprovedSenderNamesForUser($senderName->getUserId());
        if (count($approvedSenderNames) >= $this->maxApprovedSenderNames) {
            return false;
        }

        // Approve the sender name
        $senderName->setStatus('approved');
        $this->senderNameRepository->save($senderName);

        return true;
    }

    /**
     * Reject a sender name
     * 
     * @param int $senderNameId
     * @return bool
     */
    public function rejectSenderName(int $senderNameId): bool
    {
        // Get the sender name
        $senderName = $this->senderNameRepository->findById($senderNameId);
        if (!$senderName) {
            return false;
        }

        // Reject the sender name
        $senderName->setStatus('rejected');
        $this->senderNameRepository->save($senderName);

        return true;
    }

    /**
     * Delete a sender name
     * 
     * @param int $senderNameId
     * @return bool
     */
    public function deleteSenderName(int $senderNameId): bool
    {
        // Get the sender name
        $senderName = $this->senderNameRepository->findById($senderNameId);
        if (!$senderName) {
            return false;
        }

        // Delete the sender name
        $this->senderNameRepository->delete($senderName);

        return true;
    }
}
