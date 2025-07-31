<?php

namespace App\Repositories\Interfaces;

use App\Entities\PhoneNumber;

/**
 * Interface for PhoneNumber repository
 */
interface PhoneNumberRepositoryInterface extends DoctrineRepositoryInterface
{
    /**
     * Find a phone number by number
     * 
     * @param string $number The phone number
     * @return PhoneNumber|null The phone number or null if not found
     */
    public function findByNumber(string $number): ?PhoneNumber;

    /**
     * Find phone numbers by custom segment
     * 
     * @param int $segmentId The segment ID
     * @param int $limit Maximum number of phone numbers to return
     * @param int $offset Number of phone numbers to skip
     * @return array The phone numbers
     */
    public function findByCustomSegment(int $segmentId, int $limit = 100, int $offset = 0): array;

    /**
     * Count phone numbers by custom segment
     * 
     * @param int $segmentId The segment ID
     * @return int The number of phone numbers
     */
    public function countByCustomSegment(int $segmentId): int;

    /**
     * Search phone numbers
     * 
     * @param string $query The search query
     * @param int $limit Maximum number of phone numbers to return
     * @param int $offset Number of phone numbers to skip
     * @return array The phone numbers
     */
    public function search(string $query, int $limit = 100, int $offset = 0): array;

    /**
     * Find phone numbers by advanced filters
     * 
     * @param array $filters Filters to apply (operator, country, dateFrom, dateTo, segment)
     * @param int $limit Maximum number of phone numbers to return
     * @param int $offset Number of phone numbers to skip
     * @return array The phone numbers
     */
    public function findByFilters(array $filters, int $limit = 100, int $offset = 0): array;

    /**
     * Create a new phone number
     * 
     * @param string $number The phone number
     * @param string|null $civility The civility
     * @param string|null $firstName The first name
     * @param string|null $name The last name
     * @param string|null $company The company
     * @param string|null $sector The sector
     * @param string|null $notes The notes
     * @return PhoneNumber The created phone number
     */
    public function create(
        string $number,
        ?string $civility = null,
        ?string $firstName = null,
        ?string $name = null,
        ?string $company = null,
        ?string $sector = null,
        ?string $notes = null
    ): PhoneNumber;
}
