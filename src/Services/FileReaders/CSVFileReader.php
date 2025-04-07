<?php

namespace App\Services\FileReaders;

use App\Exceptions\FileReadException;
use App\Services\Interfaces\FileReaderInterface;

/**
 * CSV file reader
 */
class CSVFileReader implements FileReaderInterface
{
    /**
     * @var array Validation errors
     */
    private array $errors = [];

    /**
     * Read a CSV file and return its contents as an array
     * 
     * @param string $filePath Path to the CSV file
     * @param array $options Options for reading the file
     * @return array File contents as an array
     * @throws FileReadException If the file cannot be read
     */
    public function read(string $filePath, array $options = []): array
    {
        // Reset errors
        $this->errors = [];

        // Validate the file
        if (!$this->validate($filePath, $options)) {
            throw new FileReadException("Invalid CSV file: " . implode(", ", $this->errors));
        }

        // Set default options
        $options = array_merge([
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
            'hasHeader' => true,
            'maxRows' => 0 // 0 means no limit
        ], $options);

        try {
            // Open the file
            $handle = fopen($filePath, 'r', false, null);
            if ($handle === false) {
                throw new FileReadException("Failed to open file: {$filePath}");
            }

            $rows = [];
            $rowNumber = 0;
            $headers = [];

            // Process each line
            $delimiter = $options['delimiter'];
            while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
                $rowNumber++;

                // Handle header row
                if ($options['hasHeader'] && $rowNumber === 1) {
                    $headers = $data;
                    continue;
                }

                // Check if we've reached the maximum number of rows
                if ($options['maxRows'] > 0 && count($rows) >= $options['maxRows']) {
                    break;
                }

                // If we have headers, associate data with header names
                if ($options['hasHeader'] && !empty($headers)) {
                    $row = [];
                    foreach ($data as $i => $value) {
                        if (isset($headers[$i])) {
                            $row[$headers[$i]] = $value;
                        } else {
                            $row["column{$i}"] = $value;
                        }
                    }
                    $rows[] = $row;
                } else {
                    // Otherwise, just add the data as is
                    $rows[] = $data;
                }
            }

            fclose($handle);
            return $rows;
        } catch (\Exception $e) {
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            throw new FileReadException("Error reading CSV file: " . $e->getMessage());
        }
    }

    /**
     * Validate a CSV file
     * 
     * @param string $filePath Path to the CSV file
     * @param array $options Options for validation
     * @return bool Whether the file is valid
     */
    public function validate(string $filePath, array $options = []): bool
    {
        // Reset errors
        $this->errors = [];

        // Check if file exists
        if (!file_exists($filePath)) {
            $this->errors[] = "File not found: {$filePath}";
            return false;
        }

        // Check if file is readable
        if (!is_readable($filePath)) {
            $this->errors[] = "File is not readable: {$filePath}";
            return false;
        }

        // Check file extension
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        if (strtolower($extension) !== 'csv') {
            $this->errors[] = "Invalid file format. Only CSV files are supported.";
            return false;
        }

        // Check file size
        $maxSize = $options['maxSize'] ?? 10 * 1024 * 1024; // 10MB by default
        if (filesize($filePath) > $maxSize) {
            $this->errors[] = "File is too large. Maximum size is " . ($maxSize / 1024 / 1024) . "MB.";
            return false;
        }

        // Try to open the file
        $handle = @fopen($filePath, 'r', false, null);
        if ($handle === false) {
            $this->errors[] = "Failed to open file: {$filePath}";
            return false;
        }

        // Check if the file is empty
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            $this->errors[] = "File is empty.";
            fclose($handle);
            return false;
        }

        // Reset file pointer
        rewind($handle);

        // Try to read the first line as CSV
        $options = array_merge([
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\'
        ], $options);

        $delimiter = $options['delimiter'];
        $firstRow = fgetcsv($handle, 1000, $delimiter);
        if ($firstRow === false || count($firstRow) < 1) {
            $this->errors[] = "Invalid CSV format.";
            fclose($handle);
            return false;
        }

        fclose($handle);
        return true;
    }

    /**
     * Get validation errors
     * 
     * @return array Validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
