<?php

namespace App\Services;

use App\Services\Interfaces\PhoneNumberNormalizerInterface;

/**
 * Service for normalizing phone numbers
 */
class PhoneNumberNormalizer implements PhoneNumberNormalizerInterface
{
    /**
     * @var string Default country code (Côte d'Ivoire)
     */
    private string $defaultCountryCode;

    /**
     * Constructor
     * 
     * @param string $defaultCountryCode Default country code to use when normalizing numbers
     */
    public function __construct(string $defaultCountryCode = '225')
    {
        $this->defaultCountryCode = $defaultCountryCode;
    }

    /**
     * Normalize a phone number to a standard format
     * 
     * @param string $number Phone number to normalize
     * @return string|null Normalized phone number or null if invalid
     */
    public function normalize(string $number): ?string
    {
        // Remove all non-numeric characters except the + sign
        $number = preg_replace('/[^0-9+]/', '', $number);

        // If the number is empty after cleaning, it's invalid
        if (empty($number)) {
            return null;
        }

        // If the number starts with 00, replace with +
        if (strpos($number, '00') === 0) {
            $number = '+' . substr($number, 2);
        }

        // If the number doesn't start with +, add the default country code
        if (strpos($number, '+') !== 0) {
            // Check if the number already has the country code without the +
            if (strpos($number, $this->defaultCountryCode) === 0) {
                $number = '+' . $number;
            } else {
                $number = '+' . $this->defaultCountryCode . $number;
            }
        }

        // Validate the number format (basic validation)
        if (!preg_match('/^\+[0-9]{6,15}$/', $number)) {
            return null;
        }

        return $number;
    }
}
