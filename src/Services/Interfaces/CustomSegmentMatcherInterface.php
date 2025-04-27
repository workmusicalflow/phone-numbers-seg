<?php

namespace App\Services\Interfaces;

use App\Entities\PhoneNumber; // Use Doctrine Entity
use App\Entities\CustomSegment; // Use Doctrine Entity

/**
 * Interface for custom segment matcher service
 */
interface CustomSegmentMatcherInterface
{
    /**
     * Find all custom segments that match a phone number
     * 
     * @param PhoneNumber $phoneNumber The phone number entity to match // Use Doctrine Entity
     * @return array Array of matching CustomSegment objects // Returns Doctrine Entities
     */
    public function findMatchingSegments(PhoneNumber $phoneNumber): array; // Use Doctrine Entity

    /**
     * Check if a phone number matches a custom segment
     * 
     * @param PhoneNumber $phoneNumber The phone number entity to check // Use Doctrine Entity
     * @param CustomSegment $segment The custom segment entity to check against // Use Doctrine Entity
     * @return bool True if the phone number matches the segment, false otherwise
     */
    public function matches(PhoneNumber $phoneNumber, CustomSegment $segment): bool; // Use Doctrine Entities

    /**
     * Automatically assign matching segments to a phone number
     * 
     * @param PhoneNumber $phoneNumber The phone number entity to assign segments to // Use Doctrine Entity
     * @return int Number of segments assigned
     */
    public function autoAssignSegments(PhoneNumber $phoneNumber): int; // Use Doctrine Entity

    /**
     * Automatically assign matching segments to multiple phone numbers
     * 
     * @param array $phoneNumbers Array of PhoneNumber entities // Use Doctrine Entities
     * @return array Associative array with phone number IDs as keys and number of segments assigned as values
     */
    public function batchAutoAssignSegments(array $phoneNumbers): array; // Use Doctrine Entities
}
