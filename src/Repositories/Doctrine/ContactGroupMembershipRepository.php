<?php

namespace App\Repositories\Doctrine;

use App\Entities\ContactGroupMembership;
use App\Repositories\Interfaces\ContactGroupMembershipRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

/**
 * ContactGroupMembership repository using Doctrine ORM
 * 
 * This repository provides methods to access and manipulate ContactGroupMembership entities.
 */
class ContactGroupMembershipRepository extends BaseRepository implements ContactGroupMembershipRepositoryInterface
{
    /**
     * Constructor
     * 
     * @param EntityManagerInterface $entityManager The entity manager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, ContactGroupMembership::class);
    }
    
    /**
     * Find memberships by multiple contact IDs in a single query (optimized batch loading)
     * 
     * @param array $contactIds Array of contact IDs
     * @return array The memberships grouped by contact ID
     */
    public function findByContactIds(array $contactIds): array
    {
        if (empty($contactIds)) {
            return [];
        }
        
        // Use a cache key for efficient query execution and to avoid duplicates
        $sortedIds = $contactIds;
        sort($sortedIds);
        $cacheKey = md5(json_encode($sortedIds));
        static $queryCache = [];

        // Check if we've already executed this exact query in this request
        if (isset($queryCache[$cacheKey])) {
            return $queryCache[$cacheKey];
        }
        
        // Use a single optimized query with IN clause to get all memberships at once
        // This is far more efficient than individual queries
        $queryBuilder = $this->createQueryBuilder('m');
        $queryBuilder->select('m')
            ->where($queryBuilder->expr()->in('m.contactId', ':contactIds'))
            ->setParameter('contactIds', $contactIds)
            ->orderBy('m.contactId', 'ASC');
            
        // Try to add an index hint if the database supports it
        try {
            $connection = $this->getEntityManager()->getConnection();
            $driver = $connection->getDriver();
            $driverClass = get_class($driver);
            
            // Check if we're using MySQL by looking at the driver class name
            if (strpos($driverClass, 'MySQL') !== false || strpos($driverClass, 'pdo_mysql') !== false) {
                $queryBuilder->from(ContactGroupMembership::class, 'm', 'USE INDEX (idx_contact_group_memberships_contact_id)');
            }
        } catch (\Exception $e) {
            // If we can't determine the driver or it doesn't support hints, just continue without the hint
        }
        
        $memberships = $queryBuilder->getQuery()->getResult();
        
        // Group memberships by contact ID for easy retrieval by the DataLoader
        $membershipsByContactId = [];
        foreach ($contactIds as $contactId) {
            $membershipsByContactId[$contactId] = [];
        }
        
        // Efficiently distribute the memberships to their respective contact IDs
        foreach ($memberships as $membership) {
            $contactId = $membership->getContactId();
            $membershipsByContactId[$contactId][] = $membership;
        }
        
        // Cache the result for this request
        $queryCache[$cacheKey] = $membershipsByContactId;
        
        return $membershipsByContactId;
    }

    /**
     * Find memberships by contact ID
     * 
     * @param int $contactId The contact ID
     * @return array The memberships
     */
    public function findByContactId(int $contactId): array
    {
        return $this->findBy(['contactId' => $contactId]);
    }

    /**
     * Find memberships by group ID
     * 
     * @param int $groupId The group ID
     * @return array The memberships
     */
    public function findByGroupId(int $groupId): array
    {
        return $this->findBy(['groupId' => $groupId]);
    }

    /**
     * Count memberships by group ID
     * 
     * @param int $groupId The group ID
     * @return int The number of memberships
     */
    public function countByGroupId(int $groupId): int
    {
        return $this->count(['groupId' => $groupId]);
    }

    /**
     * Count memberships by contact ID
     * 
     * @param int $contactId The contact ID
     * @return int The number of memberships
     */
    public function countByContactId(int $contactId): int
    {
        return $this->count(['contactId' => $contactId]);
    }

    /**
     * Add a contact to a group
     * 
     * @param int $contactId The contact ID
     * @param int $groupId The group ID
     * @return bool True if the contact was added to the group
     */
    public function addContactToGroup(int $contactId, int $groupId): bool
    {
        try {
            // Check if the membership already exists
            $membership = $this->findOneBy([
                'contactId' => $contactId,
                'groupId' => $groupId
            ]);

            if ($membership !== null) {
                // Membership already exists
                return true;
            }

            // Create a new membership
            $membership = new ContactGroupMembership();
            $membership->setContactId($contactId);
            $membership->setGroupId($groupId);

            $this->save($membership);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Remove a contact from a group
     * 
     * @param int $contactId The contact ID
     * @param int $groupId The group ID
     * @return bool True if the contact was removed from the group
     */
    public function removeContactFromGroup(int $contactId, int $groupId): bool
    {
        try {
            $membership = $this->findOneBy([
                'contactId' => $contactId,
                'groupId' => $groupId
            ]);

            if ($membership === null) {
                // Membership doesn't exist
                return true;
            }

            $this->delete($membership);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Delete memberships by contact ID
     * 
     * @param int $contactId The contact ID
     * @return bool True if the memberships were deleted
     */
    public function deleteByContactId(int $contactId): bool
    {
        try {
            $memberships = $this->findByContactId($contactId);

            foreach ($memberships as $membership) {
                $this->delete($membership);
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Delete memberships by group ID
     * 
     * @param int $groupId The group ID
     * @return bool True if the memberships were deleted
     */
    public function deleteByGroupId(int $groupId): bool
    {
        try {
            $memberships = $this->findByGroupId($groupId);

            foreach ($memberships as $membership) {
                $this->delete($membership);
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Find membership by contact ID and group ID
     * 
     * @param int $contactId The contact ID
     * @param int $groupId The group ID
     * @return ContactGroupMembership|null The membership or null if not found
     */
    public function findByContactIdAndGroupId(int $contactId, int $groupId): ?ContactGroupMembership
    {
        return $this->findOneBy([
            'contactId' => $contactId,
            'groupId' => $groupId
        ]);
    }
}
