<?php

namespace App\Services;

use App\Models\PhoneNumber;
use App\Models\CustomSegment;
use App\Repositories\CustomSegmentRepository;
use App\Services\Interfaces\CustomSegmentMatcherInterface;
use App\Services\Interfaces\RegexValidatorInterface;

/**
 * Service for matching phone numbers against custom segments
 */
class CustomSegmentMatcher implements CustomSegmentMatcherInterface
{
    /**
     * @var CustomSegmentRepository
     */
    private CustomSegmentRepository $customSegmentRepository;

    /**
     * @var RegexValidatorInterface
     */
    private RegexValidatorInterface $regexValidator;

    /**
     * Constructor
     * 
     * @param CustomSegmentRepository $customSegmentRepository
     * @param RegexValidatorInterface $regexValidator
     */
    public function __construct(
        CustomSegmentRepository $customSegmentRepository,
        RegexValidatorInterface $regexValidator
    ) {
        $this->customSegmentRepository = $customSegmentRepository;
        $this->regexValidator = $regexValidator;
    }

    /**
     * Find all custom segments that match a phone number
     * 
     * @param PhoneNumber $phoneNumber The phone number to match
     * @return array Array of matching CustomSegment objects
     */
    public function findMatchingSegments(PhoneNumber $phoneNumber): array
    {
        // Get all custom segments
        $allSegments = $this->customSegmentRepository->findAll();

        // Filter segments that match the phone number
        $matchingSegments = [];
        foreach ($allSegments as $segment) {
            if ($this->matches($phoneNumber, $segment)) {
                $matchingSegments[] = $segment;
            }
        }

        return $matchingSegments;
    }

    /**
     * Check if a phone number matches a custom segment
     * 
     * @param PhoneNumber $phoneNumber The phone number to check
     * @param CustomSegment $segment The custom segment to check against
     * @return bool True if the phone number matches the segment, false otherwise
     */
    public function matches(PhoneNumber $phoneNumber, CustomSegment $segment): bool
    {
        // If the segment doesn't have a pattern, it can't match
        $pattern = $segment->getPattern();
        if (empty($pattern)) {
            return false;
        }

        // Get the phone number as a string
        $number = $phoneNumber->getNumber();

        // Check if the pattern matches the phone number
        return $this->regexValidator->test($pattern, $number);
    }

    /**
     * Automatically assign matching segments to a phone number
     * 
     * @param PhoneNumber $phoneNumber The phone number to assign segments to
     * @return int Number of segments assigned
     */
    public function autoAssignSegments(PhoneNumber $phoneNumber): int
    {
        // Find matching segments
        $matchingSegments = $this->findMatchingSegments($phoneNumber);

        // If no matching segments, return 0
        if (empty($matchingSegments)) {
            return 0;
        }

        // Assign segments to the phone number
        $count = 0;
        foreach ($matchingSegments as $segment) {
            // Skip if the phone number is already in this segment
            if ($this->isPhoneNumberInSegment($phoneNumber, $segment)) {
                continue;
            }

            // Add the phone number to the segment
            $this->customSegmentRepository->addPhoneNumberToSegment(
                $phoneNumber->getId(),
                $segment->getId()
            );

            $count++;
        }

        return $count;
    }

    /**
     * Automatically assign matching segments to multiple phone numbers
     * 
     * @param array $phoneNumbers Array of PhoneNumber objects
     * @return array Associative array with phone number IDs as keys and number of segments assigned as values
     */
    public function batchAutoAssignSegments(array $phoneNumbers): array
    {
        $results = [];

        foreach ($phoneNumbers as $phoneNumber) {
            $results[$phoneNumber->getId()] = $this->autoAssignSegments($phoneNumber);
        }

        return $results;
    }

    /**
     * Check if a phone number is already in a segment
     * 
     * @param PhoneNumber $phoneNumber The phone number to check
     * @param CustomSegment $segment The segment to check
     * @return bool True if the phone number is in the segment, false otherwise
     */
    private function isPhoneNumberInSegment(PhoneNumber $phoneNumber, CustomSegment $segment): bool
    {
        // Get segments for the phone number
        $segments = $this->customSegmentRepository->findByPhoneNumberId($phoneNumber->getId());

        // Check if the segment is in the list
        foreach ($segments as $existingSegment) {
            if ($existingSegment->getId() === $segment->getId()) {
                return true;
            }
        }

        return false;
    }
}
