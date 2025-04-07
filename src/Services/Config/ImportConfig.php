<?php

namespace App\Services\Config;

use App\Services\Interfaces\ImportConfigInterface;

/**
 * Import configuration
 */
class ImportConfig implements ImportConfigInterface
{
    /**
     * @var string Delimiter character
     */
    private string $delimiter;

    /**
     * @var string Enclosure character
     */
    private string $enclosure;

    /**
     * @var string Escape character
     */
    private string $escape;

    /**
     * @var bool Whether the first row is a header
     */
    private bool $hasHeaderRow;

    /**
     * @var int Maximum number of rows to import (0 means no limit)
     */
    private int $maxRows;

    /**
     * @var int Maximum file size in bytes
     */
    private int $maxFileSize;

    /**
     * @var bool Whether to skip invalid phone numbers
     */
    private bool $skipInvalid;

    /**
     * @var bool Whether to segment phone numbers immediately
     */
    private bool $segmentImmediately;

    /**
     * @var array Column mapping (column name => field name)
     */
    private array $columnMapping;

    /**
     * @var string Default country code
     */
    private string $defaultCountryCode;

    /**
     * Constructor
     * 
     * @param array $options Import options
     */
    public function __construct(array $options = [])
    {
        $this->delimiter = $options['delimiter'] ?? ',';
        $this->enclosure = $options['enclosure'] ?? '"';
        $this->escape = $options['escape'] ?? '\\';
        $this->hasHeaderRow = $options['hasHeader'] ?? true;
        $this->maxRows = $options['maxRows'] ?? 0;
        $this->maxFileSize = $options['maxFileSize'] ?? 10 * 1024 * 1024; // 10MB by default
        $this->skipInvalid = $options['skipInvalid'] ?? true;
        $this->segmentImmediately = $options['segmentImmediately'] ?? true;
        $this->columnMapping = $options['columnMapping'] ?? [
            'number' => 'number',
            'civility' => 'civility',
            'firstName' => 'firstName',
            'name' => 'name',
            'company' => 'company',
            'sector' => 'sector',
            'notes' => 'notes'
        ];
        $this->defaultCountryCode = $options['defaultCountryCode'] ?? '225';
    }

    /**
     * Get the delimiter character
     * 
     * @return string Delimiter character
     */
    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    /**
     * Get the enclosure character
     * 
     * @return string Enclosure character
     */
    public function getEnclosure(): string
    {
        return $this->enclosure;
    }

    /**
     * Get the escape character
     * 
     * @return string Escape character
     */
    public function getEscape(): string
    {
        return $this->escape;
    }

    /**
     * Check if the first row is a header
     * 
     * @return bool Whether the first row is a header
     */
    public function hasHeader(): bool
    {
        return $this->hasHeaderRow;
    }

    /**
     * Get the maximum number of rows to import
     * 
     * @return int Maximum number of rows (0 means no limit)
     */
    public function getMaxRows(): int
    {
        return $this->maxRows;
    }

    /**
     * Get the maximum file size in bytes
     * 
     * @return int Maximum file size in bytes
     */
    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }

    /**
     * Check if invalid phone numbers should be skipped
     * 
     * @return bool Whether to skip invalid phone numbers
     */
    public function skipInvalidNumbers(): bool
    {
        return $this->skipInvalid;
    }

    /**
     * Check if phone numbers should be segmented immediately
     * 
     * @return bool Whether to segment phone numbers immediately
     */
    public function segmentImmediately(): bool
    {
        return $this->segmentImmediately;
    }

    /**
     * Get the column mapping
     * 
     * @return array Column mapping (column name => field name)
     */
    public function getColumnMapping(): array
    {
        return $this->columnMapping;
    }

    /**
     * Get the default country code
     * 
     * @return string Default country code
     */
    public function getDefaultCountryCode(): string
    {
        return $this->defaultCountryCode;
    }

    /**
     * Set the delimiter character
     * 
     * @param string $delimiter Delimiter character
     * @return self
     */
    public function setDelimiter(string $delimiter): self
    {
        $this->delimiter = $delimiter;
        return $this;
    }

    /**
     * Set the enclosure character
     * 
     * @param string $enclosure Enclosure character
     * @return self
     */
    public function setEnclosure(string $enclosure): self
    {
        $this->enclosure = $enclosure;
        return $this;
    }

    /**
     * Set the escape character
     * 
     * @param string $escape Escape character
     * @return self
     */
    public function setEscape(string $escape): self
    {
        $this->escape = $escape;
        return $this;
    }

    /**
     * Set whether the first row is a header
     * 
     * @param bool $hasHeader Whether the first row is a header
     * @return self
     */
    public function setHasHeader(bool $hasHeader): self
    {
        $this->hasHeaderRow = $hasHeader;
        return $this;
    }

    /**
     * Set the maximum number of rows to import
     * 
     * @param int $maxRows Maximum number of rows (0 means no limit)
     * @return self
     */
    public function setMaxRows(int $maxRows): self
    {
        $this->maxRows = $maxRows;
        return $this;
    }

    /**
     * Set the maximum file size in bytes
     * 
     * @param int $maxFileSize Maximum file size in bytes
     * @return self
     */
    public function setMaxFileSize(int $maxFileSize): self
    {
        $this->maxFileSize = $maxFileSize;
        return $this;
    }

    /**
     * Set whether to skip invalid phone numbers
     * 
     * @param bool $skipInvalid Whether to skip invalid phone numbers
     * @return self
     */
    public function setSkipInvalidNumbers(bool $skipInvalid): self
    {
        $this->skipInvalid = $skipInvalid;
        return $this;
    }

    /**
     * Set whether to segment phone numbers immediately
     * 
     * @param bool $segmentImmediately Whether to segment phone numbers immediately
     * @return self
     */
    public function setSegmentImmediately(bool $segmentImmediately): self
    {
        $this->segmentImmediately = $segmentImmediately;
        return $this;
    }

    /**
     * Set the column mapping
     * 
     * @param array $columnMapping Column mapping (column name => field name)
     * @return self
     */
    public function setColumnMapping(array $columnMapping): self
    {
        $this->columnMapping = $columnMapping;
        return $this;
    }

    /**
     * Set the default country code
     * 
     * @param string $defaultCountryCode Default country code
     * @return self
     */
    public function setDefaultCountryCode(string $defaultCountryCode): self
    {
        $this->defaultCountryCode = $defaultCountryCode;
        return $this;
    }
}
