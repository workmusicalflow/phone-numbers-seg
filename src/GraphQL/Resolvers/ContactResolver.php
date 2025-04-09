<?php

namespace App\GraphQL\Resolvers;

use App\Repositories\ContactRepository;
use App\Models\Contact; // Assuming Contact model exists
use App\Models\User; // Needed for user context
use Exception;
use Psr\Log\LoggerInterface;

class ContactResolver
{
    private ContactRepository $contactRepository;
    private LoggerInterface $logger;

    // We might need AuthService later for user context if not passed directly
    public function __construct(ContactRepository $contactRepository, LoggerInterface $logger)
    {
        $this->contactRepository = $contactRepository;
        $this->logger = $logger;
    }

    /**
     * Resolver for the 'contacts' query.
     * Fetches contacts for the currently authenticated user.
     *
     * @param array<string, mixed> $args Contains limit and offset
     * @param mixed $context Context potentially containing the user
     * @param \GraphQL\Type\Definition\ResolveInfo $info Resolve info
     * @return array<int, array<string, mixed>>
     * @throws Exception
     */
    public function resolveContacts(array $args, $context): array
    {
        $this->logger->info('Executing ContactResolver::resolveContacts');
        try {
            // --- Authentication/User Context Handling (Phase 2 improvement needed) ---
            // For now, assume user ID is in session or passed via context
            // This needs to be standardized later. Let's try session for now.
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                $this->logger->error('User not authenticated for resolveContacts.');
                throw new Exception("User not authenticated");
            }
            // --- End Authentication Handling ---

            $limit = isset($args['limit']) ? (int)$args['limit'] : 100;
            $offset = isset($args['offset']) ? (int)$args['offset'] : 0;

            $contacts = $this->contactRepository->findByUserId($userId, $limit, $offset);
            $this->logger->info('Found ' . count($contacts) . ' contacts for user ' . $userId);

            // Convert Contact objects to arrays
            $result = [];
            foreach ($contacts as $contact) {
                $result[] = $this->formatContact($contact);
            }
            $this->logger->info('Formatted contacts for GraphQL response.');
            return $result;
        } catch (Exception $e) {
            $this->logger->error('Error in ContactResolver::resolveContacts: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'contact' query.
     * Fetches a single contact by ID, ensuring it belongs to the current user.
     *
     * @param array<string, mixed> $args Contains 'id'
     * @param mixed $context
     * @return array<string, mixed>|null
     * @throws Exception
     */
    public function resolveContact(array $args, $context): ?array
    {
        $contactId = (int)($args['id'] ?? 0);
        $this->logger->info('Executing ContactResolver::resolveContact for ID: ' . $contactId);

        if ($contactId <= 0) {
            $this->logger->warning('Invalid contact ID provided for resolveContact.', ['args' => $args]);
            return null;
        }

        try {
            // --- Authentication/User Context Handling ---
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                $this->logger->error('User not authenticated for resolveContact.');
                throw new Exception("User not authenticated");
            }
            // --- End Authentication Handling ---

            $contact = $this->contactRepository->findById($contactId);
            if (!$contact) {
                $this->logger->info('Contact not found for ID: ' . $contactId);
                return null;
            }

            // Authorization check: Does this contact belong to the current user?
            if ($contact->getUserId() !== $userId) {
                $this->logger->warning('User ' . $userId . ' attempted to access contact ' . $contactId . ' belonging to user ' . $contact->getUserId());
                return null; // Or throw an authorization exception
            }

            $this->logger->info('Contact found for ID: ' . $contactId . ' and user ' . $userId);
            return $this->formatContact($contact);
        } catch (Exception $e) {
            $this->logger->error('Error in ContactResolver::resolveContact: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'searchContacts' query.
     * Searches contacts for the currently authenticated user.
     *
     * @param array<string, mixed> $args Contains 'query', 'limit', 'offset'
     * @param mixed $context
     * @return array<int, array<string, mixed>>
     * @throws Exception
     */
    public function resolveSearchContacts(array $args, $context): array
    {
        $query = $args['query'] ?? '';
        $this->logger->info('Executing ContactResolver::resolveSearchContacts for query: ' . $query);

        if (empty($query)) {
            $this->logger->warning('Empty query provided for searchContacts.', ['args' => $args]);
            return []; // Return empty array for empty query
        }

        try {
            // --- Authentication/User Context Handling ---
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                $this->logger->error('User not authenticated for resolveSearchContacts.');
                throw new Exception("User not authenticated");
            }
            // --- End Authentication Handling ---

            $limit = isset($args['limit']) ? (int)$args['limit'] : 100;
            $offset = isset($args['offset']) ? (int)$args['offset'] : 0;

            $contacts = $this->contactRepository->searchByUserId($query, $userId, $limit, $offset);
            $this->logger->info('Found ' . count($contacts) . ' contacts for query "' . $query . '" and user ' . $userId);

            // Convert Contact objects to arrays
            $result = [];
            foreach ($contacts as $contact) {
                $result[] = $this->formatContact($contact);
            }
            $this->logger->info('Formatted search results for GraphQL response.');
            return $result;
        } catch (Exception $e) {
            $this->logger->error('Error in ContactResolver::resolveSearchContacts: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'createContact' mutation.
     *
     * @param array<string, mixed> $args Contains contact data ('name', 'phoneNumber', etc.)
     * @param mixed $context
     * @return array<string, mixed>
     * @throws Exception
     */
    public function mutateCreateContact(array $args, $context): array
    {
        $name = $args['name'] ?? '';
        $phoneNumber = $args['phoneNumber'] ?? '';
        $this->logger->info('Executing ContactResolver::mutateCreateContact for name: ' . $name);

        if (empty($name) || empty($phoneNumber)) {
            $this->logger->error('Name or phoneNumber missing for createContact mutation.', ['args' => $args]);
            throw new Exception("Nom et numéro de téléphone requis.");
        }

        try {
            // --- Authentication/User Context Handling ---
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                $this->logger->error('User not authenticated for mutateCreateContact.');
                throw new Exception("User not authenticated");
            }
            // --- End Authentication Handling ---

            // Create a new contact model instance
            $contact = new Contact(
                0, // ID will be generated by the database
                $userId,
                $name,
                $phoneNumber,
                $args['email'] ?? null,
                $args['notes'] ?? null
                // createdAt/updatedAt handled by model/repository
            );

            // Save the contact using the repository
            $savedContact = $this->contactRepository->create($contact); // Assuming 'create' returns the saved object with ID
            $this->logger->info('Contact created successfully for user ' . $userId . ' with ID: ' . $savedContact->getId());

            return $this->formatContact($savedContact);
        } catch (Exception $e) {
            $this->logger->error('Error in ContactResolver::mutateCreateContact: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'updateContact' mutation.
     *
     * @param array<string, mixed> $args Contains 'id' and updated contact data
     * @param mixed $context
     * @return array<string, mixed>
     * @throws Exception
     */
    public function mutateUpdateContact(array $args, $context): array
    {
        $contactId = (int)($args['id'] ?? 0);
        $this->logger->info('Executing ContactResolver::mutateUpdateContact for ID: ' . $contactId);

        if ($contactId <= 0) {
            $this->logger->error('Invalid contact ID provided for updateContact mutation.', ['args' => $args]);
            throw new Exception("ID de contact invalide.");
        }

        try {
            // --- Authentication/User Context Handling ---
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                $this->logger->error('User not authenticated for mutateUpdateContact.');
                throw new Exception("User not authenticated");
            }
            // --- End Authentication Handling ---

            // Fetch the existing contact
            $existingContact = $this->contactRepository->findById($contactId);
            if (!$existingContact) {
                $this->logger->error('Contact not found for update mutation with ID: ' . $contactId);
                throw new Exception("Contact non trouvé");
            }

            // Authorization check
            if ($existingContact->getUserId() !== $userId) {
                $this->logger->warning('User ' . $userId . ' attempted to update contact ' . $contactId . ' belonging to user ' . $existingContact->getUserId());
                throw new Exception("Contact non trouvé"); // Treat as not found for security
            }

            // Update the contact model instance (only fields provided in args)
            // Note: The Contact model should ideally handle setting updatedAt automatically
            $updatedContact = new Contact(
                $contactId,
                $userId,
                $args['name'] ?? $existingContact->getName(), // Use existing value if not provided
                $args['phoneNumber'] ?? $existingContact->getPhoneNumber(),
                array_key_exists('email', $args) ? $args['email'] : $existingContact->getEmail(),
                array_key_exists('notes', $args) ? $args['notes'] : $existingContact->getNotes(),
                $existingContact->getCreatedAt() // Keep original creation date
                // updatedAt should be handled by the model or repository update method
            );


            // Save the updated contact
            $savedContact = $this->contactRepository->update($updatedContact); // Assuming 'update' returns the saved object
            $this->logger->info('Contact updated successfully for ID: ' . $contactId);

            return $this->formatContact($savedContact);
        } catch (Exception $e) {
            $this->logger->error('Error in ContactResolver::mutateUpdateContact: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'deleteContact' mutation.
     *
     * @param array<string, mixed> $args Contains 'id'
     * @param mixed $context
     * @return bool
     * @throws Exception
     */
    public function mutateDeleteContact(array $args, $context): bool
    {
        $contactId = (int)($args['id'] ?? 0);
        $this->logger->info('Executing ContactResolver::mutateDeleteContact for ID: ' . $contactId);

        if ($contactId <= 0) {
            $this->logger->error('Invalid contact ID provided for deleteContact mutation.', ['args' => $args]);
            throw new Exception("ID de contact invalide.");
        }

        try {
            // --- Authentication/User Context Handling ---
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                $this->logger->error('User not authenticated for mutateDeleteContact.');
                throw new Exception("User not authenticated");
            }
            // --- End Authentication Handling ---

            // Fetch the existing contact to verify ownership
            $existingContact = $this->contactRepository->findById($contactId);
            if (!$existingContact) {
                $this->logger->warning('Attempted to delete non-existent contact with ID: ' . $contactId);
                return false; // Or throw not found exception
            }

            // Authorization check
            if ($existingContact->getUserId() !== $userId) {
                $this->logger->warning('User ' . $userId . ' attempted to delete contact ' . $contactId . ' belonging to user ' . $existingContact->getUserId());
                return false; // Treat as not found for security
            }

            // Delete the contact
            $deleted = $this->contactRepository->delete($existingContact); // Assuming delete takes object or ID
            if ($deleted) {
                $this->logger->info('Contact deleted successfully with ID: ' . $contactId);
            } else {
                $this->logger->error('Failed to delete contact with ID: ' . $contactId);
            }
            return $deleted;
        } catch (Exception $e) {
            $this->logger->error('Error in ContactResolver::mutateDeleteContact: ' . $e->getMessage(), ['exception' => $e]);
            // Depending on policy, might return false or re-throw
            // Returning false might hide underlying issues, re-throwing is often better
            throw $e;
            // return false;
        }
    }


    // --- Helper Methods ---

    /**
     * Formats a Contact object into an array suitable for GraphQL response.
     * This will be improved in Phase 3 (Centralized Conversion).
     *
     * @param Contact $contact
     * @return array<string, mixed>
     */
    private function formatContact(Contact $contact): array
    {
        // Note: The GraphQL schema expects 'id' as ID!, 'name', 'phoneNumber', etc.
        return [
            'id' => $contact->getId(), // Ensure ID is returned as string if schema expects ID!
            'name' => $contact->getName(),
            'phoneNumber' => $contact->getPhoneNumber(),
            'email' => $contact->getEmail(),
            'notes' => $contact->getNotes(),
            'createdAt' => $contact->getCreatedAt(), // Ensure format is correct (e.g., ISO 8601)
            'updatedAt' => $contact->getUpdatedAt(), // Ensure format is correct
        ];
    }
}
