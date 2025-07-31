<?php

namespace App\Repositories\Interfaces;

use App\Entities\AdminContact;

/**
 * Interface for AdminContactRepository
 */
interface AdminContactRepositoryInterface extends
    RepositoryInterface,
    ReadRepositoryInterface,
    WriteRepositoryInterface,
    DeleteRepositoryInterface,
    CountableRepositoryInterface
{
    /**
     * Find an admin contact by its ID.
     *
     * @param int $id The ID of the contact.
     * @return AdminContact|null The contact or null if not found.
     */
    public function findById($id); // Already in ReadRepositoryInterface? Assuming specific return type needed.

    /**
     * Find an admin contact by phone number.
     *
     * @param string $phoneNumber The phone number to search for.
     * @return AdminContact|null The contact or null if not found.
     */
    public function findByPhoneNumber(string $phoneNumber): ?AdminContact;

    /**
     * Find all admin contacts with pagination.
     *
     * @param int $limit Maximum number of contacts to return.
     * @param int $offset Number of contacts to skip.
     * @return AdminContact[] An array of AdminContact entities.
     */
    public function findAll(?int $limit = 100, ?int $offset = 0): array; // Already in ReadRepositoryInterface? Assuming specific return type needed.

    /**
     * Find all admin contacts belonging to a specific custom segment.
     *
     * @param int $segmentId The ID of the custom segment.
     * @param int $limit Maximum number of contacts to return.
     * @param int $offset Number of contacts to skip.
     * @return AdminContact[] An array of AdminContact entities.
     */
    public function findBySegmentId(int $segmentId, int $limit = 100, int $offset = 0): array;

    /**
     * Count all admin contacts.
     *
     * @return int The total number of admin contacts.
     */
    public function countAll(): int;

    /**
     * Count admin contacts in a specific segment.
     *
     * @param int $segmentId The ID of the custom segment.
     * @return int The number of contacts in the segment.
     */
    public function countBySegmentId(int $segmentId): int;

    /**
     * Save an admin contact (insert or update).
     *
     * @param AdminContact $contact The contact entity to save.
     * @return AdminContact The saved contact entity.
     */
    public function save($contact); // Already in WriteRepositoryInterface? Assuming specific return type needed.

    /**
     * Delete an admin contact by its ID.
     *
     * @param int $id The ID of the contact to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function deleteById($id): bool; // Already in DeleteRepositoryInterface? Assuming specific return type needed.
}
