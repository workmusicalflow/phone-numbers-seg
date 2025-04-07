<?php

namespace App\GraphQL\Controllers;

use App\Models\ContactGroup;
use App\Repositories\ContactGroupRepository;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Right;
use App\Models\User;
use Exception;
use Psr\Log\LoggerInterface;

class ContactGroupController
{
    private ContactGroupRepository $contactGroupRepository;
    private LoggerInterface $logger;

    public function __construct(ContactGroupRepository $contactGroupRepository, LoggerInterface $logger)
    {
        $this->contactGroupRepository = $contactGroupRepository;
        $this->logger = $logger;
    }

    /**
     * @Query
     * @Logged
     * @Right("ROLE_USER")
     */
    public function contactGroups(?int $limit = 100, ?int $offset = 0, User $user): array
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
     */
    public function contactGroup(int $id, User $user): ?ContactGroup
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
     */
    public function searchContactGroups(string $query, ?int $limit = 100, ?int $offset = 0, User $user): array
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
        User $user
    ): ?ContactGroup {
        try {
            $group = new ContactGroup(
                0, // ID sera généré par la base de données
                $user->getId(),
                $name,
                $description
            );

            return $this->contactGroupRepository->create($group);
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
        User $user
    ): ?ContactGroup {
        try {
            // Récupérer le groupe existant
            $existingGroup = $this->contactGroupRepository->findById($id);

            // Vérifier que le groupe existe et appartient à l'utilisateur
            if (!$existingGroup || $existingGroup->getUserId() !== $user->getId()) {
                return null;
            }

            // Créer un nouveau groupe avec les données mises à jour
            $updatedGroup = new ContactGroup(
                $id,
                $user->getId(),
                $name,
                $description,
                $existingGroup->getCreatedAt()
            );

            return $this->contactGroupRepository->update($updatedGroup);
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
    public function deleteContactGroup(int $id, User $user): bool
    {
        try {
            // Récupérer le groupe existant
            $existingGroup = $this->contactGroupRepository->findById($id);

            // Vérifier que le groupe existe et appartient à l'utilisateur
            if (!$existingGroup || $existingGroup->getUserId() !== $user->getId()) {
                return false;
            }

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
    public function addContactToGroup(int $contactId, int $groupId, User $user): bool
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
    public function removeContactFromGroup(int $contactId, int $groupId, User $user): bool
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
