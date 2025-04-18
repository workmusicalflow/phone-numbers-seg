<?php

namespace App\Repositories\Interfaces;

use App\Entities\ContactGroup;
use Exception;

/**
 * Interface for ContactGroup repository
 */
interface ContactGroupRepositoryInterface extends DoctrineRepositoryInterface
{
    /**
     * Find contact groups by user ID
     * 
     * @param int $userId The user ID
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The contact groups
     */
    public function findByUserId(int $userId, ?int $limit = null, ?int $offset = null): array;

    /**
     * Find multiple contact groups by their IDs, ensuring they belong to the specified user
     * 
     * @param array $ids Array of group IDs
     * @param int $userId The user ID
     * @return array The contact groups
     */
    public function findByIds(array $ids, int $userId): array;

    /**
     * Search contact groups
     * 
     * @param string $query The search query
     * @param array|null $fields The fields to search in
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The contact groups
     */
    public function search(string $query, ?array $fields = null, ?int $limit = null, ?int $offset = null): array;

    /**
     * Search contact groups by user ID
     * 
     * @param string $query The search query
     * @param int $userId The user ID
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The contact groups
     */
    public function searchByUserId(string $query, int $userId, ?int $limit = null, ?int $offset = null): array;

    /**
     * Count contact groups by user ID
     * 
     * @param int|null $userId The user ID (optional)
     * @return int The number of contact groups
     */
    public function countByUserId(?int $userId = null): int;

    /**
     * Get contacts in a group
     * 
     * @param int $groupId The group ID
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The contacts
     */
    public function getContactsInGroup(int $groupId, ?int $limit = null, ?int $offset = null): array;

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
}
