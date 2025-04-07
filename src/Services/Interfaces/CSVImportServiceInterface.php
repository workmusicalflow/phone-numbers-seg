<?php

namespace App\Services\Interfaces;

use App\Services\Results\ImportResult;

/**
 * Interface for CSV import service
 */
interface CSVImportServiceInterface
{
    /**
     * Import phone numbers from a CSV file
     * 
     * @param string $filePath Path to the CSV file
     * @param ImportConfigInterface $config Import configuration
     * @return ImportResultInterface Import result
     */
    public function importFromFile(string $filePath, ImportConfigInterface $config): ImportResultInterface;

    /**
     * Import phone numbers from a string
     * 
     * @param string $content CSV content as a string
     * @param ImportConfigInterface $config Import configuration
     * @return ImportResultInterface Import result
     */
    public function importFromString(string $content, ImportConfigInterface $config): ImportResultInterface;

    /**
     * Import phone numbers from an array
     * 
     * @param array $data Array of phone numbers or rows with phone number data
     * @param ImportConfigInterface $config Import configuration
     * @return ImportResultInterface Import result
     */
    public function importFromArray(array $data, ImportConfigInterface $config): ImportResultInterface;

    /**
     * Process a batch of phone numbers
     * 
     * @param array $batch Array of phone numbers or rows with phone number data
     * @param ImportConfigInterface $config Import configuration
     * @return ImportResultInterface Import result
     */
    public function processBatch(array $batch, ImportConfigInterface $config): ImportResultInterface;

    /**
     * Normalize a phone number
     * 
     * @param string $number Phone number to normalize
     * @param string $defaultCountryCode Default country code
     * @return string|null Normalized phone number or null if invalid
     */
    public function normalizePhoneNumber(string $number, string $defaultCountryCode = '225'): ?string;

    /**
     * Extract phone number data from a row
     * 
     * @param array|string $row Row data or phone number string
     * @param array $columnMapping Column mapping (column name => field name)
     * @return array Phone number data
     */
    public function extractPhoneNumberData($row, array $columnMapping): array;
}
