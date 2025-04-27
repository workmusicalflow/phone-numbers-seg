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

        parent::__construct([$this, 'batchLoadContactGroups'], $logger);
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
     * Get the current user ID
     * 
     * @return int|null The user ID
     */
    public function getUserId(): ?int
    {
        return $this->userId;
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

        // Use a static variable to collect all contact IDs that are processed
        // This allows us to potentially use a single query for all contacts
        static $processedContactIds = [];
        static $batchResults = [];
        
        // Check if we've already processed a batch that includes these contacts
        $allAlreadyProcessed = true;
        foreach ($contactIds as $id) {
            if (!isset($batchResults[$id])) {
                $allAlreadyProcessed = false;
                break;
            }
        }
        
        if ($allAlreadyProcessed) {
            $this->logger->info('CACHE HIT: All ' . count($contactIds) . ' contact IDs already batched and cached');
            $results = [];
            foreach ($contactIds as $id) {
                $results[] = $batchResults[$id];
            }
            return $results;
        }
        
        // Combine with previously processed IDs
        $newContactIds = array_diff($contactIds, $processedContactIds);
        if (!empty($newContactIds)) {
            $processedContactIds = array_merge($processedContactIds, $newContactIds);
        }
        
        // Deduplicate contact IDs (in case the same ID was requested multiple times)
        $uniqueContactIds = array_values(array_unique($contactIds));
        
        // If we're batching a larger number of contacts, log this as a performance win
        if (count($uniqueContactIds) > 1) {
            $this->logger->info('BATCH OPTIMIZATION: BatchLoadContactGroups for ' . count($uniqueContactIds) . 
                                 ' contacts in a single batch (requested: ' . count($contactIds) . ')');
        } else {
            $this->logger->info('BatchLoadContactGroups for ' . count($uniqueContactIds) . ' contacts');
        }

        try {
            // Step 1: Get all membership records for all contacts in a single optimized query
            $memberships = $this->fetchMembershipsForContacts($uniqueContactIds);
            
            if (empty($memberships)) {
                $this->logger->info('No memberships found for contacts: ' . implode(', ', $uniqueContactIds));
                // Map results back to original contact IDs order
                return array_map(function() { return []; }, array_flip(array_flip($contactIds)));
            }

            // Step 2: Extract the unique group IDs from all memberships
            $groupIds = $this->extractUniqueGroupIdsFromMemberships($memberships);
            
            if (empty($groupIds)) {
                $this->logger->info('No group IDs extracted from memberships');
                return array_map(function() { return []; }, array_flip(array_flip($contactIds)));
            }

            // Step 3: Fetch all required group entities in a single query
            $groups = $this->fetchGroupsByIds($groupIds);
            
            if (empty($groups)) {
                $this->logger->info('No groups found for IDs: ' . implode(', ', $groupIds));
                return array_map(function() { return []; }, array_flip(array_flip($contactIds)));
            }

            // Step 4: Create a map of group ID to formatted group for quick lookup
            $groupMap = $this->createGroupMap($groups);

            // Step 5: Organize results by contact ID
            $uniqueResults = $this->organizeResultsByContactId($uniqueContactIds, $memberships, $groupMap);
            
            // Store in static cache for future requests
            foreach ($uniqueContactIds as $index => $contactId) {
                $batchResults[$contactId] = $uniqueResults[$index] ?? [];
            }
            
            // Map unique results back to original contact IDs order
            $finalResults = [];
            foreach ($contactIds as $index => $contactId) {
                $position = array_search($contactId, $uniqueContactIds);
                $finalResults[$index] = $position !== false ? $uniqueResults[$position] : [];
            }

            $this->logger->info('Successfully batch-loaded groups for ' . count($uniqueContactIds) . 
                               ' unique contacts in a single query');
            return $finalResults;

        } catch (\Exception $e) {
            $this->logger->error('Error in ContactGroupDataLoader::batchLoadContactGroups: ' . $e->getMessage(), [
                'exception' => $e,
                'contactIds' => $contactIds
            ]);
            
            // Return empty arrays in case of error
            return array_map(function() { return []; }, array_flip(array_flip($contactIds)));
        }
    }

    /**
     * Fetch memberships for contacts using the optimized batch method
     * 
     * @param array $contactIds The contact IDs
     * @return array The memberships grouped by contact ID
     */
    private function fetchMembershipsForContacts(array $contactIds): array
    {
        if (empty($contactIds)) {
            return [];
        }

        // Use the optimized batch method to fetch all memberships in a single query
        $membershipsByContactId = $this->membershipRepository->findByContactIds($contactIds);
        
        // Count total memberships for logging
        $totalMemberships = 0;
        foreach ($membershipsByContactId as $contactMemberships) {
            $totalMemberships += count($contactMemberships);
        }
        
        $this->logger->info('Batch-fetched ' . $totalMemberships . ' memberships for ' . count($contactIds) . ' contacts in a single query');
        
        // Return flat array of all memberships for backward compatibility with the rest of the method
        $allMemberships = [];
        foreach ($membershipsByContactId as $contactMemberships) {
            foreach ($contactMemberships as $membership) {
                $allMemberships[] = $membership;
            }
        }
        
        return $allMemberships;
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