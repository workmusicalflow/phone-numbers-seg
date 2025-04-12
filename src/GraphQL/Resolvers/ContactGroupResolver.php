<?php

namespace App\GraphQL\Resolvers;

use App\Repositories\ContactGroupRepository;
use App\Repositories\ContactGroupMembershipRepository;
use App\Repositories\ContactRepository;
use App\Models\ContactGroup;
use App\Models\ContactGroupMembership;
use App\Models\Contact;
use App\Services\Interfaces\AuthServiceInterface;
use App\GraphQL\Formatters\GraphQLFormatterInterface;
use Exception;
use Psr\Log\LoggerInterface;

class ContactGroupResolver
{
    private ContactGroupRepository $contactGroupRepository;
    private ContactGroupMembershipRepository $membershipRepository;
    private ContactRepository $contactRepository;
    private AuthServiceInterface $authService;
    private GraphQLFormatterInterface $formatter;
    private LoggerInterface $logger;

    public function __construct(
        ContactGroupRepository $contactGroupRepository,
        ContactGroupMembershipRepository $membershipRepository,
        ContactRepository $contactRepository,
        AuthServiceInterface $authService,
        GraphQLFormatterInterface $formatter,
        LoggerInterface $logger
    ) {
        $this->contactGroupRepository = $contactGroupRepository;
        $this->membershipRepository = $membershipRepository;
        $this->contactRepository = $contactRepository;
        $this->authService = $authService;
        $this->formatter = $formatter;
        $this->logger = $logger;
    }

    /**
     * Resolver for the 'contactGroups' query.
     * Fetches contact groups for the currently authenticated user.
     *
     * @param array<string, mixed> $args Contains limit and offset
     * @param mixed $context Context potentially containing the user
     * @return array<int, array<string, mixed>>
     * @throws Exception
     */
    public function resolveContactGroups(array $args, $context): array
    {
        $this->logger->info('Executing ContactGroupResolver::resolveContactGroups');
        try {
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for resolveContactGroups.');
                throw new Exception("User not authenticated");
            }
            $userId = $currentUser->getId();
            // --- End Authentication Handling ---

            $limit = isset($args['limit']) ? (int)$args['limit'] : 100;
            $offset = isset($args['offset']) ? (int)$args['offset'] : 0;

            $groups = $this->contactGroupRepository->findByUserId($userId, $limit, $offset);
            $this->logger->info('Found ' . count($groups) . ' contact groups for user ' . $userId);

            // Convert ContactGroup objects to arrays using the formatter service
            $result = [];
            foreach ($groups as $group) {
                // Get the count of contacts in this group
                $contactCount = count($this->contactGroupRepository->getContactsInGroup($group->getId(), 1000, 0));
                $result[] = $this->formatter->formatContactGroup($group, $contactCount);
            }
            $this->logger->info('Formatted contact groups for GraphQL response.');
            return $result;
        } catch (Exception $e) {
            $this->logger->error('Error in ContactGroupResolver::resolveContactGroups: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'contactGroup' query.
     * Fetches a single contact group by ID, ensuring it belongs to the current user.
     *
     * @param array<string, mixed> $args Contains 'id'
     * @param mixed $context
     * @return array<string, mixed>|null
     * @throws Exception
     */
    public function resolveContactGroup(array $args, $context): ?array
    {
        $groupId = (int)($args['id'] ?? 0);
        $this->logger->info('Executing ContactGroupResolver::resolveContactGroup for ID: ' . $groupId);

        if ($groupId <= 0) {
            $this->logger->warning('Invalid contact group ID provided for resolveContactGroup.', ['args' => $args]);
            return null;
        }

        try {
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for resolveContactGroup.');
                throw new Exception("User not authenticated");
            }
            $userId = $currentUser->getId();
            // --- End Authentication Handling ---

            $group = $this->contactGroupRepository->findById($groupId);
            if (!$group) {
                $this->logger->info('Contact group not found for ID: ' . $groupId);
                return null;
            }

            // Authorization check: Does this group belong to the current user?
            if ($group->getUserId() !== $userId) {
                $this->logger->warning('User ' . $userId . ' attempted to access contact group ' . $groupId . ' belonging to user ' . $group->getUserId());
                return null; // Or throw an authorization exception
            }

            // Get the count of contacts in this group
            $contactCount = count($this->contactGroupRepository->getContactsInGroup($group->getId(), 1000, 0));
            $this->logger->info('Contact group found for ID: ' . $groupId . ' and user ' . $userId);
            return $this->formatter->formatContactGroup($group, $contactCount);
        } catch (Exception $e) {
            $this->logger->error('Error in ContactGroupResolver::resolveContactGroup: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'contactGroupsCount' query.
     * Returns the total number of contact groups for the currently authenticated user.
     *
     * @param array<string, mixed> $args
     * @param mixed $context
     * @return int
     * @throws Exception
     */
    public function resolveContactGroupsCount(array $args, $context): int
    {
        $this->logger->info('Executing ContactGroupResolver::resolveContactGroupsCount');

        try {
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for resolveContactGroupsCount.');
                throw new Exception("User not authenticated");
            }
            $userId = $currentUser->getId();
            // --- End Authentication Handling ---

            $count = $this->contactGroupRepository->count($userId);
            $this->logger->info('Found ' . $count . ' contact groups for user ' . $userId);

            return $count;
        } catch (Exception $e) {
            $this->logger->error('Error in ContactGroupResolver::resolveContactGroupsCount: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'contactsInGroup' query.
     * Fetches contacts that belong to a specific group.
     *
     * @param array<string, mixed> $args Contains 'groupId', 'limit', and 'offset'
     * @param mixed $context
     * @return array<int, array<string, mixed>>
     * @throws Exception
     */
    public function resolveContactsInGroup(array $args, $context): array
    {
        $groupId = (int)($args['groupId'] ?? 0);
        $this->logger->info('Executing ContactGroupResolver::resolveContactsInGroup for group ID: ' . $groupId);

        if ($groupId <= 0) {
            $this->logger->warning('Invalid group ID provided for resolveContactsInGroup.', ['args' => $args]);
            return [];
        }

        try {
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for resolveContactsInGroup.');
                throw new Exception("User not authenticated");
            }
            $userId = $currentUser->getId();
            // --- End Authentication Handling ---

            // Verify the group belongs to the current user
            $group = $this->contactGroupRepository->findById($groupId);
            if (!$group || $group->getUserId() !== $userId) {
                $this->logger->warning('User ' . $userId . ' attempted to access contacts in group ' . $groupId . ' that does not belong to them.');
                return []; // Return empty array for security
            }

            $limit = isset($args['limit']) ? (int)$args['limit'] : 100;
            $offset = isset($args['offset']) ? (int)$args['offset'] : 0;

            $contacts = $this->contactGroupRepository->getContactsInGroup($groupId, $limit, $offset);
            $this->logger->info('Found ' . count($contacts) . ' contacts in group ' . $groupId);

            // Convert Contact objects to arrays using the formatter service
            $result = [];
            foreach ($contacts as $contact) {
                $result[] = $this->formatter->formatContact($contact);
            }
            $this->logger->info('Formatted contacts for GraphQL response.');
            return $result;
        } catch (Exception $e) {
            $this->logger->error('Error in ContactGroupResolver::resolveContactsInGroup: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'contactsInGroupCount' query.
     * Returns the total number of contacts in a specific group.
     *
     * @param array<string, mixed> $args Contains 'groupId'
     * @param mixed $context
     * @return int
     * @throws Exception
     */
    public function resolveContactsInGroupCount(array $args, $context): int
    {
        $groupId = (int)($args['groupId'] ?? 0);
        $this->logger->info('Executing ContactGroupResolver::resolveContactsInGroupCount for group ID: ' . $groupId);

        if ($groupId <= 0) {
            $this->logger->warning('Invalid group ID provided for resolveContactsInGroupCount.', ['args' => $args]);
            return 0;
        }

        try {
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for resolveContactsInGroupCount.');
                throw new Exception("User not authenticated");
            }
            $userId = $currentUser->getId();
            // --- End Authentication Handling ---

            // Verify the group belongs to the current user
            $group = $this->contactGroupRepository->findById($groupId);
            if (!$group || $group->getUserId() !== $userId) {
                $this->logger->warning('User ' . $userId . ' attempted to access contact count in group ' . $groupId . ' that does not belong to them.');
                return 0; // Return 0 for security
            }

            $contacts = $this->contactGroupRepository->getContactsInGroup($groupId, 1000, 0);
            $count = count($contacts);
            $this->logger->info('Found ' . $count . ' contacts in group ' . $groupId);

            return $count;
        } catch (Exception $e) {
            $this->logger->error('Error in ContactGroupResolver::resolveContactsInGroupCount: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'createContactGroup' mutation.
     *
     * @param array<string, mixed> $args Contains contact group data ('name', 'description')
     * @param mixed $context
     * @return array<string, mixed>
     * @throws Exception
     */
    public function mutateCreateContactGroup(array $args, $context): array
    {
        $name = $args['name'] ?? '';
        $this->logger->info('Executing ContactGroupResolver::mutateCreateContactGroup for name: ' . $name);

        if (empty($name)) {
            $this->logger->error('Name missing for createContactGroup mutation.', ['args' => $args]);
            throw new Exception("Nom du groupe requis.");
        }

        try {
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for mutateCreateContactGroup.');
                throw new Exception("User not authenticated");
            }
            $userId = $currentUser->getId();
            // --- End Authentication Handling ---

            // Create a new contact group model instance
            $group = new ContactGroup(
                0, // ID will be generated by the database
                $userId,
                $name,
                $args['description'] ?? null
                // createdAt/updatedAt handled by model/repository
            );

            // Save the contact group using the repository
            $savedGroup = $this->contactGroupRepository->create($group);
            $this->logger->info('Contact group created successfully for user ' . $userId . ' with ID: ' . $savedGroup->getId());

            return $this->formatter->formatContactGroup($savedGroup, 0); // New group has 0 contacts
        } catch (Exception $e) {
            $this->logger->error('Error in ContactGroupResolver::mutateCreateContactGroup: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'updateContactGroup' mutation.
     *
     * @param array<string, mixed> $args Contains 'id' and updated contact group data
     * @param mixed $context
     * @return array<string, mixed>
     * @throws Exception
     */
    public function mutateUpdateContactGroup(array $args, $context): array
    {
        $groupId = (int)($args['id'] ?? 0);
        $this->logger->info('Executing ContactGroupResolver::mutateUpdateContactGroup for ID: ' . $groupId);

        if ($groupId <= 0) {
            $this->logger->error('Invalid contact group ID provided for updateContactGroup mutation.', ['args' => $args]);
            throw new Exception("ID de groupe invalide.");
        }

        try {
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for mutateUpdateContactGroup.');
                throw new Exception("User not authenticated");
            }
            $userId = $currentUser->getId();
            // --- End Authentication Handling ---

            // Fetch the existing contact group
            $existingGroup = $this->contactGroupRepository->findById($groupId);
            if (!$existingGroup) {
                $this->logger->error('Contact group not found for update mutation with ID: ' . $groupId);
                throw new Exception("Groupe non trouvé");
            }

            // Authorization check
            if ($existingGroup->getUserId() !== $userId) {
                $this->logger->warning('User ' . $userId . ' attempted to update contact group ' . $groupId . ' belonging to user ' . $existingGroup->getUserId());
                throw new Exception("Groupe non trouvé"); // Treat as not found for security
            }

            // Update the contact group model instance
            $existingGroup->setName($args['name'] ?? $existingGroup->getName());
            if (array_key_exists('description', $args)) {
                $existingGroup->setDescription($args['description']);
            }

            // Save the updated contact group
            $savedGroup = $this->contactGroupRepository->update($existingGroup);
            $this->logger->info('Contact group updated successfully for ID: ' . $groupId);

            // Get the count of contacts in this group
            $contactCount = count($this->contactGroupRepository->getContactsInGroup($groupId, 1000, 0));
            return $this->formatter->formatContactGroup($savedGroup, $contactCount);
        } catch (Exception $e) {
            $this->logger->error('Error in ContactGroupResolver::mutateUpdateContactGroup: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'deleteContactGroup' mutation.
     *
     * @param array<string, mixed> $args Contains 'id'
     * @param mixed $context
     * @return bool
     * @throws Exception
     */
    public function mutateDeleteContactGroup(array $args, $context): bool
    {
        $groupId = (int)($args['id'] ?? 0);
        $this->logger->info('Executing ContactGroupResolver::mutateDeleteContactGroup for ID: ' . $groupId);

        if ($groupId <= 0) {
            $this->logger->error('Invalid contact group ID provided for deleteContactGroup mutation.', ['args' => $args]);
            throw new Exception("ID de groupe invalide.");
        }

        try {
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for mutateDeleteContactGroup.');
                throw new Exception("User not authenticated");
            }
            $userId = $currentUser->getId();
            // --- End Authentication Handling ---

            // Fetch the existing contact group to verify ownership
            $existingGroup = $this->contactGroupRepository->findById($groupId);
            if (!$existingGroup) {
                $this->logger->warning('Attempted to delete non-existent contact group with ID: ' . $groupId);
                return false; // Or throw not found exception
            }

            // Authorization check
            if ($existingGroup->getUserId() !== $userId) {
                $this->logger->warning('User ' . $userId . ' attempted to delete contact group ' . $groupId . ' belonging to user ' . $existingGroup->getUserId());
                return false; // Treat as not found for security
            }

            // Delete the contact group
            $deleted = $this->contactGroupRepository->delete($existingGroup);
            if ($deleted) {
                $this->logger->info('Contact group deleted successfully with ID: ' . $groupId);
            } else {
                $this->logger->error('Failed to delete contact group with ID: ' . $groupId);
            }
            return $deleted;
        } catch (Exception $e) {
            $this->logger->error('Error in ContactGroupResolver::mutateDeleteContactGroup: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'addContactToGroup' mutation.
     *
     * @param array<string, mixed> $args Contains 'contactId' and 'groupId'
     * @param mixed $context
     * @return array<string, mixed>
     * @throws Exception
     */
    public function mutateAddContactToGroup(array $args, $context): array
    {
        $contactId = (int)($args['contactId'] ?? 0);
        $groupId = (int)($args['groupId'] ?? 0);
        $this->logger->info('Executing ContactGroupResolver::mutateAddContactToGroup for contact ID: ' . $contactId . ' and group ID: ' . $groupId);

        if ($contactId <= 0 || $groupId <= 0) {
            $this->logger->error('Invalid contact ID or group ID provided for addContactToGroup mutation.', ['args' => $args]);
            throw new Exception("ID de contact ou de groupe invalide.");
        }

        try {
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for mutateAddContactToGroup.');
                throw new Exception("User not authenticated");
            }
            $userId = $currentUser->getId();
            // --- End Authentication Handling ---

            // Verify the contact belongs to the current user
            $contact = $this->contactRepository->findById($contactId);
            if (!$contact || $contact->getUserId() !== $userId) {
                $this->logger->warning('User ' . $userId . ' attempted to add contact ' . $contactId . ' that does not belong to them to a group.');
                throw new Exception("Contact non trouvé"); // Treat as not found for security
            }

            // Verify the group belongs to the current user
            $group = $this->contactGroupRepository->findById($groupId);
            if (!$group || $group->getUserId() !== $userId) {
                $this->logger->warning('User ' . $userId . ' attempted to add a contact to group ' . $groupId . ' that does not belong to them.');
                throw new Exception("Groupe non trouvé"); // Treat as not found for security
            }

            // Create a new membership
            $membership = new ContactGroupMembership(
                0, // ID will be generated by the database
                $contactId,
                $groupId
                // createdAt handled by model/repository
            );

            // Save the membership using the repository
            $savedMembership = $this->membershipRepository->create($membership);
            $this->logger->info('Contact ' . $contactId . ' added to group ' . $groupId . ' successfully.');

            return $this->formatter->formatContactGroupMembership($savedMembership, $contact, $group);
        } catch (Exception $e) {
            $this->logger->error('Error in ContactGroupResolver::mutateAddContactToGroup: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'removeContactFromGroup' mutation.
     *
     * @param array<string, mixed> $args Contains 'contactId' and 'groupId'
     * @param mixed $context
     * @return bool
     * @throws Exception
     */
    public function mutateRemoveContactFromGroup(array $args, $context): bool
    {
        $contactId = (int)($args['contactId'] ?? 0);
        $groupId = (int)($args['groupId'] ?? 0);
        $this->logger->info('Executing ContactGroupResolver::mutateRemoveContactFromGroup for contact ID: ' . $contactId . ' and group ID: ' . $groupId);

        if ($contactId <= 0 || $groupId <= 0) {
            $this->logger->error('Invalid contact ID or group ID provided for removeContactFromGroup mutation.', ['args' => $args]);
            throw new Exception("ID de contact ou de groupe invalide.");
        }

        try {
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for mutateRemoveContactFromGroup.');
                throw new Exception("User not authenticated");
            }
            $userId = $currentUser->getId();
            // --- End Authentication Handling ---

            // Verify the contact belongs to the current user
            $contact = $this->contactRepository->findById($contactId);
            if (!$contact || $contact->getUserId() !== $userId) {
                $this->logger->warning('User ' . $userId . ' attempted to remove contact ' . $contactId . ' that does not belong to them from a group.');
                return false; // Treat as not found for security
            }

            // Verify the group belongs to the current user
            $group = $this->contactGroupRepository->findById($groupId);
            if (!$group || $group->getUserId() !== $userId) {
                $this->logger->warning('User ' . $userId . ' attempted to remove a contact from group ' . $groupId . ' that does not belong to them.');
                return false; // Treat as not found for security
            }

            // Delete the membership
            $deleted = $this->membershipRepository->deleteByContactAndGroup($contactId, $groupId);
            if ($deleted) {
                $this->logger->info('Contact ' . $contactId . ' removed from group ' . $groupId . ' successfully.');
            } else {
                $this->logger->warning('Contact ' . $contactId . ' was not in group ' . $groupId . '.');
            }
            return $deleted;
        } catch (Exception $e) {
            $this->logger->error('Error in ContactGroupResolver::mutateRemoveContactFromGroup: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'addContactsToGroup' mutation.
     *
     * @param array<string, mixed> $args Contains 'contactIds' and 'groupId'
     * @param mixed $context
     * @return array<string, mixed>
     * @throws Exception
     */
    public function mutateAddContactsToGroup(array $args, $context): array
    {
        $contactIds = $args['contactIds'] ?? [];
        $groupId = (int)($args['groupId'] ?? 0);
        $this->logger->info('Executing ContactGroupResolver::mutateAddContactsToGroup for ' . count($contactIds) . ' contacts and group ID: ' . $groupId);

        if (empty($contactIds) || $groupId <= 0) {
            $this->logger->error('Invalid contact IDs or group ID provided for addContactsToGroup mutation.', ['args' => $args]);
            throw new Exception("IDs de contacts ou de groupe invalides.");
        }

        try {
            // --- Authentication/User Context Handling (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for mutateAddContactsToGroup.');
                throw new Exception("User not authenticated");
            }
            $userId = $currentUser->getId();
            // --- End Authentication Handling ---

            // Verify the group belongs to the current user
            $group = $this->contactGroupRepository->findById($groupId);
            if (!$group || $group->getUserId() !== $userId) {
                $this->logger->warning('User ' . $userId . ' attempted to add contacts to group ' . $groupId . ' that does not belong to them.');
                throw new Exception("Groupe non trouvé"); // Treat as not found for security
            }

            // Initialize result counters
            $successful = 0;
            $failed = 0;
            $memberships = [];
            $errors = [];

            // Start a transaction
            $this->membershipRepository->beginTransaction();

            try {
                foreach ($contactIds as $contactId) {
                    $contactId = (int)$contactId;

                    // Verify the contact belongs to the current user
                    $contact = $this->contactRepository->findById($contactId);
                    if (!$contact || $contact->getUserId() !== $userId) {
                        $failed++;
                        $errors[] = [
                            'contactId' => $contactId,
                            'message' => 'Contact non trouvé ou non autorisé'
                        ];
                        continue;
                    }

                    // Create a new membership
                    $membership = new ContactGroupMembership(
                        0, // ID will be generated by the database
                        $contactId,
                        $groupId
                        // createdAt handled by model/repository
                    );

                    try {
                        // Save the membership using the repository
                        $savedMembership = $this->membershipRepository->create($membership);
                        $successful++;
                        $memberships[] = $this->formatter->formatContactGroupMembership($savedMembership, $contact, $group);
                    } catch (Exception $e) {
                        $failed++;
                        $errors[] = [
                            'contactId' => $contactId,
                            'message' => $e->getMessage()
                        ];
                    }
                }

                // Commit the transaction
                $this->membershipRepository->commit();
            } catch (Exception $e) {
                // Rollback the transaction on error
                $this->membershipRepository->rollback();
                throw $e;
            }

            $this->logger->info('Added ' . $successful . ' contacts to group ' . $groupId . ' with ' . $failed . ' failures.');

            return [
                'status' => $failed === 0 ? 'success' : 'partial',
                'message' => $successful . ' contacts ajoutés au groupe, ' . $failed . ' échecs.',
                'successful' => $successful,
                'failed' => $failed,
                'memberships' => $memberships,
                'errors' => $errors
            ];
        } catch (Exception $e) {
            $this->logger->error('Error in ContactGroupResolver::mutateAddContactsToGroup: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }
}
