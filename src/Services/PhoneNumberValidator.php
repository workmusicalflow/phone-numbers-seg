<?php

namespace App\Services;

use App\Models\PhoneNumber;
use App\Services\Interfaces\PhoneNumberValidatorInterface;

/**
 * Validator for phone numbers
 */
class PhoneNumberValidator implements PhoneNumberValidatorInterface
{
    /**
     * Validate a phone number
     * 
     * @param PhoneNumber $phoneNumber
     * @return bool
     */
    public function validate(PhoneNumber $phoneNumber): bool
    {
        $number = $phoneNumber->getNumber();

        // Check if the number is empty
        if (empty($number)) {
            return false;
        }

        // Check if the number starts with a plus sign
        if (strpos($number, '+') !== 0) {
            return false;
        }

        // Check if the number contains only digits after the plus sign
        if (!preg_match('/^\+[0-9]+$/', $number)) {
            return false;
        }

        // Check if the number has a valid length (international numbers are typically 7-15 digits)
        $digitsOnly = substr($number, 1); // Remove the plus sign
        if (strlen($digitsOnly) < 7 || strlen($digitsOnly) > 15) {
            return false;
        }

        return true;
    }
}
