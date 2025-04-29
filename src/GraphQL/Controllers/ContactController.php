<?php

namespace App\GraphQL\Controllers;

use App\Entities\Contact; // Use Doctrine Entity
use App\Repositories\Interfaces\ContactRepositoryInterface; // Use Interface
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Right;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use App\Models\User;
use Exception;
use Psr\Log\LoggerInterface;

class ContactController
{
    private ContactRepositoryInterface $contactRepository; // Use Interface
    private LoggerInterface $logger;

    public function __construct(ContactRepositoryInterface $contactRepository, LoggerInterface $logger) // Use Interface
    {
        $this->contactRepository = $contactRepository;
        $this->logger = $logger;
    }

    /**
     * @Query
     * @Logged
     * @Right("ROLE_USER")
     */
    public function contacts(?int $limit = 100, ?int $offset = 0, ?string $search = null, ?int $groupId = null, #[InjectUser] User $user): array
    {
        try {
            // If groupId is provided, return contacts in group
            if ($groupId !== null) {
                return $this->contactRepository->findByUserIdAndGroupId($user->getId(), $groupId, $limit, $offset);
            }
            
            // If search is provided, filter by search term
            if ($search !== null && $search !== '') {
                return $this->contactRepository->searchByUserId($search, $user->getId(), $limit, $offset);
            }
            
            // Default: return all user contacts
            return $this->contactRepository->findByUserId($user->getId(), $limit, $offset);
        } catch (Exception $e) {
            $this->logger->error('Error fetching contacts: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @Query
     * @Logged
     * @Right("ROLE_USER")
     * @return Contact|null
     */
    public function contact(int $id, #[InjectUser] User $user): ?Contact // Return type hint is Entity
    {
        try {
            $contact = $this->contactRepository->findById($id); // findById is available via DoctrineRepositoryInterface

            // Vérifier que le contact appartient à l'utilisateur
            if ($contact && $contact->getUserId() === $user->getId()) {
                return $contact;
            }

            return null;
        } catch (Exception $e) {
            $this->logger->error('Error fetching contact: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @Query
     * @Logged
     * @Right("ROLE_USER")
     */
    public function searchContacts(string $query, ?int $limit = 100, ?int $offset = 0, #[InjectUser] User $user): array
    {
        try {
            return $this->contactRepository->searchByUserId($query, $user->getId(), $limit, $offset);
        } catch (Exception $e) {
            $this->logger->error('Error searching contacts: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @Mutation
     * @Logged
     * @Right("ROLE_USER")
     * @return Contact
     */
    public function createContact(
        string $name,
        string $phoneNumber,
        ?string $email = null,
        ?string $notes = null,
        ?array $groupIds = null,
        #[InjectUser] User $user
    ): Contact {
        try {
            // Use the Entity constructor or setters
            $contact = new Contact();
            $contact->setUserId($user->getId());
            $contact->setName($name);
            $contact->setPhoneNumber($phoneNumber);
            $contact->setEmail($email);
            $contact->setNotes($notes);
            // createdAt and updatedAt are handled by Doctrine Lifecycle Callbacks if configured, or set manually

            $this->contactRepository->save($contact); // Use save instead of create
            
            // Handle group assignments if provided
            if ($groupIds && !empty($groupIds)) {
                $this->contactRepository->assignContactToGroups($contact->getId(), $groupIds);
            }
            
            // Assuming save modifies the entity with the ID
            return $contact;
        } catch (Exception $e) {
            $this->logger->error('Error creating contact: ' . $e->getMessage(), [
                'name' => $name,
                'phoneNumber' => $phoneNumber,
                'userId' => $user->getId(),
                'exception' => $e
            ]);
            throw $e; // Re-throw to let GraphQL handle the error properly
        }
    }

    /**
     * @Mutation
     * @Logged
     * @Right("ROLE_USER")
     * @return Contact|null
     */
    public function updateContact(
        int $id,
        string $name,
        string $phoneNumber,
        ?string $email = null,
        ?string $notes = null,
        ?array $groupIds = null,
        #[InjectUser] User $user
    ): ?Contact { // Return type hint is Entity
        try {
            // Récupérer le contact existant
            $existingContact = $this->contactRepository->findById($id); // findById is available

            // Vérifier que le contact existe et appartient à l'utilisateur
            if (!$existingContact || $existingContact->getUserId() !== $user->getId()) {
                $this->logger->warning('Attempt to update contact not owned by user or not found', ['contactId' => $id, 'userId' => $user->getId()]);
                return null;
            }

            // Mettre à jour l'entité existante
            $existingContact->setName($name);
            $existingContact->setPhoneNumber($phoneNumber);
            $existingContact->setEmail($email);
            $existingContact->setNotes($notes);
            // updatedAt should be handled by Doctrine Lifecycle Callbacks if configured

            $this->contactRepository->save($existingContact); // Use save instead of update
            
            // Handle group assignments if provided
            if ($groupIds !== null) {
                $this->contactRepository->updateContactGroups($id, $groupIds);
            }
            
            return $existingContact;
        } catch (Exception $e) {
            $this->logger->error('Error updating contact: ' . $e->getMessage(), ['contactId' => $id]);
            return null;
        }
    }

    /**
     * @Mutation
     * @Logged
     * @Right("ROLE_USER")
     */
    public function deleteContact(int $id, #[InjectUser] User $user): bool
    {
        try {
            // Récupérer le contact existant
            $existingContact = $this->contactRepository->findById($id); // findById is available

            // Vérifier que le contact existe et appartient à l'utilisateur
            if (!$existingContact || $existingContact->getUserId() !== $user->getId()) {
                $this->logger->warning('Attempt to delete contact not owned by user or not found', ['contactId' => $id, 'userId' => $user->getId()]);
                return false;
            }

            return $this->contactRepository->delete($existingContact); // Use delete instead of remove
        } catch (Exception $e) {
            $this->logger->error('Error deleting contact: ' . $e->getMessage(), ['contactId' => $id]);
            return false;
        }
    }

    /**
     * @Query
     * @Logged
     * @Right("ROLE_ADMIN")
     */
    public function allContacts(?int $limit = 100, ?int $offset = 0): array
    {
        try {
            return $this->contactRepository->findAll($limit, $offset);
        } catch (Exception $e) {
            $this->logger->error('Error fetching all contacts: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @Query
     * @Logged
     * @Right("ROLE_ADMIN")
     */
    public function userContacts(int $userId, ?int $limit = 100, ?int $offset = 0): array
    {
        try {
            return $this->contactRepository->findByUserId($userId, $limit, $offset);
        } catch (Exception $e) {
            $this->logger->error('Error fetching user contacts: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * @Query
     * @Logged
     * @Right("ROLE_USER")
     */
    public function contactsCount(?string $search = null, ?int $groupId = null, #[InjectUser] User $user): int
    {
        try {
            // If groupId is provided, count contacts in group
            if ($groupId !== null) {
                return $this->contactRepository->countByUserIdAndGroupId($user->getId(), $groupId);
            }
            
            // If search is provided, count filtered results
            if ($search !== null && $search !== '') {
                return $this->contactRepository->countSearchByUserId($search, $user->getId());
            }
            
            // Default: count all user contacts
            return $this->contactRepository->countByUserId($user->getId());
        } catch (Exception $e) {
            $this->logger->error('Error counting contacts: ' . $e->getMessage());
            return 0;
        }
    }
}