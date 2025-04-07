<?php

namespace App\Services\Interfaces;

/**
 * Interface for SMS validation service
 */
interface SMSValidationServiceInterface
{
    /**
     * Normalize a phone number to the format required by the Orange API
     * 
     * @param string $number Phone number
     * @return string Normalized phone number
     */
    public function normalizePhoneNumber(string $number): string;

    /**
     * Validate a phone number
     * 
     * @param string $number Phone number to validate
     * @return bool Whether the phone number is valid
     */
    public function validatePhoneNumber(string $number): bool;

    /**
     * Validate an SMS message
     * 
     * @param string $message Message to validate
     * @return bool Whether the message is valid
     */
    public function validateMessage(string $message): bool;

    /**
     * Get the character count of an SMS message
     * 
     * @param string $message Message to count
     * @return int Number of characters
     */
    public function getMessageCharacterCount(string $message): int;

    /**
     * Get the number of SMS parts needed for a message
     * 
     * @param string $message Message to analyze
     * @return int Number of SMS parts
     */
    public function getMessagePartCount(string $message): int;

    /**
     * Convert a phone number from international to local format
     * 
     * @param string $phoneNumber Phone number in international format
     * @return string Phone number in local format
     */
    public function convertToLocalFormat(string $phoneNumber): string;

    /**
     * Convert a phone number from local to international format
     * 
     * @param string $phoneNumber Phone number in local format
     * @return string Phone number in international format
     */
    public function convertToInternationalFormat(string $phoneNumber): string;
}
