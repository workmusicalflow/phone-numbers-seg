<?php

namespace App\GraphQL\Controllers;

use App\Entities\ContactGroup; // Use Doctrine Entity
use App\Entities\User; // Use Doctrine Entity
use App\Repositories\Interfaces\ContactGroupRepositoryInterface; // Use Interface
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Right;
// Removed use App\Models\User; as App\Entities\User is imported now
use Exception;
use Psr\Log\LoggerInterface;

class ContactGroupController
{
    private ContactGroupRepositoryInterface $contactGroupRepository; // Use Interface
    private LoggerInterface $logger;

    public function __construct(ContactGroupRepositoryInterface $contactGroupRepository, LoggerInterface $logger) // Use Interface
    {
        $this->contactGroupRepository = $contactGroupRepository;
        $this->logger = $logger;
    }

    /**
     * @Query
     * @Logged
     * @Right("ROLE_USER")
     * @return ContactGroup[]
     */
    public function contactGroups(?int $limit = 100, ?int $offset = 0, User $user): array // Use Doctrine Entity for User param
    {
        try {
            return $this->contactGroupRepository->findByUserId($user->getId(), $limit, $offset);
        } catch (Exception $e) {
            $this->logger->error('Error fetching contact groups: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @Query
     * @Logged
     * @Right("ROLE_USER")
     * @return ?ContactGroup
     */
    public function contactGroup(int $id, User $user): ?ContactGroup // Use Doctrine Entity for User param and return type
    {
        try {
            $group = $this->contactGroupRepository->findById($id);

            // Vérifier que le groupe appartient à l'utilisateur
            if ($group && $group->getUserId() === $user->getId()) {
                return $group;
            }

            return null;
        } catch (Exception $e) {
            $this->logger->error('Error fetching contact group: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @Query
     * @Logged
     * @Right("ROLE_USER")
     * @return ContactGroup[]
     */
    public function searchContactGroups(string $query, ?int $limit = 100, ?int $offset = 0, User $user): array // Use Doctrine Entity for User param
    {
        try {
            return $this->contactGroupRepository->searchByUserId($query, $user->getId(), $limit, $offset);
        } catch (Exception $e) {
            $this->logger->error('Error searching contact groups: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @Mutation
     * @Logged
     * @Right("ROLE_USER")
     */
    public function createContactGroup(
        string $name,
        ?string $description = null,
        User $user // Use Doctrine Entity for User param
    ): ?ContactGroup { // Return Doctrine Entity
        try {
            // Instantiate Doctrine Entity and use setters
            $group = new ContactGroup();
            $group->setUserId($user->getId());
            $group->setName($name);
            $group->setDescription($description);
            // createdAt/updatedAt likely handled by Doctrine lifecycle callbacks or BaseRepository->save

            return $this->contactGroupRepository->save($group); // Use save method
        } catch (Exception $e) {
            $this->logger->error('Error creating contact group: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @Mutation
     * @Logged
     * @Right("ROLE_USER")
     */
    public function updateContactGroup(
        int $id,
        string $name,
        ?string $description = null,
        User $user // Use Doctrine Entity for User param
    ): ?ContactGroup { // Return Doctrine Entity
        try {
            // Récupérer le groupe existant
            $existingGroup = $this->contactGroupRepository->findById($id);

            // Vérifier que le groupe existe et appartient à l'utilisateur
            if (!$existingGroup || $existingGroup->getUserId() !== $user->getId()) {
                return null;
            }

            // Update existing entity
            $existingGroup->setName($name);
            $existingGroup->setDescription($description);
            // updatedAt likely handled by Doctrine lifecycle callbacks or BaseRepository->save

            return $this->contactGroupRepository->save($existingGroup); // Use save method
        } catch (Exception $e) {
            $this->logger->error('Error updating contact group: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @Mutation
     * @Logged
     * @Right("ROLE_USER")
     */
    public function deleteContactGroup(int $id, User $user): bool // Use Doctrine Entity for User param
    {
        try {
            // Récupérer le groupe existant
            $existingGroup = $this->contactGroupRepository->findById($id);

            // Vérifier que le groupe existe et appartient à l'utilisateur
            if (!$existingGroup || $existingGroup->getUserId() !== $user->getId()) {
                return false;
            }

            // Pass the entity object to delete
            return $this->contactGroupRepository->delete($existingGroup);
        } catch (Exception $e) {
            $this->logger->error('Error deleting contact group: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @Mutation
     * @Logged
     * @Right("ROLE_USER")
     */
    public function addContactToGroup(int $contactId, int $groupId, User $user): bool // Use Doctrine Entity for User param
    {
        try {
            // Vérifier que le groupe appartient à l'utilisateur
            $group = $this->contactGroupRepository->findById($groupId);
            if (!$group || $group->getUserId() !== $user->getId()) {
                return false;
            }

            return $this->contactGroupRepository->addContactToGroup($contactId, $groupId);
        } catch (Exception $e) {
            $this->logger->error('Error adding contact to group: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @Mutation
     * @Logged
     * @Right("ROLE_USER")
     */
    public function removeContactFromGroup(int $contactId, int $groupId, User $user): bool // Use Doctrine Entity for User param
    {
        try {
            // Vérifier que le groupe appartient à l'utilisateur
            $group = $this->contactGroupRepository->findById($groupId);
            if (!$group || $group->getUserId() !== $user->getId()) {
                return false;
            }

            return $this->contactGroupRepository->removeContactFromGroup($contactId, $groupId);
        } catch (Exception $e) {
            $this->logger->error('Error removing contact from group: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @Query
     * @Logged
     * @Right("ROLE_ADMIN")
     * @return ContactGroup[]
     */
    public function allContactGroups(?int $limit = 100, ?int $offset = 0): array
    {
        try {
            return $this->contactGroupRepository->findAll($limit, $offset);
        } catch (Exception $e) {
            $this->logger->error('Error fetching all contact groups: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @Query
     * @Logged
     * @Right("ROLE_ADMIN")
     * @return ContactGroup[]
     */
    public function userContactGroups(int $userId, ?int $limit = 100, ?int $offset = 0): array
    {
        try {
            return $this->contactGroupRepository->findByUserId($userId, $limit, $offset);
        } catch (Exception $e) {
            $this->logger->error('Error fetching user contact groups: ' . $e->getMessage());
            return [];
        }
    }
}
