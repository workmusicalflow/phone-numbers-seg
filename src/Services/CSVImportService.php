<?php

namespace App\Services;

use App\Models\PhoneNumber;
use App\Repositories\PhoneNumberRepository;
use App\Services\Interfaces\PhoneSegmentationServiceInterface;

/**
 * Service for importing phone numbers from CSV files
 */
class CSVImportService
{
    /**
     * @var PhoneNumberRepository
     */
    private PhoneNumberRepository $phoneNumberRepository;

    /**
     * @var PhoneSegmentationServiceInterface
     */
    private PhoneSegmentationServiceInterface $segmentationService;

    /**
     * @var int Maximum number of phone numbers to process in a single batch
     */
    private const MAX_BATCH_SIZE = 5000;

    /**
     * @var array Validation errors
     */
    private array $errors = [];

    /**
     * @var array Detailed errors with line numbers and values
     */
    private array $detailedErrors = [];

    /**
     * @var int Maximum number of detailed errors to collect
     */
    private int $maxDetailedErrors = 20;

    /**
     * @var int Current line being processed
     */
    private int $currentLine = 0;

    /**
     * @var array Import statistics
     */
    private array $stats = [
        'total' => 0,
        'valid' => 0,
        'invalid' => 0,
        'duplicates' => 0,
        'processed' => 0
    ];

    /**
     * Constructor
     * 
     * @param PhoneNumberRepository $phoneNumberRepository
     * @param PhoneSegmentationServiceInterface $segmentationService
     */
    public function __construct(
        PhoneNumberRepository $phoneNumberRepository,
        PhoneSegmentationServiceInterface $segmentationService
    ) {
        $this->phoneNumberRepository = $phoneNumberRepository;
        $this->segmentationService = $segmentationService;
    }

    /**
     * Import phone numbers from a CSV file
     * 
     * @param string $filePath Path to the CSV file
     * @param array $options Import options
     * @return array Import results
     */
    public function importFromFile(string $filePath, array $options = []): array
    {
        // Log pour déboguer
        error_log("CSVImportService::importFromFile called with filePath: {$filePath}");
        error_log("Import options: " . print_r($options, true));

        // Reset statistics, errors and line counter
        $this->resetStats();
        $this->currentLine = 0;
        $this->detailedErrors = [];

        // Check if file exists
        if (!file_exists($filePath)) {
            $errorMessage = "File not found: {$filePath}";
            error_log("Import error: " . $errorMessage);
            $this->errors[] = $errorMessage;
            return $this->getResults();
        }

        // Check file extension - plus permissif, accepte les fichiers sans extension
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        error_log("File extension: " . $extension);
        if ($extension && strtolower($extension) !== 'csv') {
            $errorMessage = "Invalid file format. Only CSV files are supported. Extension detected: {$extension}";
            error_log("Import error: " . $errorMessage);
            $this->errors[] = $errorMessage;
            return $this->getResults();
        }

        // Set default options
        $options = array_merge([
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
            'hasHeader' => true,
            'phoneColumn' => 0,
            'civilityColumn' => -1, // -1 means not specified
            'firstNameColumn' => -1, // -1 means not specified
            'nameColumn' => -1, // -1 means not specified
            'companyColumn' => -1, // -1 means not specified
            'sectorColumn' => -1, // -1 means not specified
            'notesColumn' => -1, // -1 means not specified
            'emailColumn' => -1, // -1 means not specified
            'skipInvalid' => true,
            'batchSize' => 200, // Reduced default batch size
            'segmentImmediately' => true
        ], $options);

        try {
            // Open the file
            $handle = fopen($filePath, 'r');
            if ($handle === false) {
                $this->errors[] = "Failed to open file: {$filePath}";
                return $this->getResults();
            }

            // Skip header if needed
            if ($options['hasHeader']) {
                fgetcsv($handle, 0, $options['delimiter'], $options['enclosure'], $options['escape']);
                $this->currentLine = 1; // Set to 1 after skipping header
            }

            $batch = [];
            $lineNumber = $options['hasHeader'] ? 2 : 1;
            $this->currentLine = $lineNumber - 1; // Initialize current line

            // Process each line
            while (($data = fgetcsv($handle, 0, $options['delimiter'], $options['enclosure'], $options['escape'])) !== false) {
                $this->stats['total']++;
                $this->currentLine++; // Increment line counter for each processed line

                // Validate column indices against actual data
                $columnCount = count($data);
                $phoneColumnIndex = $options['phoneColumn'];

                // Check if phone column index is valid
                if ($phoneColumnIndex < 0 || $phoneColumnIndex >= $columnCount) {
                    if (count($this->detailedErrors) < $this->maxDetailedErrors) {
                        $this->detailedErrors[] = [
                            'line' => $this->currentLine,
                            'value' => 'N/A',
                            'message' => "Index de colonne téléphone invalide: {$phoneColumnIndex}. Le fichier n'a que {$columnCount} colonnes."
                        ];
                    }
                    $this->stats['invalid']++;
                    $lineNumber++;
                    continue;
                }

                // Get phone number from the specified column
                $phoneNumber = isset($data[$phoneColumnIndex]) ? trim($data[$phoneColumnIndex]) : '';

                // Validate phone number
                if (empty($phoneNumber)) {
                    $this->stats['invalid']++;
                    if (!$options['skipInvalid']) {
                        $this->errors[] = "Line {$lineNumber}: Empty phone number";
                    }

                    // Add detailed error
                    if (count($this->detailedErrors) < $this->maxDetailedErrors) {
                        $this->detailedErrors[] = [
                            'line' => $this->currentLine,
                            'value' => 'empty',
                            'message' => "Numéro de téléphone vide"
                        ];
                    }

                    $lineNumber++;
                    continue;
                }

                // Normalize phone number
                $normalizedNumber = $this->normalizePhoneNumber($phoneNumber);
                if ($normalizedNumber === null) {
                    $this->stats['invalid']++;
                    if (!$options['skipInvalid']) {
                        $this->errors[] = "Line {$lineNumber}: Invalid phone number format: {$phoneNumber}";
                    }

                    // Add detailed error
                    if (count($this->detailedErrors) < $this->maxDetailedErrors) {
                        $this->detailedErrors[] = [
                            'line' => $this->currentLine,
                            'value' => $phoneNumber,
                            'message' => "Format de numéro invalide"
                        ];
                    }

                    $lineNumber++;
                    continue;
                }

                // Check for duplicates in the current batch
                if (in_array($normalizedNumber, array_column($batch, 'number'))) {
                    $this->stats['duplicates']++;
                    if (!$options['skipInvalid']) {
                        $this->errors[] = "Line {$lineNumber}: Duplicate phone number: {$normalizedNumber}";
                    }

                    // Add detailed error
                    if (count($this->detailedErrors) < $this->maxDetailedErrors) {
                        $this->detailedErrors[] = [
                            'line' => $this->currentLine,
                            'value' => $normalizedNumber,
                            'message' => "Doublon détecté dans le fichier"
                        ];
                    }

                    $lineNumber++;
                    continue;
                }

                // Extract additional fields if columns are specified
                $additionalFields = [
                    'civility' => $this->getColumnValue($data, $options['civilityColumn'], $columnCount),
                    'firstName' => $this->getColumnValue($data, $options['firstNameColumn'], $columnCount),
                    'name' => $this->getColumnValue($data, $options['nameColumn'], $columnCount),
                    'company' => $this->getColumnValue($data, $options['companyColumn'], $columnCount),
                    'sector' => $this->getColumnValue($data, $options['sectorColumn'], $columnCount),
                    'notes' => $this->getColumnValue($data, $options['notesColumn'], $columnCount),
                    'email' => $this->getColumnValue($data, $options['emailColumn'], $columnCount)
                ];

                // Add to batch with additional fields
                $batch[] = [
                    'number' => $normalizedNumber,
                    'fields' => $additionalFields
                ];
                $this->stats['valid']++;

                // Process batch if it reaches the maximum size
                if (count($batch) >= $options['batchSize']) {
                    $this->processBatch($batch, $options['segmentImmediately']);
                    $batch = [];
                }

                $lineNumber++;
            }

            // Process remaining batch
            if (!empty($batch)) {
                $this->processBatch($batch, $options['segmentImmediately']);
            }

            fclose($handle);
        } catch (\Exception $e) {
            $this->errors[] = "Error processing CSV file: " . $e->getMessage();

            // Add detailed error for exception
            if (count($this->detailedErrors) < $this->maxDetailedErrors) {
                $this->detailedErrors[] = [
                    'line' => $this->currentLine,
                    'value' => 'N/A',
                    'message' => "Erreur système: " . $e->getMessage()
                ];
            }
        }

        return $this->getResults();
    }

    /**
     * Import phone numbers from an array
     * 
     * @param array $numbers Array of phone numbers
     * @param array $options Import options
     * @return array Import results
     */
    public function importFromArray(array $numbers, array $options = []): array
    {
        // Reset statistics and errors
        $this->resetStats();

        // Set default options
        $options = array_merge([
            'skipInvalid' => true,
            'batchSize' => self::MAX_BATCH_SIZE,
            'segmentImmediately' => true
        ], $options);

        try {
            $batch = [];
            $this->stats['total'] = count($numbers);

            // Process each number
            foreach ($numbers as $index => $phoneNumber) {
                $phoneNumber = trim($phoneNumber);

                // Validate phone number
                if (empty($phoneNumber)) {
                    $this->stats['invalid']++;
                    if (!$options['skipInvalid']) {
                        $this->errors[] = "Index {$index}: Empty phone number";
                    }
                    continue;
                }

                // Normalize phone number
                $normalizedNumber = $this->normalizePhoneNumber($phoneNumber);
                if ($normalizedNumber === null) {
                    $this->stats['invalid']++;
                    if (!$options['skipInvalid']) {
                        $this->errors[] = "Index {$index}: Invalid phone number format: {$phoneNumber}";
                    }
                    continue;
                }

                // Check for duplicates in the current batch
                if (in_array($normalizedNumber, $batch)) {
                    $this->stats['duplicates']++;
                    if (!$options['skipInvalid']) {
                        $this->errors[] = "Index {$index}: Duplicate phone number: {$normalizedNumber}";
                    }
                    continue;
                }

                // Add to batch
                $batch[] = $normalizedNumber;
                $this->stats['valid']++;

                // Process batch if it reaches the maximum size
                if (count($batch) >= $options['batchSize']) {
                    $this->processBatch($batch, $options['segmentImmediately']);
                    $batch = [];
                }
            }

            // Process remaining batch
            if (!empty($batch)) {
                $this->processBatch($batch, $options['segmentImmediately']);
            }
        } catch (\Exception $e) {
            $this->errors[] = "Error processing phone numbers: " . $e->getMessage();
        }

        return $this->getResults();
    }

    /**
     * Process a batch of phone numbers
     * 
     * @param array $batch Array of normalized phone numbers with additional fields
     * @param bool $segment Whether to segment the numbers immediately
     * @return void
     */
    /**
     * Helper method to safely get a value from a column
     * 
     * @param array $data Row data
     * @param int $columnIndex Column index
     * @param int $columnCount Total number of columns
     * @return string|null Column value or null if not available
     */
    private function getColumnValue(array $data, int $columnIndex, int $columnCount): ?string
    {
        if ($columnIndex >= 0 && $columnIndex < $columnCount && isset($data[$columnIndex])) {
            $value = trim($data[$columnIndex]);
            return !empty($value) ? $value : null;
        }
        return null;
    }

    private function processBatch(array $batch, bool $segment = true): void
    {
        // Store phone numbers in the database
        foreach ($batch as $item) {
            try {
                $number = $item['number'];
                $fields = $item['fields'];

                // Check if the number already exists in the database
                $existingNumber = $this->phoneNumberRepository->findByNumber($number);
                if ($existingNumber === null) {
                    // Create a new phone number with additional fields
                    $phoneNumber = new PhoneNumber(
                        $number,
                        null, // id
                        $fields['civility'],
                        $fields['firstName'],
                        $fields['name'],
                        $fields['company'],
                        $fields['sector'],
                        $fields['notes']
                    );

                    // Note: Email field is collected but PhoneNumber model doesn't have email property
                    // If needed in the future, extend the PhoneNumber model to include email

                    $this->phoneNumberRepository->save($phoneNumber);

                    // Segment the number if requested
                    if ($segment) {
                        $this->segmentationService->segmentPhoneNumber($phoneNumber);
                    }
                } else {
                    // Number already exists, increment duplicates count
                    $this->stats['duplicates']++;

                    // Add detailed error for duplicate
                    if (count($this->detailedErrors) < $this->maxDetailedErrors) {
                        $this->detailedErrors[] = [
                            'line' => 'unknown', // Line number not available in batch context
                            'value' => $number,
                            'message' => "Numéro déjà existant dans la base de données"
                        ];
                    }

                    // Optionally update existing record with new information if provided
                    $updated = false;
                    if (!empty(array_filter($fields, function ($value) {
                        return $value !== null;
                    }))) {
                        if ($fields['civility'] !== null && $existingNumber->getCivility() === null) {
                            $existingNumber->setCivility($fields['civility']);
                            $updated = true;
                        }
                        if ($fields['firstName'] !== null && $existingNumber->getFirstName() === null) {
                            $existingNumber->setFirstName($fields['firstName']);
                            $updated = true;
                        }
                        if ($fields['name'] !== null && $existingNumber->getName() === null) {
                            $existingNumber->setName($fields['name']);
                            $updated = true;
                        }
                        if ($fields['company'] !== null && $existingNumber->getCompany() === null) {
                            $existingNumber->setCompany($fields['company']);
                            $updated = true;
                        }
                        if ($fields['sector'] !== null && $existingNumber->getSector() === null) {
                            $existingNumber->setSector($fields['sector']);
                            $updated = true;
                        }
                        if ($fields['notes'] !== null && $existingNumber->getNotes() === null) {
                            $existingNumber->setNotes($fields['notes']);
                            $updated = true;
                        }

                        if ($updated) {
                            $this->phoneNumberRepository->save($existingNumber);
                        }
                    }
                }

                $this->stats['processed']++;
            } catch (\Exception $e) {
                $this->errors[] = "Error processing number {$item['number']}: " . $e->getMessage();

                // Add detailed error for processing exception
                if (count($this->detailedErrors) < $this->maxDetailedErrors) {
                    $this->detailedErrors[] = [
                        'line' => 'unknown', // Line number not available in batch context
                        'value' => $item['number'],
                        'message' => "Erreur de traitement: " . $e->getMessage()
                    ];
                }
            }
        }
    }

    /**
     * Normalize a phone number
     * 
     * @param string $number Phone number to normalize
     * @return string|null Normalized phone number or null if invalid
     */
    private function normalizePhoneNumber(string $number): ?string
    {
        // Remove all non-numeric characters except the + sign
        $number = preg_replace('/[^0-9+]/', '', $number);

        // If the number is empty after cleaning, it's invalid
        if (empty($number)) {
            return null;
        }

        // If the number starts with 00, replace with +
        if (strpos($number, '00') === 0) {
            $number = '+' . substr($number, 2);
        }

        // If the number doesn't start with +, add the default country code (225 for Côte d'Ivoire)
        if (strpos($number, '+') !== 0) {
            // Check if the number already has the country code without the +
            if (strpos($number, '225') === 0) {
                $number = '+' . $number;
            } else {
                $number = '+225' . $number;
            }
        }

        // Validate the number format (basic validation)
        if (!preg_match('/^\+[0-9]{6,15}$/', $number)) {
            return null;
        }

        return $number;
    }

    /**
     * Reset statistics and errors
     * 
     * @return void
     */
    private function resetStats(): void
    {
        $this->stats = [
            'total' => 0,
            'valid' => 0,
            'invalid' => 0,
            'duplicates' => 0,
            'processed' => 0
        ];
        $this->errors = [];
        $this->detailedErrors = [];
        $this->currentLine = 0;
    }

    /**
     * Get import results
     * 
     * @return array Import results
     */
    private function getResults(): array
    {
        return [
            'status' => empty($this->errors) ? 'success' : 'error',
            'stats' => $this->stats,
            'errors' => $this->errors,
            'detailedErrors' => $this->detailedErrors
        ];
    }
}
