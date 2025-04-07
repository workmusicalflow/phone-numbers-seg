<?php

namespace App\Services\Interfaces;

use App\Models\PhoneNumber;
use App\Models\CustomSegment;

/**
 * Interface for custom segment matcher service
 */
interface CustomSegmentMatcherInterface
{
    /**
     * Find all custom segments that match a phone number
     * 
     * @param PhoneNumber $phoneNumber The phone number to match
     * @return array Array of matching CustomSegment objects
     */
    public function findMatchingSegments(PhoneNumber $phoneNumber): array;

    /**
     * Check if a phone number matches a custom segment
     * 
     * @param PhoneNumber $phoneNumber The phone number to check
     * @param CustomSegment $segment The custom segment to check against
     * @return bool True if the phone number matches the segment, false otherwise
     */
    public function matches(PhoneNumber $phoneNumber, CustomSegment $segment): bool;

    /**
     * Automatically assign matching segments to a phone number
     * 
     * @param PhoneNumber $phoneNumber The phone number to assign segments to
     * @return int Number of segments assigned
     */
    public function autoAssignSegments(PhoneNumber $phoneNumber): int;

    /**
     * Automatically assign matching segments to multiple phone numbers
     * 
     * @param array $phoneNumbers Array of PhoneNumber objects
     * @return array Associative array with phone number IDs as keys and number of segments assigned as values
     */
    public function batchAutoAssignSegments(array $phoneNumbers): array;
}
