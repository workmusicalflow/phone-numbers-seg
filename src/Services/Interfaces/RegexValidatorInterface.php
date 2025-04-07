<?php

namespace App\Services\Interfaces;

/**
 * Interface for regex validation service
 */
interface RegexValidatorInterface
{
    /**
     * Validate a regex pattern
     * 
     * @param string $pattern The regex pattern to validate
     * @return bool True if the pattern is valid, false otherwise
     */
    public function isValid(string $pattern): bool;

    /**
     * Get the error message for the last validation
     * 
     * @return string|null The error message or null if there is no error
     */
    public function getError(): ?string;

    /**
     * Test a regex pattern against a string
     * 
     * @param string $pattern The regex pattern to test
     * @param string $subject The string to test against
     * @return bool True if the pattern matches the subject, false otherwise
     */
    public function test(string $pattern, string $subject): bool;

    /**
     * Get all matches of a regex pattern in a string
     * 
     * @param string $pattern The regex pattern to match
     * @param string $subject The string to match against
     * @return array The matches found
     */
    public function getMatches(string $pattern, string $subject): array;
}
