<?php

namespace App\GraphQL\DataLoaders;

use App\Repositories\Interfaces\ContactGroupRepositoryInterface;
use App\Repositories\Interfaces\ContactGroupMembershipRepositoryInterface;
use App\GraphQL\Formatters\GraphQLFormatterInterface;
use Psr\Log\LoggerInterface;

/**
 * ContactGroupDataLoader
 * 
 * DataLoader implementation for contact groups relationship.
 */
class ContactGroupDataLoader extends DataLoader
{
    /**
     * @var ContactGroupMembershipRepositoryInterface
     */
    private $membershipRepository;

    /**
     * @var ContactGroupRepositoryInterface
     */
    private $groupRepository;

    /**
     * @var GraphQLFormatterInterface
     */
    private $formatter;

    /**
     * @var int
     */
    private $userId;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     * 
     * @param ContactGroupMembershipRepositoryInterface $membershipRepository Membership repository
     * @param ContactGroupRepositoryInterface $groupRepository Group repository
     * @param GraphQLFormatterInterface $formatter GraphQL formatter
     * @param LoggerInterface $logger Logger
     */
    public function __construct(
        ContactGroupMembershipRepositoryInterface $membershipRepository,
        ContactGroupRepositoryInterface $groupRepository,
        GraphQLFormatterInterface $formatter,
        LoggerInterface $logger
    ) {
        $this->membershipRepository = $membershipRepository;
        $this->groupRepository = $groupRepository;
        $this->formatter = $formatter;
        $this->logger = $logger;

        parent::__construct([$this, 'batchLoadContactGroups']);
    }

    /**
     * Set the current user ID
     * 
     * @param int $userId The user ID
     * @return self
     */
    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Batch load function for contact groups
     * 
     * @param array $contactIds The contact IDs to load groups for
     * @return array Array of contact groups arrays indexed by contact ID
     */
    public function batchLoadContactGroups(array $contactIds): array
    {
        if (empty($contactIds)) {
            return [];
        }

        $this->logger->info('BatchLoadContactGroups for ' . count($contactIds) . ' contacts');

        try {
            // Step 1: Get all membership records for all contacts in a single query
            $memberships = $this->fetchMembershipsForContacts($contactIds);
            
            if (empty($memberships)) {
                $this->logger->info('No memberships found for contacts: ' . implode(', ', $contactIds));
                return array_fill(0, count($contactIds), []);
            }

            // Step 2: Extract the unique group IDs from all memberships
            $groupIds = $this->extractUniqueGroupIdsFromMemberships($memberships);
            
            if (empty($groupIds)) {
                $this->logger->info('No group IDs extracted from memberships');
                return array_fill(0, count($contactIds), []);
            }

            // Step 3: Fetch all required group entities in a single query
            $groups = $this->fetchGroupsByIds($groupIds);
            
            if (empty($groups)) {
                $this->logger->info('No groups found for IDs: ' . implode(', ', $groupIds));
                return array_fill(0, count($contactIds), []);
            }

            // Step 4: Create a map of group ID to formatted group for quick lookup
            $groupMap = $this->createGroupMap($groups);

            // Step 5: Organize results by contact ID
            $results = $this->organizeResultsByContactId($contactIds, $memberships, $groupMap);

            $this->logger->info('Successfully loaded groups for ' . count($contactIds) . ' contacts');
            return $results;

        } catch (\Exception $e) {
            $this->logger->error('Error in ContactGroupDataLoader::batchLoadContactGroups: ' . $e->getMessage(), [
                'exception' => $e,
                'contactIds' => $contactIds
            ]);
            
            // Return empty arrays in case of error
            return array_fill(0, count($contactIds), []);
        }
    }

    /**
     * Fetch memberships for contacts
     * 
     * @param array $contactIds The contact IDs
     * @return array The memberships
     */
    private function fetchMembershipsForContacts(array $contactIds): array
    {
        // Instead of directly accessing the entity manager, use repository methods
        // that are available on the repository interface
        if (empty($contactIds)) {
            return [];
        }

        // Create criteria for IN query
        $memberships = [];
        foreach ($contactIds as $contactId) {
            $contactMemberships = $this->membershipRepository->findByContactId($contactId);
            foreach ($contactMemberships as $membership) {
                $memberships[] = $membership;
            }
        }
        
        $this->logger->info('Fetched ' . count($memberships) . ' memberships for ' . count($contactIds) . ' contacts');
        
        return $memberships;
    }

    /**
     * Extract unique group IDs from memberships
     * 
     * @param array $memberships The memberships
     * @return array The unique group IDs
     */
    private function extractUniqueGroupIdsFromMemberships(array $memberships): array
    {
        $groupIds = [];
        foreach ($memberships as $membership) {
            $groupIds[] = $membership->getGroupId();
        }
        
        return array_unique($groupIds);
    }

    /**
     * Fetch groups by IDs
     * 
     * @param array $groupIds The group IDs
     * @return array The groups
     */
    private function fetchGroupsByIds(array $groupIds): array
    {
        // Ensure we only return groups belonging to the current user for security
        if (empty($this->userId)) {
            $this->logger->warning('No user ID set in ContactGroupDataLoader');
            return [];
        }
        
        $groups = $this->groupRepository->findByIds($groupIds, $this->userId);
        $this->logger->info('Fetched ' . count($groups) . ' groups for ' . count($groupIds) . ' group IDs');
        
        return $groups;
    }

    /**
     * Create a map of group ID to formatted group
     * 
     * @param array $groups The groups
     * @return array The group map
     */
    private function createGroupMap(array $groups): array
    {
        $groupMap = [];
        foreach ($groups as $group) {
            // Get contact count for the group - use existing method in repository
            // We'll manually count memberships from our repository
            try {
                $contactCount = $this->membershipRepository->countByGroupId($group->getId());
            } catch (\Exception $e) {
                // Fallback to approximation or another method if needed
                $this->logger->warning('Error counting contacts for group ' . $group->getId() . ': ' . $e->getMessage());
                $contactCount = 0;
            }
            
            // Format the group
            $groupMap[$group->getId()] = $this->formatter->formatContactGroup($group, $contactCount);
        }
        
        return $groupMap;
    }

    /**
     * Organize results by contact ID
     * 
     * @param array $contactIds The contact IDs
     * @param array $memberships The memberships
     * @param array $groupMap The group map
     * @return array The results organized by contact ID
     */
    private function organizeResultsByContactId(array $contactIds, array $memberships, array $groupMap): array
    {
        // Organize memberships by contact ID
        $membershipsByContactId = [];
        foreach ($memberships as $membership) {
            $contactId = $membership->getContactId();
            $groupId = $membership->getGroupId();
            
            if (!isset($membershipsByContactId[$contactId])) {
                $membershipsByContactId[$contactId] = [];
            }
            
            if (isset($groupMap[$groupId])) {
                $membershipsByContactId[$contactId][] = $groupMap[$groupId];
            }
        }
        
        // Prepare results in the same order as input contactIds
        $results = [];
        foreach ($contactIds as $contactId) {
            $results[] = $membershipsByContactId[$contactId] ?? [];
        }
        
        return $results;
    }
}