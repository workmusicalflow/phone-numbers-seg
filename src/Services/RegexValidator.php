<?php

namespace App\Services;

use App\Services\Interfaces\RegexValidatorInterface;

/**
 * Service for validating and testing regular expressions
 */
class RegexValidator implements RegexValidatorInterface
{
    /**
     * @var string|null The last error message
     */
    private ?string $lastError = null;

    /**
     * Validate a regex pattern
     * 
     * @param string $pattern The regex pattern to validate
     * @return bool True if the pattern is valid, false otherwise
     */
    public function isValid(string $pattern): bool
    {
        // Clear previous error
        $this->lastError = null;

        // Check if the pattern is empty
        if (empty($pattern)) {
            $this->lastError = 'Pattern cannot be empty';
            return false;
        }

        // Try to use the pattern in a regex function
        try {
            // Suppress warnings to catch them as exceptions
            set_error_handler(function ($errno, $errstr) {
                throw new \Exception($errstr);
            }, E_WARNING);

            // Test the pattern with a simple string
            preg_match($pattern, 'test');

            // Restore error handler
            restore_error_handler();

            return true;
        } catch (\Exception $e) {
            // Capture the error message
            $this->lastError = $e->getMessage();

            // Restore error handler
            restore_error_handler();

            return false;
        }
    }

    /**
     * Get the error message for the last validation
     * 
     * @return string|null The error message or null if there is no error
     */
    public function getError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Test a regex pattern against a string
     * 
     * @param string $pattern The regex pattern to test
     * @param string $subject The string to test against
     * @return bool True if the pattern matches the subject, false otherwise
     */
    public function test(string $pattern, string $subject): bool
    {
        // Clear previous error
        $this->lastError = null;

        // Validate the pattern first
        if (!$this->isValid($pattern)) {
            return false;
        }

        // Test the pattern against the subject
        try {
            // Suppress warnings to catch them as exceptions
            set_error_handler(function ($errno, $errstr) {
                throw new \Exception($errstr);
            }, E_WARNING);

            $result = preg_match($pattern, $subject) === 1;

            // Restore error handler
            restore_error_handler();

            return $result;
        } catch (\Exception $e) {
            // Capture the error message
            $this->lastError = $e->getMessage();

            // Restore error handler
            restore_error_handler();

            return false;
        }
    }

    /**
     * Get all matches of a regex pattern in a string
     * 
     * @param string $pattern The regex pattern to match
     * @param string $subject The string to match against
     * @return array The matches found
     */
    public function getMatches(string $pattern, string $subject): array
    {
        // Clear previous error
        $this->lastError = null;

        // Validate the pattern first
        if (!$this->isValid($pattern)) {
            return [];
        }

        // Get all matches
        try {
            // Suppress warnings to catch them as exceptions
            set_error_handler(function ($errno, $errstr) {
                throw new \Exception($errstr);
            }, E_WARNING);

            $matches = [];
            preg_match_all($pattern, $subject, $matches);

            // Restore error handler
            restore_error_handler();

            return $matches[0] ?? [];
        } catch (\Exception $e) {
            // Capture the error message
            $this->lastError = $e->getMessage();

            // Restore error handler
            restore_error_handler();

            return [];
        }
    }
}
