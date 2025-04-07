<?php

namespace App\Services\Interfaces;

/**
 * Interface for import configuration
 */
interface ImportConfigInterface
{
    /**
     * Get the delimiter character
     * 
     * @return string Delimiter character
     */
    public function getDelimiter(): string;

    /**
     * Get the enclosure character
     * 
     * @return string Enclosure character
     */
    public function getEnclosure(): string;

    /**
     * Get the escape character
     * 
     * @return string Escape character
     */
    public function getEscape(): string;

    /**
     * Check if the first row is a header
     * 
     * @return bool Whether the first row is a header
     */
    public function hasHeader(): bool;

    /**
     * Get the maximum number of rows to import
     * 
     * @return int Maximum number of rows (0 means no limit)
     */
    public function getMaxRows(): int;

    /**
     * Get the maximum file size in bytes
     * 
     * @return int Maximum file size in bytes
     */
    public function getMaxFileSize(): int;

    /**
     * Check if invalid phone numbers should be skipped
     * 
     * @return bool Whether to skip invalid phone numbers
     */
    public function skipInvalidNumbers(): bool;

    /**
     * Check if phone numbers should be segmented immediately
     * 
     * @return bool Whether to segment phone numbers immediately
     */
    public function segmentImmediately(): bool;

    /**
     * Get the column mapping
     * 
     * @return array Column mapping (column name => field name)
     */
    public function getColumnMapping(): array;

    /**
     * Get the default country code
     * 
     * @return string Default country code
     */
    public function getDefaultCountryCode(): string;
}
