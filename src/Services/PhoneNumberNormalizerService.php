<?php

namespace App\Services;

use App\Services\Interfaces\PhoneNumberNormalizerInterface;

/**
 * Service for normalizing and standardizing phone numbers
 * 
 * This service provides methods to convert phone numbers to standardized formats
 * and validate them against country-specific patterns.
 */
class PhoneNumberNormalizerService implements PhoneNumberNormalizerInterface
{
    /**
     * Country code for Côte d'Ivoire
     */
    private const COUNTRY_CODE = '225';
    
    /**
     * Normalize a phone number to E.164 format (+225XXXXXXX)
     * 
     * @param string $phoneNumber The phone number to normalize
     * @return string The normalized phone number
     */
    public function normalize(string $phoneNumber): string
    {
        // Remove all non-digit characters
        $cleaned = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // If already in E.164 format (starts with +225), return as is
        if (preg_match('/^\+225\d{8,10}$/', $cleaned)) {
            return $cleaned;
        }
        
        // If starts with 225, add + prefix
        if (preg_match('/^225\d{8,10}$/', $cleaned)) {
            return '+' . $cleaned;
        }
        
        // If starts with 0, remove 0 and add +225
        if (preg_match('/^0\d{8,9}$/', $cleaned)) {
            return '+225' . substr($cleaned, 1);
        }
        
        // If 8-10 digits without prefix, assume it's a local number
        if (preg_match('/^\d{8,10}$/', $cleaned)) {
            // If starts with 0, remove it
            if (substr($cleaned, 0, 1) === '0') {
                return '+225' . substr($cleaned, 1);
            }
            // Otherwise add +225 directly
            return '+225' . $cleaned;
        }
        
        // If we can't determine format, return original cleaned string
        return $cleaned;
    }
    
    /**
     * Convert a phone number to local format (without country code)
     * 
     * @param string $phoneNumber The phone number to convert
     * @return string The phone number in local format
     */
    public function toLocalFormat(string $phoneNumber): string
    {
        // First normalize to E.164
        $normalized = $this->normalize($phoneNumber);
        
        // If it starts with +225, remove it
        if (strpos($normalized, '+225') === 0) {
            $localNumber = substr($normalized, 4);
            
            // Add leading 0 if needed
            if (strlen($localNumber) === 8 || strlen($localNumber) === 9) {
                return '0' . $localNumber;
            }
            
            return $localNumber;
        }
        
        // If we couldn't normalize to E.164, return original
        return $phoneNumber;
    }
    
    /**
     * Determine if a phone number is valid for Côte d'Ivoire
     * 
     * @param string $phoneNumber The phone number to validate
     * @return bool True if valid, false otherwise
     */
    public function isValid(string $phoneNumber): bool
    {
        $normalized = $this->normalize($phoneNumber);
        
        // Regular expression for Côte d'Ivoire phone numbers in E.164 format
        // +225 followed by 8-10 digits
        return preg_match('/^\+225\d{8,10}$/', $normalized) === 1;
    }
    
    /**
     * Get possible formats for a phone number
     * Used for database queries when we need to search for all variations
     * 
     * @param string $phoneNumber The phone number
     * @return array Array of possible formats
     */
    public function getPossibleFormats(string $phoneNumber): array
    {
        $formats = [];
        
        // Always include original format
        $formats[] = $phoneNumber;
        
        // Add E.164 format
        $normalized = $this->normalize($phoneNumber);
        if (!in_array($normalized, $formats)) {
            $formats[] = $normalized;
        }
        
        // Add local format
        $local = $this->toLocalFormat($phoneNumber);
        if (!in_array($local, $formats)) {
            $formats[] = $local;
        }
        
        return array_unique($formats);
    }
}