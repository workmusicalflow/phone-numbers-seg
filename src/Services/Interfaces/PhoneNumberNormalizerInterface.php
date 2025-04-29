<?php

namespace App\Services\Interfaces;

/**
 * Interface for phone number normalization services
 * 
 * This interface defines the contract for services that handle
 * phone number normalization and format conversions.
 */
interface PhoneNumberNormalizerInterface
{
    /**
     * Normalize a phone number to E.164 format (+225XXXXXXXXXX)
     * 
     * @param string $phoneNumber The phone number to normalize
     * @return string The normalized phone number
     */
    public function normalize(string $phoneNumber): string;

    /**
     * Convert a phone number to local format (without country code)
     * 
     * @param string $phoneNumber The phone number to convert
     * @return string The phone number in local format
     */
    public function toLocalFormat(string $phoneNumber): string;

    /**
     * Determine if a phone number is valid
     * 
     * @param string $phoneNumber The phone number to validate
     * @return bool True if valid, false otherwise
     */
    public function isValid(string $phoneNumber): bool;

    /**
     * Get possible formats for a phone number
     * Used for database queries when we need to search for all variations
     * 
     * @param string $phoneNumber The phone number
     * @return array Array of possible formats
     */
    public function getPossibleFormats(string $phoneNumber): array;
}
