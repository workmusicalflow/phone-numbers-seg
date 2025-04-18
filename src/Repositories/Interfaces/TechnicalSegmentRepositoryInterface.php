<?php

namespace App\Repositories\Interfaces;

use App\Entities\Segment;

/**
 * Interface for TechnicalSegment repository
 */
interface TechnicalSegmentRepositoryInterface extends DoctrineRepositoryInterface
{
    /**
     * Find segments by phone number ID
     * 
     * @param int $phoneNumberId
     * @return array
     */
    public function findByPhoneNumberId(int $phoneNumberId): array;

    /**
     * Find segments by type
     * 
     * @param string $segmentType
     * @return array
     */
    public function findByType(string $segmentType): array;

    /**
     * Delete segments by phone number ID
     * 
     * @param int $phoneNumberId
     * @return bool
     */
    public function deleteByPhoneNumberId(int $phoneNumberId): bool;

    /**
     * Create a new segment
     * 
     * @param string $segmentType
     * @param string $value
     * @param int $phoneNumberId
     * @return Segment
     */
    public function create(string $segmentType, string $value, int $phoneNumberId): Segment;
}
