<?php

namespace App\Repositories\Interfaces;

use App\Entities\Contact;
use Exception;

/**
 * Interface for Contact repository
 */
interface ContactRepositoryInterface extends DoctrineRepositoryInterface
{
    /**
     * Find contacts by user ID
     * 
     * @param int $userId The user ID
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The contacts
     */
    public function findByUserId(int $userId, ?int $limit = null, ?int $offset = null): array;

    /**
     * Find contacts by group ID
     * 
     * @param int $groupId The group ID
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The contacts
     */
    public function findByGroupId(int $groupId, ?int $limit = null, ?int $offset = null): array;

    /**
     * Search contacts
     * 
     * @param string $query The search query
     * @param array|null $fields The fields to search in
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The contacts
     */
    public function search(string $query, ?array $fields = null, ?int $limit = null, ?int $offset = null): array;

    /**
     * Search contacts by user ID
     * 
     * @param string $query The search query
     * @param int $userId The user ID
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The contacts
     */
    public function searchByUserId(string $query, int $userId, ?int $limit = null, ?int $offset = null): array;

    /**
     * Count contacts with optional criteria
     * 
     * @param array $criteria Optional criteria to filter entities
     * @return int The number of contacts
     */
    public function count(array $criteria = []): int;

    /**
     * Count contacts by user ID
     * 
     * @param int|null $userId The user ID (optional)
     * @return int The number of contacts
     */
    public function countByUserId(?int $userId = null): int;

    /**
     * Bulk create contacts
     * 
     * @param array $contacts Array of contact data
     * @param int $userId The user ID
     * @return array The created contacts
     * @throws Exception If an error occurs
     */
    public function bulkCreate(array $contacts, int $userId): array;

    /**
     * Find a contact by phone number
     * 
     * @param string $phoneNumber The phone number to search for
     * @return Contact|null The contact if found, null otherwise
     */
    public function findByPhoneNumber(string $phoneNumber): ?Contact;
}
