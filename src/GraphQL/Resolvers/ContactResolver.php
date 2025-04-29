<?php

namespace App\GraphQL\Resolvers;

use App\Repositories\Interfaces\ContactRepositoryInterface;
use App\Repositories\Interfaces\ContactGroupRepositoryInterface;
use App\Repositories\Interfaces\ContactGroupMembershipRepositoryInterface;
use App\Models\User;
use App\Models\ContactGroupMembership; // Added
use App\Entities\Contact;
use App\Services\Interfaces\AuthServiceInterface;
use App\GraphQL\Formatters\GraphQLFormatterInterface; // Import Formatter interface
use Exception;
use Psr\Log\LoggerInterface;

class ContactResolver
{
    private ContactRepositoryInterface $contactRepository;
    private ContactGroupRepositoryInterface $groupRepository;
    private ContactGroupMembershipRepositoryInterface $membershipRepository;
    private AuthServiceInterface $authService;
    private GraphQLFormatterInterface $formatter;
    private LoggerInterface $logger;
    private \App\GraphQL\DataLoaders\ContactGroupDataLoader $contactGroupDataLoader;
    private \App\Repositories\Interfaces\SMSHistoryRepositoryInterface $smsHistoryRepository;

    public function __construct(
        ContactRepositoryInterface $contactRepository,
        ContactGroupRepositoryInterface $groupRepository,
        ContactGroupMembershipRepositoryInterface $membershipRepository,
        AuthServiceInterface $authService,
        GraphQLFormatterInterface $formatter,
        LoggerInterface $logger,
        \App\GraphQL\DataLoaders\ContactGroupDataLoader $contactGroupDataLoader,
        \App\Repositories\Interfaces\SMSHistoryRepositoryInterface $smsHistoryRepository
    ) {
        $this->contactRepository = $contactRepository;
        $this->groupRepository = $groupRepository;
        $this->membershipRepository = $membershipRepository;
        $this->authService = $authService;
        $this->formatter = $formatter;
        $this->logger = $logger;
        $this->contactGroupDataLoader = $contactGroupDataLoader;
        $this->smsHistoryRepository = $smsHistoryRepository;
    }

    /**
     * Resolver for the 'contacts' query.
     * Fetches contacts for the currently authenticated user.
     *
     * @param array<string, mixed> $args Contains limit, offset, search, groupId
     * @param mixed $context Context potentially containing the user
     * @param \GraphQL\Type\Definition\ResolveInfo $info Resolve info
     * @return array<int, array<string, mixed>>
     * @throws Exception
     */
    public function resolveContacts(array $args, $context): array
    {
        $this->logger->info('Executing ContactResolver::resolveContacts');
        try {
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for resolveContacts.');
                throw new Exception("User not authenticated");
            }
            $userId = $currentUser->getId();
            // --- End Authentication Handling ---

            $limit = isset($args['limit']) ? (int)$args['limit'] : 100;
            $offset = isset($args['offset']) ? (int)$args['offset'] : 0;
            $search = $args['search'] ?? null;
            $groupId = isset($args['groupId']) ? (int)$args['groupId'] : null;

            // Build criteria array
            $criteria = ['userId' => $userId]; // Always filter by current user
            if ($search !== null) {
                $criteria['search'] = $search; // Repository needs to handle LIKE query on name, phone, email etc.
            }
            if ($groupId !== null) {
                $criteria['groupId'] = $groupId; // Repository needs to handle join with membership
            }
            $this->logger->debug('Constructed criteria for contacts query', ['criteria' => $criteria]);

            // Call a new repository method that handles multiple criteria
            // Assuming findByCriteria exists or will be created in the repository
            $contacts = $this->contactRepository->findByCriteria($criteria, $limit, $offset);
            $this->logger->info('Found ' . count($contacts) . ' contacts based on criteria for user ' . $userId);

            // Convert Contact objects to arrays using the formatter service
            $result = [];
            foreach ($contacts as $contact) {
                $result[] = $this->formatter->formatContact($contact); // Use formatter
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
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for resolveContact.');
                throw new Exception("User not authenticated");
            }
            $userId = $currentUser->getId();
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
            return $this->formatter->formatContact($contact); // Use formatter
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
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for resolveSearchContacts.');
                throw new Exception("User not authenticated");
            }
            $userId = $currentUser->getId();
            // --- End Authentication Handling ---

            $limit = isset($args['limit']) ? (int)$args['limit'] : 100;
            $offset = isset($args['offset']) ? (int)$args['offset'] : 0;

            $contacts = $this->contactRepository->searchByUserId($query, $userId, $limit, $offset);
            $this->logger->info('Found ' . count($contacts) . ' contacts for query "' . $query . '" and user ' . $userId);

            // Convert Contact objects to arrays using the formatter service
            $result = [];
            foreach ($contacts as $contact) {
                $result[] = $this->formatter->formatContact($contact); // Use formatter
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
     * @param array<string, mixed> $args Contains contact data ('name', 'phoneNumber', 'groupIds', etc.)
     * @param mixed $context
     * @return array<string, mixed>
     * @throws Exception
     */
    public function mutateCreateContact(array $args, $context): array
    {
        $name = $args['name'] ?? '';
        $phoneNumber = $args['phoneNumber'] ?? '';
        $groupIds = $args['groupIds'] ?? null; // Get group IDs
        $this->logger->info('Executing ContactResolver::mutateCreateContact for name: ' . $name);

        if (empty($name) || empty($phoneNumber)) {
            $this->logger->error('Name or phoneNumber missing for createContact mutation.', ['args' => $args]);
            throw new Exception("Nom et numéro de téléphone requis.");
        }

        try {
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for mutateCreateContact.');
                throw new Exception("User not authenticated");
            }
            $userId = $currentUser->getId();
            // --- End Authentication Handling ---

            // Create a new contact entity instance
            $contact = new Contact();
            $contact->setUserId($userId);
            $contact->setName($name);
            $contact->setPhoneNumber($phoneNumber);
            if (isset($args['email'])) {
                $contact->setEmail($args['email']);
            }
            if (isset($args['notes'])) {
                $contact->setNotes($args['notes']);
            }
            // createdAt/updatedAt are handled by the entity constructor

            // Save the contact using the repository
            $savedContact = $this->contactRepository->save($contact);
            $contactId = $savedContact->getId();
            $this->logger->info('Contact created successfully for user ' . $userId . ' with ID: ' . $contactId);

            // Handle group memberships if groupIds are provided
            if ($groupIds !== null && is_array($groupIds)) {
                $this->updateContactMemberships($contactId, $groupIds, $userId);
            }

            // Refetch the contact to ensure all data is current after potential membership updates
            $finalContact = $this->contactRepository->findById($contactId);
            if (!$finalContact) {
                $this->logger->error('Failed to refetch contact after creation with ID: ' . $contactId);
                throw new Exception("Erreur lors de la récupération du contact après création.");
            }

            return $this->formatter->formatContact($finalContact); // Use formatter
        } catch (Exception $e) {
            $this->logger->error('Error in ContactResolver::mutateCreateContact: ' . $e->getMessage(), ['exception' => $e]);
            // Consider rolling back contact creation if membership fails? Requires transaction.
            throw $e;
        }
    }

    /**
     * Resolver for the 'updateContact' mutation.
     *
     * @param array<string, mixed> $args Contains 'id' and updated contact data
     * @param mixed $context
     * @param array<string, mixed> $args Contains 'id', updated contact data, and 'groupIds'
     * @param mixed $context
     * @return array<string, mixed>
     * @throws Exception
     */
    public function mutateUpdateContact(array $args, $context): array
    {
        $contactId = (int)($args['id'] ?? 0);
        $groupIds = $args['groupIds'] ?? null; // Get group IDs
        $this->logger->info('Executing ContactResolver::mutateUpdateContact for ID: ' . $contactId);

        if ($contactId <= 0) {
            $this->logger->error('Invalid contact ID provided for updateContact mutation.', ['args' => $args]);
            throw new Exception("ID de contact invalide.");
        }

        try {
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for mutateUpdateContact.');
                throw new Exception("User not authenticated");
            }
            $userId = $currentUser->getId();
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

            // Update the existing contact entity instance (only fields provided in args)
            // Note: The Contact entity should handle setting updatedAt automatically via PreUpdate
            if (isset($args['name'])) {
                $existingContact->setName($args['name']);
            }
            if (isset($args['phoneNumber'])) {
                $existingContact->setPhoneNumber($args['phoneNumber']);
            }
            if (array_key_exists('email', $args)) {
                $existingContact->setEmail($args['email']);
            }
            if (array_key_exists('notes', $args)) {
                $existingContact->setNotes($args['notes']);
            }
            // updatedAt will be handled by the PreUpdate lifecycle callback

            // Save the updated contact
            $savedContact = $this->contactRepository->save($existingContact);
            $this->logger->info('Contact updated successfully for ID: ' . $contactId);

            // Handle group memberships if groupIds are provided
            // If groupIds is null, memberships are not changed by this mutation call
            if ($groupIds !== null && is_array($groupIds)) {
                $this->updateContactMemberships($contactId, $groupIds, $userId);
            }

            // Refetch the contact to ensure all data is current
            $finalContact = $this->contactRepository->findById($contactId);
            if (!$finalContact) {
                $this->logger->error('Failed to refetch contact after update with ID: ' . $contactId);
                throw new Exception("Erreur lors de la récupération du contact après mise à jour.");
            }

            return $this->formatter->formatContact($finalContact); // Use formatter
        } catch (Exception $e) {
            $this->logger->error('Error in ContactResolver::mutateUpdateContact: ' . $e->getMessage(), ['exception' => $e]);
            // Consider transaction for contact update + membership changes
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
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for mutateDeleteContact.');
                throw new Exception("User not authenticated");
            }
            $userId = $currentUser->getId();
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
     * Synchronizes the contact's group memberships based on the provided group IDs.
     *
     * @param int $contactId The ID of the contact.
     * @param array<int|string> $newGroupIds Array of group IDs the contact should belong to.
     * @param int $userId The ID of the current user for authorization checks.
     * @throws Exception If group validation fails.
     */
    private function updateContactMemberships(int $contactId, array $newGroupIds, int $userId): void
    {
        $this->logger->info('Updating memberships for contact ID: ' . $contactId);
        $newGroupIds = array_map('intval', $newGroupIds); // Ensure integer IDs

        // Fetch current memberships
        $currentMemberships = $this->membershipRepository->findByContactId($contactId);
        $currentGroupIds = array_map(fn($m) => $m->getGroupId(), $currentMemberships);

        // Calculate differences
        $idsToAdd = array_diff($newGroupIds, $currentGroupIds);
        $idsToRemove = array_diff($currentGroupIds, $newGroupIds);

        // Remove old memberships
        if (!empty($idsToRemove)) {
            $this->logger->debug('Removing contact ' . $contactId . ' from groups: ' . implode(', ', $idsToRemove));
            foreach ($idsToRemove as $groupIdToRemove) {
                try {
                    $this->membershipRepository->removeContactFromGroup($contactId, $groupIdToRemove);
                } catch (Exception $e) {
                    $this->logger->error('Failed to remove contact ' . $contactId . ' from group ' . $groupIdToRemove, ['exception' => $e]);
                    // Decide if this should halt the process or just log
                }
            }
        }

        // Add new memberships
        if (!empty($idsToAdd)) {
            $this->logger->debug('Adding contact ' . $contactId . ' to groups: ' . implode(', ', $idsToAdd));
            foreach ($idsToAdd as $groupIdToAdd) {
                try {
                    // Verify the group belongs to the current user before adding
                    $group = $this->groupRepository->findById($groupIdToAdd);
                    if (!$group || $group->getUserId() !== $userId) {
                        $this->logger->warning('User ' . $userId . ' attempted to add contact ' . $contactId . ' to unauthorized group ' . $groupIdToAdd);
                        continue; // Skip adding to this group
                    }

                    $this->membershipRepository->addContactToGroup($contactId, $groupIdToAdd);
                } catch (Exception $e) {
                    // Catch potential duplicate entry errors if not handled by DB/repo create method
                    $this->logger->error('Failed to add contact ' . $contactId . ' to group ' . $groupIdToAdd, ['exception' => $e]);
                    // Decide if this should halt the process or just log
                }
            }
        }
        $this->logger->info('Finished updating memberships for contact ID: ' . $contactId);
    } // Added missing closing brace for the helper method

    /**
     * Resolver for the 'contactsCount' query.
     * Returns the total number of contacts for the currently authenticated user.
     *
     * @param array<string, mixed> $args
     * @param mixed $context
     * @param array<string, mixed> $args Contains optional 'search', 'groupId'
     * @param mixed $context
     * @return int
     * @throws Exception
     */
    public function resolveContactsCount(array $args, $context): int
    {
        $this->logger->info('Executing ContactResolver::resolveContactsCount');

        try {
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for resolveContactsCount.');
                throw new Exception("User not authenticated");
            }
            $userId = $currentUser->getId();
            // --- End Authentication Handling ---

            // Extract filter arguments
            $search = $args['search'] ?? null;
            $groupId = isset($args['groupId']) ? (int)$args['groupId'] : null;

            // Build criteria array
            $criteria = ['userId' => $userId]; // Always filter by current user
            if ($search !== null) {
                $criteria['search'] = $search;
            }
            if ($groupId !== null) {
                $criteria['groupId'] = $groupId;
            }
            $this->logger->debug('Constructed criteria for contactsCount query', ['criteria' => $criteria]);

            // Call a repository method that handles multiple criteria for counting
            // Assuming countByCriteria exists or will be created in the repository
            $count = $this->contactRepository->countByCriteria($criteria);
            $this->logger->info('Found ' . $count . ' contacts matching criteria for user ' . $userId);

            return $count;
        } catch (Exception $e) {
            $this->logger->error('Error in ContactResolver::resolveContactsCount: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Field resolver for the 'groups' field of the Contact type.
     * Fetches the groups a contact belongs to using DataLoader for batching.
     *
     * @param array<string, mixed> $contact The contact object
     * @param array<string, mixed> $args Arguments for the field
     * @param mixed $context Context object
     * @return array<int, array<string, mixed>>
     * @throws Exception
     */
    public function resolveContactGroups(array $contact, array $args, $context): array
    {
        $contactId = (int)($contact['id'] ?? 0);
        $this->logger->debug('Resolving Contact.groups field for contact ID: ' . $contactId);

        if ($contactId <= 0) {
            $this->logger->warning('Invalid contact ID in resolveContactGroups.');
            return [];
        }

        try {
            // Get the context-scoped DataLoader
            if (isset($context) && method_exists($context, 'getDataLoader')) {
                // Context is a GraphQLContext object
                $dataLoader = $context->getDataLoader('contactGroups');
                if ($dataLoader) {
                    $this->logger->debug('Using context-scoped ContactGroupDataLoader for contact ID: ' . $contactId);
                    
                    // Store this request in a RequestScoped property for batched execution
                    // The actual loading will be triggered at the end of the request cycle
                    // when all Contact.groups fields have been collected
                    return $dataLoader->load($contactId);
                }
            }

            // Fallback to instance DataLoader if context-scoped one is not available
            $this->logger->debug('Using instance ContactGroupDataLoader for contact ID: ' . $contactId);
            
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for resolveContactGroups.');
                throw new Exception("User not authenticated");
            }
            $userId = $currentUser->getId();
            // --- End Authentication Handling ---

            // Set the user ID if not already set
            if ($this->contactGroupDataLoader->getUserId() !== $userId) {
                $this->contactGroupDataLoader->setUserId($userId);
                $this->logger->debug('Set userId ' . $userId . ' on instance ContactGroupDataLoader');
            }

            // Use the DataLoader to load the groups
            return $this->contactGroupDataLoader->load($contactId);
            
        } catch (Exception $e) {
            $this->logger->error('Error in ContactResolver::resolveContactGroups: ' . $e->getMessage(), ['exception' => $e]);
            return []; // Return empty array on error for field resolver
        }
    }

    /**
     * Resolver for the 'groupsForContact' query.
     * Fetches the groups a specific contact belongs to.
     *
     * @param array<string, mixed> $args Contains 'contactId'
     * @param mixed $context
     * @return array<int, array<string, mixed>>
     * @throws Exception
     */
    public function resolveGroupsForContact(array $args, $context): array
    {
        $contactId = (int)($args['contactId'] ?? 0);
        $this->logger->info('Executing ContactResolver::resolveGroupsForContact for contact ID: ' . $contactId);

        if ($contactId <= 0) {
            $this->logger->warning('Invalid contact ID provided for resolveGroupsForContact.', ['args' => $args]);
            return [];
        }

        try {
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for resolveGroupsForContact.');
                throw new Exception("User not authenticated");
            }
            $userId = $currentUser->getId();
            // --- End Authentication Handling ---

            // Verify the contact belongs to the current user
            $contact = $this->contactRepository->findById($contactId);
            if (!$contact || $contact->getUserId() !== $userId) {
                $this->logger->warning('User ' . $userId . ' attempted to access groups for contact ' . $contactId . ' that does not belong to them.');
                return []; // Return empty array for security
            }

            // Fetch memberships
            $memberships = $this->membershipRepository->findByContactId($contactId);
            $groupIds = array_map(fn($m) => $m->getGroupId(), $memberships);

            if (empty($groupIds)) {
                return [];
            }

            // Fetch group details (ensure groups also belong to the user for consistency, though membership implies this)
            $groups = $this->groupRepository->findByIds($groupIds, $userId); // Assuming findByIds method exists and checks user ID

            // Format the groups
            $result = [];
            foreach ($groups as $group) {
                // Fetch contact count for each group - might be inefficient, consider optimizing if needed
                $contactCount = count($this->groupRepository->getContactsInGroup($group->getId(), 1000, 0));
                $result[] = $this->formatter->formatContactGroup($group, $contactCount);
            }

            $this->logger->info('Found ' . count($result) . ' groups for contact ' . $contactId);
            return $result;
        } catch (Exception $e) {
            $this->logger->error('Error in ContactResolver::resolveGroupsForContact: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the smsHistory field on the Contact type.
     *
     * @param array<string, mixed> $contact The parent Contact object.
     * @param array<string, mixed> $args Arguments passed to the field (none expected here).
     * @param mixed $context The context.
     * @return array
     */
    public function resolveSmsHistory(array $contact, array $args, $context): array
    {
        $phoneNumber = $contact['phoneNumber'] ?? '';
        $this->logger->debug('Resolving Contact.smsHistory field for phone number: ' . $phoneNumber);

        if (empty($phoneNumber)) {
            $this->logger->warning('Empty phone number in resolveSmsHistory.');
            return [];
        }

        try {
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for resolveSmsHistory.');
                throw new Exception("User not authenticated");
            }
            $userId = $currentUser->getId();
            // --- End Authentication Handling ---

            // Get the contact ID and verify owner
            $contactId = (int)($contact['id'] ?? 0);
            if ($contactId > 0) {
                $contactEntity = $this->contactRepository->findById($contactId);
                if (!$contactEntity || $contactEntity->getUserId() !== $userId) {
                    $this->logger->warning('User ' . $userId . ' attempted to access SMS history for contact ' . $contactId . ' that does not belong to them.');
                    return []; // Return empty array for security
                }
            }

            // Fetch SMS history for the phone number
            $limit = isset($args['limit']) ? (int)$args['limit'] : 100;
            $offset = isset($args['offset']) ? (int)$args['offset'] : 0;
            $smsHistory = $this->smsHistoryRepository->findByPhoneNumber($phoneNumber, $limit, $offset);
            
            // Format the SMS history entries
            $result = [];
            foreach ($smsHistory as $sms) {
                $result[] = $this->formatter->formatSMSHistory($sms);
            }

            $this->logger->debug('Found ' . count($result) . ' SMS history entries for phone number ' . $phoneNumber);
            return $result;
        } catch (Exception $e) {
            $this->logger->error('Error in ContactResolver::resolveSmsHistory: ' . $e->getMessage(), ['exception' => $e]);
            return []; // Return empty array on error for field resolver
        }
    }

    /**
     * Resolver for the smsTotalCount field on the Contact type.
     *
     * @param array<string, mixed> $contact The parent Contact object.
     * @return int
     */
    public function resolveSmsTotalCount(array $contact): int
    {
        $phoneNumber = $contact['phoneNumber'] ?? '';
        $this->logger->debug('Resolving Contact.smsTotalCount field for phone number: ' . $phoneNumber);

        if (empty($phoneNumber)) {
            return 0;
        }

        try {
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for resolveSmsTotalCount.');
                return 0; // Return 0 on authentication failure for field resolver
            }
            // --- End Authentication Handling ---

            // Count all SMS sent to this phone number
            $count = $this->smsHistoryRepository->countByPhoneNumber($phoneNumber);
            $this->logger->debug('Counted ' . $count . ' total SMS for phone number ' . $phoneNumber);
            return $count;
        } catch (Exception $e) {
            $this->logger->error('Error in ContactResolver::resolveSmsTotalCount: ' . $e->getMessage(), ['exception' => $e]);
            return 0; // Return 0 on error for field resolver
        }
    }

    /**
     * Resolver for the smsSentCount field on the Contact type.
     *
     * @param array<string, mixed> $contact The parent Contact object.
     * @return int
     */
    public function resolveSmsSentCount(array $contact): int
    {
        $phoneNumber = $contact['phoneNumber'] ?? '';
        $this->logger->debug('Resolving Contact.smsSentCount field for phone number: ' . $phoneNumber);

        if (empty($phoneNumber)) {
            return 0;
        }

        try {
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for resolveSmsSentCount.');
                return 0; // Return 0 on authentication failure for field resolver
            }
            // --- End Authentication Handling ---

            // Count SMS with 'SENT' status sent to this phone number
            $count = $this->smsHistoryRepository->countByPhoneNumberAndStatus($phoneNumber, 'SENT');
            $this->logger->debug('Counted ' . $count . ' SENT SMS for phone number ' . $phoneNumber);
            return $count;
        } catch (Exception $e) {
            $this->logger->error('Error in ContactResolver::resolveSmsSentCount: ' . $e->getMessage(), ['exception' => $e]);
            return 0; // Return 0 on error for field resolver
        }
    }

    /**
     * Resolver for the smsFailedCount field on the Contact type.
     *
     * @param array<string, mixed> $contact The parent Contact object.
     * @return int
     */
    public function resolveSmsFailedCount(array $contact): int
    {
        $phoneNumber = $contact['phoneNumber'] ?? '';
        $this->logger->debug('Resolving Contact.smsFailedCount field for phone number: ' . $phoneNumber);

        if (empty($phoneNumber)) {
            return 0;
        }

        try {
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for resolveSmsFailedCount.');
                return 0; // Return 0 on authentication failure for field resolver
            }
            // --- End Authentication Handling ---

            // Count SMS with 'FAILED' status sent to this phone number
            $count = $this->smsHistoryRepository->countByPhoneNumberAndStatus($phoneNumber, 'FAILED');
            $this->logger->debug('Counted ' . $count . ' FAILED SMS for phone number ' . $phoneNumber);
            return $count;
        } catch (Exception $e) {
            $this->logger->error('Error in ContactResolver::resolveSmsFailedCount: ' . $e->getMessage(), ['exception' => $e]);
            return 0; // Return 0 on error for field resolver
        }
    }

    /**
     * Resolver for the smsScore field on the Contact type.
     *
     * @param array<string, mixed> $contact The parent Contact object.
     * @return float
     */
    public function resolveSmsScore(array $contact): float
    {
        $phoneNumber = $contact['phoneNumber'] ?? '';
        $this->logger->debug('Resolving Contact.smsScore field for phone number: ' . $phoneNumber);

        if (empty($phoneNumber)) {
            return 0.0;
        }

        try {
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for resolveSmsScore.');
                return 0.0; // Return 0.0 on authentication failure for field resolver
            }
            // --- End Authentication Handling ---

            // Calculate the score based on SENT / Total ratio
            $total = $this->smsHistoryRepository->countByPhoneNumber($phoneNumber);
            
            if ($total === 0) {
                return 0.0; // Avoid division by zero if no SMS were sent
            }
            
            $sent = $this->smsHistoryRepository->countByPhoneNumberAndStatus($phoneNumber, 'SENT');
            
            // Calculate score as SENT / Total, rounded to two decimal places
            $score = round($sent / $total, 2);
            $this->logger->debug('Calculated SMS score ' . $score . ' for phone number ' . $phoneNumber . ' (SENT: ' . $sent . ', Total: ' . $total . ')');
            
            return $score;
        } catch (Exception $e) {
            $this->logger->error('Error in ContactResolver::resolveSmsScore: ' . $e->getMessage(), ['exception' => $e]);
            return 0.0; // Return 0.0 on error for field resolver
        }
    }
}
