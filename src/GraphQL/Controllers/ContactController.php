<?php

namespace App\GraphQL\Controllers;

use App\Models\Contact;
use App\Repositories\ContactRepository;
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
    private ContactRepository $contactRepository;
    private LoggerInterface $logger;

    public function __construct(ContactRepository $contactRepository, LoggerInterface $logger)
    {
        $this->contactRepository = $contactRepository;
        $this->logger = $logger;
    }

    /**
     * @Query
     * @Logged
     * @Right("ROLE_USER")
     */
    public function contacts(?int $limit = 100, ?int $offset = 0, User $user): array
    {
        try {
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
     */
    public function contact(int $id, User $user): ?Contact
    {
        try {
            $contact = $this->contactRepository->findById($id);

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
    public function searchContacts(string $query, ?int $limit = 100, ?int $offset = 0, User $user): array
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
     */
    public function createContact(
        string $name,
        string $phoneNumber,
        ?string $email = null,
        ?string $notes = null,
        User $user
    ): ?Contact {
        try {
            $contact = new Contact(
                0, // ID sera généré par la base de données
                $user->getId(),
                $name,
                $phoneNumber,
                $email,
                $notes
            );

            return $this->contactRepository->create($contact);
        } catch (Exception $e) {
            $this->logger->error('Error creating contact: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @Mutation
     * @Logged
     * @Right("ROLE_USER")
     */
    public function updateContact(
        int $id,
        string $name,
        string $phoneNumber,
        ?string $email = null,
        ?string $notes = null,
        User $user
    ): ?Contact {
        try {
            // Récupérer le contact existant
            $existingContact = $this->contactRepository->findById($id);

            // Vérifier que le contact existe et appartient à l'utilisateur
            if (!$existingContact || $existingContact->getUserId() !== $user->getId()) {
                return null;
            }

            // Créer un nouveau contact avec les données mises à jour
            $updatedContact = new Contact(
                $id,
                $user->getId(),
                $name,
                $phoneNumber,
                $email,
                $notes,
                $existingContact->getCreatedAt()
            );

            return $this->contactRepository->update($updatedContact);
        } catch (Exception $e) {
            $this->logger->error('Error updating contact: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @Mutation
     * @Logged
     * @Right("ROLE_USER")
     */
    public function deleteContact(int $id, User $user): bool
    {
        try {
            // Récupérer le contact existant
            $existingContact = $this->contactRepository->findById($id);

            // Vérifier que le contact existe et appartient à l'utilisateur
            if (!$existingContact || $existingContact->getUserId() !== $user->getId()) {
                return false;
            }

            return $this->contactRepository->delete($existingContact);
        } catch (Exception $e) {
            $this->logger->error('Error deleting contact: ' . $e->getMessage());
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
}
