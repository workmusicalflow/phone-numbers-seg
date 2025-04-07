<?php

namespace App\Services\Results;

use App\Services\Interfaces\ImportResultInterface;

/**
 * Import result
 */
class ImportResult implements ImportResultInterface
{
    /**
     * @var string Status of the import (success, error, warning)
     */
    private string $status = 'success';

    /**
     * @var int Number of records successfully imported
     */
    private int $successCount = 0;

    /**
     * @var int Number of records that failed to import
     */
    private int $failureCount = 0;

    /**
     * @var int Number of duplicate records
     */
    private int $duplicateCount = 0;

    /**
     * @var int Number of records that were updated
     */
    private int $updateCount = 0;

    /**
     * @var array Error messages
     */
    private array $errors = [];

    /**
     * @var array Warning messages
     */
    private array $warnings = [];

    /**
     * @var array Imported records
     */
    private array $importedRecords = [];

    /**
     * @var array Failed records
     */
    private array $failedRecords = [];

    /**
     * Constructor
     * 
     * @param string $status Initial status
     */
    public function __construct(string $status = 'success')
    {
        $this->status = $status;
    }

    /**
     * Get the status of the import
     * 
     * @return string Status of the import (success, error, warning)
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Get the total number of records processed
     * 
     * @return int Total number of records processed
     */
    public function getTotalRecords(): int
    {
        return $this->successCount + $this->failureCount;
    }

    /**
     * Get the number of records successfully imported
     * 
     * @return int Number of records successfully imported
     */
    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    /**
     * Get the number of records that failed to import
     * 
     * @return int Number of records that failed to import
     */
    public function getFailureCount(): int
    {
        return $this->failureCount;
    }

    /**
     * Get the number of duplicate records
     * 
     * @return int Number of duplicate records
     */
    public function getDuplicateCount(): int
    {
        return $this->duplicateCount;
    }

    /**
     * Get the number of records that were updated
     * 
     * @return int Number of records that were updated
     */
    public function getUpdateCount(): int
    {
        return $this->updateCount;
    }

    /**
     * Get the error messages
     * 
     * @return array Error messages
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get the warning messages
     * 
     * @return array Warning messages
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Get the imported records
     * 
     * @return array Imported records
     */
    public function getImportedRecords(): array
    {
        return $this->importedRecords;
    }

    /**
     * Get the failed records
     * 
     * @return array Failed records
     */
    public function getFailedRecords(): array
    {
        return $this->failedRecords;
    }

    /**
     * Add an error message
     * 
     * @param string $error Error message
     * @param int|null $index Index of the record that caused the error
     * @return self
     */
    public function addError(string $error, ?int $index = null): self
    {
        $this->status = 'error';

        if ($index !== null) {
            $this->errors[] = [
                'index' => $index,
                'message' => $error
            ];
        } else {
            $this->errors[] = $error;
        }

        return $this;
    }

    /**
     * Add a warning message
     * 
     * @param string $warning Warning message
     * @param int|null $index Index of the record that caused the warning
     * @return self
     */
    public function addWarning(string $warning, ?int $index = null): self
    {
        if ($this->status === 'success') {
            $this->status = 'warning';
        }

        if ($index !== null) {
            $this->warnings[] = [
                'index' => $index,
                'message' => $warning
            ];
        } else {
            $this->warnings[] = $warning;
        }

        return $this;
    }

    /**
     * Add an imported record
     * 
     * @param mixed $record Imported record
     * @return self
     */
    public function addImportedRecord($record): self
    {
        $this->importedRecords[] = $record;
        $this->successCount++;

        return $this;
    }

    /**
     * Add a failed record
     * 
     * @param mixed $record Failed record
     * @param string|null $reason Reason for failure
     * @return self
     */
    public function addFailedRecord($record, ?string $reason = null): self
    {
        if ($reason !== null) {
            $this->failedRecords[] = [
                'record' => $record,
                'reason' => $reason
            ];
        } else {
            $this->failedRecords[] = $record;
        }

        $this->failureCount++;

        return $this;
    }

    /**
     * Increment the duplicate count
     * 
     * @param int $count Number to increment by
     * @return self
     */
    public function incrementDuplicateCount(int $count = 1): self
    {
        $this->duplicateCount += $count;

        return $this;
    }

    /**
     * Increment the update count
     * 
     * @param int $count Number to increment by
     * @return self
     */
    public function incrementUpdateCount(int $count = 1): self
    {
        $this->updateCount += $count;

        return $this;
    }

    /**
     * Convert the result to an array
     * 
     * @return array Result as an array
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'summary' => [
                'total' => $this->getTotalRecords(),
                'successful' => $this->successCount,
                'failed' => $this->failureCount,
                'duplicates' => $this->duplicateCount,
                'updated' => $this->updateCount
            ],
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'importedRecords' => $this->importedRecords,
            'failedRecords' => $this->failedRecords
        ];
    }
}
