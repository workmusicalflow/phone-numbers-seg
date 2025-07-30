<?php

namespace App\GraphQL\Resolvers;

use App\Repositories\ContactGroupRepository;
use App\Models\ContactGroup;
use Psr\Log\LoggerInterface;
use Exception;

class ContactGroupResolver
{
    private ContactGroupRepository $contactGroupRepository;
    private LoggerInterface $logger;

    public function __construct(
        ContactGroupRepository $contactGroupRepository,
        LoggerInterface $logger
    ) {
        $this->contactGroupRepository = $contactGroupRepository;
        $this->logger = $logger;
    }

    /**
     * Résoudre la requête contactGroups
     */
    public function resolveContactGroups(array $args, $context): array
    {
        try {
            // Vérifier l'authentification
            if (!isset($_SESSION['user_id'])) {
                throw new Exception("Authentication required");
            }

            $userId = $_SESSION['user_id'];
            $limit = $args['limit'] ?? 100;
            $offset = $args['offset'] ?? 0;

            $this->logger->info("Fetching contact groups for user {$userId}", [
                'limit' => $limit,
                'offset' => $offset
            ]);

            $groups = $this->contactGroupRepository->findByUserId($userId, $limit, $offset);

            // Convertir les objets ContactGroup en tableaux pour GraphQL
            $groupsArray = array_map(function($group) {
                return [
                    'id' => (string)$group->getId(),
                    'name' => $group->getName(),
                    'description' => $group->getDescription(),
                    'userId' => (string)$group->getUserId(),
                    'createdAt' => $group->getCreatedAt(),
                    'updatedAt' => $group->getUpdatedAt()
                ];
            }, $groups);

            return $groupsArray;
        } catch (Exception $e) {
            $this->logger->error('Error fetching contact groups: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Résoudre la requête contactGroup (par ID)
     */
    public function resolveContactGroup(array $args, $context): ?array
    {
        try {
            // Vérifier l'authentification
            if (!isset($_SESSION['user_id'])) {
                throw new Exception("Authentication required");
            }

            $userId = $_SESSION['user_id'];
            $groupId = (int)$args['id'];

            $this->logger->info("Fetching contact group {$groupId} for user {$userId}");

            $group = $this->contactGroupRepository->findById($groupId);
            
            if ($group === null) {
                return null;
            }

            // Vérifier que le groupe appartient à l'utilisateur connecté
            if ($group->getUserId() !== $userId) {
                $this->logger->warning("User {$userId} attempted to access group {$groupId} belonging to user {$group->getUserId()}");
                throw new Exception("Access denied to this contact group");
            }

            // Convertir l'objet en tableau pour GraphQL
            return [
                'id' => (string)$group->getId(),
                'name' => $group->getName(),
                'description' => $group->getDescription(),
                'userId' => (string)$group->getUserId(),
                'createdAt' => $group->getCreatedAt(),
                'updatedAt' => $group->getUpdatedAt()
            ];
        } catch (Exception $e) {
            $this->logger->error('Error fetching contact group: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Résoudre la mutation createContactGroup
     */
    public function mutateCreateContactGroup(array $args, $context): array
    {
        try {
            // Vérifier l'authentification
            if (!isset($_SESSION['user_id'])) {
                throw new Exception("Authentication required");
            }

            $userId = $_SESSION['user_id'];
            $name = trim($args['name']);
            $description = isset($args['description']) ? trim($args['description']) : null;

            if (empty($name)) {
                throw new Exception("Group name is required");
            }

            $this->logger->info("Creating contact group for user {$userId}", [
                'name' => $name,
                'description' => $description
            ]);

            // Créer un nouvel objet ContactGroup
            // Note: Le constructeur demande un ID, mais on peut passer 0 pour un nouveau groupe
            $group = new ContactGroup(
                0, // ID sera défini après insertion
                $userId,
                $name,
                $description,
                date('Y-m-d H:i:s'), // createdAt
                date('Y-m-d H:i:s')  // updatedAt
            );

            $savedGroup = $this->contactGroupRepository->create($group);
            
            $this->logger->info("Contact group created successfully", [
                'id' => $savedGroup->getId(),
                'name' => $savedGroup->getName()
            ]);

            // Convertir l'objet en tableau pour GraphQL
            return [
                'id' => (string)$savedGroup->getId(),
                'name' => $savedGroup->getName(),
                'description' => $savedGroup->getDescription(),
                'userId' => (string)$savedGroup->getUserId(),
                'createdAt' => $savedGroup->getCreatedAt(),
                'updatedAt' => $savedGroup->getUpdatedAt()
            ];
        } catch (Exception $e) {
            $this->logger->error('Error creating contact group: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Résoudre la mutation updateContactGroup
     */
    public function mutateUpdateContactGroup(array $args, $context): array
    {
        try {
            // Vérifier l'authentification
            if (!isset($_SESSION['user_id'])) {
                throw new Exception("Authentication required");
            }

            $userId = $_SESSION['user_id'];
            $groupId = (int)$args['id'];
            $name = trim($args['name']);
            $description = isset($args['description']) ? trim($args['description']) : null;

            if (empty($name)) {
                throw new Exception("Group name is required");
            }

            $this->logger->info("Updating contact group {$groupId} for user {$userId}");

            // Récupérer le groupe existant
            $group = $this->contactGroupRepository->findById($groupId);
            
            if ($group === null) {
                throw new Exception("Contact group not found");
            }

            // Vérifier que le groupe appartient à l'utilisateur connecté
            if ($group->getUserId() !== $userId) {
                $this->logger->warning("User {$userId} attempted to update group {$groupId} belonging to user {$group->getUserId()}");
                throw new Exception("Access denied to this contact group");
            }

            // Mettre à jour les propriétés
            $group->setName($name);
            $group->setDescription($description);

            $updatedGroup = $this->contactGroupRepository->update($group);
            
            $this->logger->info("Contact group updated successfully", [
                'id' => $updatedGroup->getId(),
                'name' => $updatedGroup->getName()
            ]);

            // Convertir l'objet en tableau pour GraphQL
            return [
                'id' => (string)$updatedGroup->getId(),
                'name' => $updatedGroup->getName(),
                'description' => $updatedGroup->getDescription(),
                'userId' => (string)$updatedGroup->getUserId(),
                'createdAt' => $updatedGroup->getCreatedAt(),
                'updatedAt' => $updatedGroup->getUpdatedAt()
            ];
        } catch (Exception $e) {
            $this->logger->error('Error updating contact group: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Résoudre la mutation deleteContactGroup
     */
    public function mutateDeleteContactGroup(array $args, $context): bool
    {
        try {
            // Vérifier l'authentification
            if (!isset($_SESSION['user_id'])) {
                throw new Exception("Authentication required");
            }

            $userId = $_SESSION['user_id'];
            $groupId = (int)$args['id'];

            $this->logger->info("Deleting contact group {$groupId} for user {$userId}");

            // Récupérer le groupe existant
            $group = $this->contactGroupRepository->findById($groupId);
            
            if ($group === null) {
                throw new Exception("Contact group not found");
            }

            // Vérifier que le groupe appartient à l'utilisateur connecté
            if ($group->getUserId() !== $userId) {
                $this->logger->warning("User {$userId} attempted to delete group {$groupId} belonging to user {$group->getUserId()}");
                throw new Exception("Access denied to this contact group");
            }

            // Supprimer le groupe
            $success = $this->contactGroupRepository->delete($group);
            
            if ($success) {
                $this->logger->info("Contact group deleted successfully", [
                    'id' => $groupId
                ]);
            } else {
                $this->logger->error("Failed to delete contact group {$groupId}");
            }

            return $success;
        } catch (Exception $e) {
            $this->logger->error('Error deleting contact group: ' . $e->getMessage());
            throw $e;
        }
    }
}