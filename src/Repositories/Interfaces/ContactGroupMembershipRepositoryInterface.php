<?php

namespace App\Repositories\Interfaces;

use App\Entities\ContactGroupMembership;
use Exception;

/**
 * Interface for ContactGroupMembership repository
 */
interface ContactGroupMembershipRepositoryInterface extends DoctrineRepositoryInterface
{
    /**
     * Find memberships by contact ID
     * 
     * @param int $contactId The contact ID
     * @return array The memberships
     */
    public function findByContactId(int $contactId): array;

    /**
     * Find memberships by group ID
     * 
     * @param int $groupId The group ID
     * @return array The memberships
     */
    public function findByGroupId(int $groupId): array;

    /**
     * Count memberships by group ID
     * 
     * @param int $groupId The group ID
     * @return int The number of memberships
     */
    public function countByGroupId(int $groupId): int;

    /**
     * Count memberships by contact ID
     * 
     * @param int $contactId The contact ID
     * @return int The number of memberships
     */
    public function countByContactId(int $contactId): int;

    /**
     * Add a contact to a group
     * 
     * @param int $contactId The contact ID
     * @param int $groupId The group ID
     * @return bool True if the contact was added to the group
     */
    public function addContactToGroup(int $contactId, int $groupId): bool;

    /**
     * Remove a contact from a group
     * 
     * @param int $contactId The contact ID
     * @param int $groupId The group ID
     * @return bool True if the contact was removed from the group
     */
    public function removeContactFromGroup(int $contactId, int $groupId): bool;

    /**
     * Delete memberships by contact ID
     * 
     * @param int $contactId The contact ID
     * @return bool True if the memberships were deleted
     */
    public function deleteByContactId(int $contactId): bool;

    /**
     * Delete memberships by group ID
     * 
     * @param int $groupId The group ID
     * @return bool True if the memberships were deleted
     */
    public function deleteByGroupId(int $groupId): bool;
}
