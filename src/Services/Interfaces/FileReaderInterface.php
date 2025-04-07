<?php

namespace App\Services\Interfaces;

/**
 * Interface for file readers
 */
interface FileReaderInterface
{
    /**
     * Read a file and return its contents as an array
     * 
     * @param string $filePath Path to the file
     * @param array $options Options for reading the file
     * @return array File contents as an array
     * @throws \App\Exceptions\FileReadException If the file cannot be read
     */
    public function read(string $filePath, array $options = []): array;

    /**
     * Validate a file
     * 
     * @param string $filePath Path to the file
     * @param array $options Options for validation
     * @return bool Whether the file is valid
     */
    public function validate(string $filePath, array $options = []): bool;

    /**
     * Get validation errors
     * 
     * @return array Validation errors
     */
    public function getErrors(): array;
}
