<?php

namespace App\GraphQL\Controllers;

use App\Services\CSVImportService;
use App\Services\ExportService;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Input;

/**
 * GraphQL controller for import/export operations
 */
class ImportExportController
{
    /**
     * @var CSVImportService
     */
    private CSVImportService $csvImportService;

    /**
     * @var ExportService
     */
    private ExportService $exportService;

    /**
     * Constructor
     * 
     * @param CSVImportService $csvImportService
     * @param ExportService $exportService
     */
    public function __construct(CSVImportService $csvImportService, ExportService $exportService)
    {
        $this->csvImportService = $csvImportService;
        $this->exportService = $exportService;
    }

    /**
     * Import phone numbers from an array
     * 
     * @Mutation
     * @param string[] $numbers
     * @param bool $skipInvalid
     * @param bool $segmentImmediately
     * @return array
     */
    public function importPhoneNumbers(
        array $numbers,
        bool $skipInvalid = true,
        bool $segmentImmediately = true
    ): array {
        $options = [
            'skipInvalid' => $skipInvalid,
            'segmentImmediately' => $segmentImmediately
        ];

        return $this->csvImportService->importFromArray($numbers, $options);
    }

    /**
     * Import phone numbers with additional data
     * 
     * @Mutation
     * @param array $phoneData Array of phone data objects
     * @param bool $skipInvalid
     * @param bool $segmentImmediately
     * @return array
     */
    public function importPhoneNumbersWithData(
        array $phoneData,
        bool $skipInvalid = true,
        bool $segmentImmediately = true
    ): array {
        // Convert the input data to the format expected by the import service
        $numbers = [];
        $additionalData = [];

        foreach ($phoneData as $data) {
            if (isset($data['number'])) {
                $number = $data['number'];
                $numbers[] = $number;

                $additionalData[$number] = [
                    'civility' => $data['civility'] ?? null,
                    'firstName' => $data['firstName'] ?? null,
                    'name' => $data['name'] ?? null,
                    'company' => $data['company'] ?? null,
                    'sector' => $data['sector'] ?? null,
                    'notes' => $data['notes'] ?? null
                ];
            }
        }

        $options = [
            'skipInvalid' => $skipInvalid,
            'segmentImmediately' => $segmentImmediately,
            'additionalData' => $additionalData
        ];

        return $this->csvImportService->importFromArray($numbers, $options);
    }

    /**
     * Export phone numbers to CSV format
     * 
     * @Query
     * @param ExportOptions $options Export options
     * @return ExportResult
     */
    public function exportToCSV(ExportOptions $options): ExportResult
    {
        // Convert input options to the format expected by the export service
        $exportOptions = [
            'includeHeaders' => $options->includeHeaders,
            'delimiter' => $options->delimiter,
            'enclosure' => $options->enclosure,
            'escape' => $options->escape,
            'includeSegments' => $options->includeSegments,
            'includeContactInfo' => $options->includeContactInfo,
            'downloadFile' => false, // Always false for GraphQL
            'filename' => $options->filename,
            'filters' => [],
            'limit' => $options->limit,
            'offset' => $options->offset
        ];

        // Add search filter if provided
        if (!empty($options->search)) {
            $exportOptions['filters']['search'] = $options->search;
        }

        // Add advanced filters if provided
        if (!empty($options->operator)) {
            $exportOptions['filters']['operator'] = $options->operator;
        }

        if (!empty($options->country)) {
            $exportOptions['filters']['country'] = $options->country;
        }

        if (!empty($options->segment)) {
            $exportOptions['filters']['segment'] = $options->segment;
        }

        if (!empty($options->dateFrom)) {
            $exportOptions['filters']['dateFrom'] = $options->dateFrom;
        }

        if (!empty($options->dateTo)) {
            $exportOptions['filters']['dateTo'] = $options->dateTo;
        }

        // Process the export
        $result = $this->exportService->exportToCSV($exportOptions);

        // Return the result
        if (is_array($result) && isset($result['status']) && $result['status'] === 'error') {
            return new ExportResult(
                false,
                $result['errors'] ?? ['Unknown error'],
                null,
                $result['stats'] ?? []
            );
        }

        return new ExportResult(
            true,
            [],
            base64_encode($result), // Encode the CSV content as base64
            ['exported' => $exportOptions['limit']]
        );
    }

    /**
     * Export phone numbers to Excel format
     * 
     * @Query
     * @param ExportOptions $options Export options
     * @return ExportResult
     */
    public function exportToExcel(ExportOptions $options): ExportResult
    {
        // Convert input options to the format expected by the export service
        $exportOptions = [
            'includeHeaders' => $options->includeHeaders,
            'includeSegments' => $options->includeSegments,
            'includeContactInfo' => $options->includeContactInfo,
            'downloadFile' => false, // Always false for GraphQL
            'filename' => $options->filename,
            'filters' => [],
            'limit' => $options->limit,
            'offset' => $options->offset
        ];

        // Add search filter if provided
        if (!empty($options->search)) {
            $exportOptions['filters']['search'] = $options->search;
        }

        // Add advanced filters if provided
        if (!empty($options->operator)) {
            $exportOptions['filters']['operator'] = $options->operator;
        }

        if (!empty($options->country)) {
            $exportOptions['filters']['country'] = $options->country;
        }

        if (!empty($options->segment)) {
            $exportOptions['filters']['segment'] = $options->segment;
        }

        if (!empty($options->dateFrom)) {
            $exportOptions['filters']['dateFrom'] = $options->dateFrom;
        }

        if (!empty($options->dateTo)) {
            $exportOptions['filters']['dateTo'] = $options->dateTo;
        }

        // Process the export
        $result = $this->exportService->exportToExcel($exportOptions);

        // Return the result
        if (is_array($result) && isset($result['status']) && $result['status'] === 'error') {
            return new ExportResult(
                false,
                $result['errors'] ?? ['Unknown error'],
                null,
                $result['stats'] ?? []
            );
        }

        return new ExportResult(
            true,
            [],
            base64_encode($result), // Encode the Excel content as base64
            ['exported' => $exportOptions['limit']]
        );
    }
}

/**
 * Input type for phone data
 * 
 * @Type
 */
class PhoneDataInput
{
    /**
     * @var string
     */
    public string $number;

    /**
     * @var string|null
     */
    public ?string $civility = null;

    /**
     * @var string|null
     */
    public ?string $firstName = null;

    /**
     * @var string|null
     */
    public ?string $name = null;

    /**
     * @var string|null
     */
    public ?string $company = null;

    /**
     * @var string|null
     */
    public ?string $sector = null;

    /**
     * @var string|null
     */
    public ?string $notes = null;
}

/**
 * Input type for export options
 * 
 * @Input
 */
class ExportOptions
{
    /**
     * @var string
     */
    public string $search = '';

    /**
     * @var int
     */
    public int $limit = 1000;

    /**
     * @var int
     */
    public int $offset = 0;

    /**
     * @var bool
     */
    public bool $includeHeaders = true;

    /**
     * @var bool
     */
    public bool $includeContactInfo = true;

    /**
     * @var bool
     */
    public bool $includeSegments = true;

    /**
     * @var string
     */
    public string $delimiter = ',';

    /**
     * @var string
     */
    public string $enclosure = '"';

    /**
     * @var string
     */
    public string $escape = '\\';

    /**
     * @var string
     */
    public string $filename = '';

    /**
     * @var string|null
     */
    public ?string $operator = null;

    /**
     * @var string|null
     */
    public ?string $country = null;

    /**
     * @var int|null
     */
    public ?int $segment = null;

    /**
     * @var string|null
     */
    public ?string $dateFrom = null;

    /**
     * @var string|null
     */
    public ?string $dateTo = null;
}

/**
 * Result type for export operations
 * 
 * @Type
 */
class ExportResult
{
    /**
     * @var bool
     */
    public bool $success;

    /**
     * @var array
     */
    public array $errors;

    /**
     * @var string|null
     */
    public ?string $data;

    /**
     * @var array
     */
    public array $stats;

    /**
     * Constructor
     * 
     * @param bool $success
     * @param array $errors
     * @param string|null $data
     * @param array $stats
     */
    public function __construct(bool $success, array $errors, ?string $data, array $stats)
    {
        $this->success = $success;
        $this->errors = $errors;
        $this->data = $data;
        $this->stats = $stats;
    }
}
