<?php

namespace App\Services\Interfaces;

/**
 * Interface for import results
 */
interface ImportResultInterface
{
    /**
     * Get the status of the import
     * 
     * @return string Status of the import (success, error, warning)
     */
    public function getStatus(): string;

    /**
     * Get the total number of records processed
     * 
     * @return int Total number of records processed
     */
    public function getTotalRecords(): int;

    /**
     * Get the number of records successfully imported
     * 
     * @return int Number of records successfully imported
     */
    public function getSuccessCount(): int;

    /**
     * Get the number of records that failed to import
     * 
     * @return int Number of records that failed to import
     */
    public function getFailureCount(): int;

    /**
     * Get the number of duplicate records
     * 
     * @return int Number of duplicate records
     */
    public function getDuplicateCount(): int;

    /**
     * Get the number of records that were updated
     * 
     * @return int Number of records that were updated
     */
    public function getUpdateCount(): int;

    /**
     * Get the error messages
     * 
     * @return array Error messages
     */
    public function getErrors(): array;

    /**
     * Get the warning messages
     * 
     * @return array Warning messages
     */
    public function getWarnings(): array;

    /**
     * Get the imported records
     * 
     * @return array Imported records
     */
    public function getImportedRecords(): array;

    /**
     * Get the failed records
     * 
     * @return array Failed records
     */
    public function getFailedRecords(): array;

    /**
     * Add an error message
     * 
     * @param string $error Error message
     * @param int|null $index Index of the record that caused the error
     * @return self
     */
    public function addError(string $error, ?int $index = null): self;

    /**
     * Add a warning message
     * 
     * @param string $warning Warning message
     * @param int|null $index Index of the record that caused the warning
     * @return self
     */
    public function addWarning(string $warning, ?int $index = null): self;

    /**
     * Add an imported record
     * 
     * @param mixed $record Imported record
     * @return self
     */
    public function addImportedRecord($record): self;

    /**
     * Add a failed record
     * 
     * @param mixed $record Failed record
     * @param string|null $reason Reason for failure
     * @return self
     */
    public function addFailedRecord($record, ?string $reason = null): self;

    /**
     * Increment the duplicate count
     * 
     * @param int $count Number to increment by
     * @return self
     */
    public function incrementDuplicateCount(int $count = 1): self;

    /**
     * Increment the update count
     * 
     * @param int $count Number to increment by
     * @return self
     */
    public function incrementUpdateCount(int $count = 1): self;

    /**
     * Convert the result to an array
     * 
     * @return array Result as an array
     */
    public function toArray(): array;
}
