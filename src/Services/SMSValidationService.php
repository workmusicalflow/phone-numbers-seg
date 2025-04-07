<?php

namespace App\Services;

use App\Services\Interfaces\SMSValidationServiceInterface;

/**
 * Service for SMS validation and phone number normalization
 */
class SMSValidationService implements SMSValidationServiceInterface
{
    /**
     * Maximum SMS length for a single part
     */
    private const MAX_SMS_LENGTH = 160;

    /**
     * Maximum SMS length for a multi-part message
     */
    private const MAX_MULTIPART_SMS_LENGTH = 153;

    /**
     * Normalize a phone number to the format required by the Orange API
     * 
     * @param string $number Phone number
     * @return string Normalized phone number
     */
    public function normalizePhoneNumber(string $number): string
    {
        // Remove any non-numeric characters except the leading +
        $number = preg_replace('/[^0-9+]/', '', $number);

        // Handle different formats
        if (substr($number, 0, 1) === '+') {
            // Format: +2250777104936 - already in international format
            $normalizedNumber = $number;
        } elseif (substr($number, 0, 4) === '0022') {
            // Format: 002250777104936 - convert to +225...
            $normalizedNumber = '+' . substr($number, 3);
        } elseif (substr($number, 0, 1) === '0') {
            // Format: 0777104936 - convert to +225...
            $normalizedNumber = '+225' . substr($number, 1);
        } else {
            // If none of the above, assume it's already normalized or invalid
            $normalizedNumber = $number;
        }

        // Add the 'tel:' prefix required by the Orange API
        return 'tel:' . $normalizedNumber;
    }

    /**
     * Validate a phone number
     * 
     * @param string $number Phone number to validate
     * @return bool Whether the phone number is valid
     */
    public function validatePhoneNumber(string $number): bool
    {
        // Remove any non-numeric characters except the leading +
        $number = preg_replace('/[^0-9+]/', '', $number);

        // Check if the number is empty
        if (empty($number)) {
            return false;
        }

        // Check if the number starts with + and has at least 10 digits
        if (substr($number, 0, 1) === '+' && strlen($number) >= 10) {
            return true;
        }

        // Check if the number starts with 00 and has at least 12 digits
        if (substr($number, 0, 2) === '00' && strlen($number) >= 12) {
            return true;
        }

        // Check if the number starts with 0 and has at least 10 digits (local format)
        if (substr($number, 0, 1) === '0' && strlen($number) >= 10) {
            return true;
        }

        // If none of the above, the number is invalid
        return false;
    }

    /**
     * Validate an SMS message
     * 
     * @param string $message Message to validate
     * @return bool Whether the message is valid
     */
    public function validateMessage(string $message): bool
    {
        // Check if the message is empty
        if (empty($message)) {
            return false;
        }

        // Check if the message is too long (more than 6 parts)
        if ($this->getMessagePartCount($message) > 6) {
            return false;
        }

        return true;
    }

    /**
     * Get the character count of an SMS message
     * 
     * @param string $message Message to count
     * @return int Number of characters
     */
    public function getMessageCharacterCount(string $message): int
    {
        return mb_strlen($message, 'UTF-8');
    }

    /**
     * Get the number of SMS parts needed for a message
     * 
     * @param string $message Message to analyze
     * @return int Number of SMS parts
     */
    public function getMessagePartCount(string $message): int
    {
        $length = $this->getMessageCharacterCount($message);

        if ($length <= self::MAX_SMS_LENGTH) {
            return 1;
        }

        return ceil($length / self::MAX_MULTIPART_SMS_LENGTH);
    }

    /**
     * Convert a phone number from international to local format
     * 
     * @param string $phoneNumber Phone number in international format
     * @return string Phone number in local format
     */
    public function convertToLocalFormat(string $phoneNumber): string
    {
        // If the number begins with tel:, remove it
        if (strpos($phoneNumber, 'tel:') === 0) {
            $phoneNumber = substr($phoneNumber, 4);
        }

        // If the number begins with +225, convert to local format
        if (strpos($phoneNumber, '+225') === 0) {
            $localNumber = substr($phoneNumber, 4); // Remove +225

            // If the number doesn't start with 0, add it
            if (substr($localNumber, 0, 1) !== '0') {
                $localNumber = '0' . $localNumber;
            }

            return $localNumber;
        }

        return $phoneNumber;
    }

    /**
     * Convert a phone number from local to international format
     * 
     * @param string $phoneNumber Phone number in local format
     * @return string Phone number in international format
     */
    public function convertToInternationalFormat(string $phoneNumber): string
    {
        // Remove any non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);

        // If the number starts with 0 and has 10 digits, it's a local number
        if (strlen($cleaned) === 10 && substr($cleaned, 0, 1) === '0') {
            // Convert to international format (Côte d'Ivoire +225)
            return '+225' . substr($cleaned, 1);
        }

        // If the number has 8 digits, it's a local number without the 0
        if (strlen($cleaned) === 8) {
            // Convert to international format (Côte d'Ivoire +225)
            return '+225' . $cleaned;
        }

        // If the number already starts with +, return it as is
        if (substr($phoneNumber, 0, 1) === '+') {
            return $phoneNumber;
        }

        // If none of the above, assume it's already in international format without the +
        return '+' . $cleaned;
    }
}
