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
}
