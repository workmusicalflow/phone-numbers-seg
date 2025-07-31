<?php

namespace App\Repositories\Interfaces;

use App\Entities\PhoneNumberSegment;

/**
 * Interface for PhoneNumberSegment repository
 */
interface PhoneNumberSegmentRepositoryInterface extends DoctrineRepositoryInterface
{
    /**
     * Find phone number segments by phone number ID
     * 
     * @param int $phoneNumberId The phone number ID
     * @return array The phone number segments
     */
    public function findByPhoneNumberId(int $phoneNumberId): array;

    /**
     * Find phone number segments by custom segment ID
     * 
     * @param int $customSegmentId The custom segment ID
     * @return array The phone number segments
     */
    public function findByCustomSegmentId(int $customSegmentId): array;

    /**
     * Delete phone number segments by phone number ID
     * 
     * @param int $phoneNumberId The phone number ID
     * @return bool True if successful
     */
    public function deleteByPhoneNumberId(int $phoneNumberId): bool;

    /**
     * Delete phone number segments by custom segment ID
     * 
     * @param int $customSegmentId The custom segment ID
     * @return bool True if successful
     */
    public function deleteByCustomSegmentId(int $customSegmentId): bool;

    /**
     * Create a new phone number segment
     * 
     * @param int $phoneNumberId The phone number ID
     * @param int $customSegmentId The custom segment ID
     * @return PhoneNumberSegment The created phone number segment
     */
    public function create(int $phoneNumberId, int $customSegmentId): PhoneNumberSegment;

    /**
     * Add a phone number to a custom segment
     * 
     * @param int $phoneNumberId The phone number ID
     * @param int $customSegmentId The custom segment ID
     * @return bool True if successful
     */
    public function addPhoneNumberToSegment(int $phoneNumberId, int $customSegmentId): bool;

    /**
     * Remove a phone number from a custom segment
     * 
     * @param int $phoneNumberId The phone number ID
     * @param int $customSegmentId The custom segment ID
     * @return bool True if successful
     */
    public function removePhoneNumberFromSegment(int $phoneNumberId, int $customSegmentId): bool;
}
