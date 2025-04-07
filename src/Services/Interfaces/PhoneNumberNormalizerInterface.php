<?php

namespace App\Services\Interfaces;

/**
 * Interface for phone number normalization
 */
interface PhoneNumberNormalizerInterface
{
    /**
     * Normalize a phone number to a standard format
     * 
     * @param string $number Phone number to normalize
     * @return string|null Normalized phone number or null if invalid
     */
    public function normalize(string $number): ?string;
}
