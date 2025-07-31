<?php

namespace App\GraphQL\Controllers;

use App\Services\CSVImportService;
use App\Services\ExportService;
use App\Repositories\Interfaces\ContactGroupRepositoryInterface;
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
     * @var ContactGroupRepositoryInterface
     */
    private ContactGroupRepositoryInterface $contactGroupRepository;

    /**
     * Constructor
     * 
     * @param CSVImportService $csvImportService
     * @param ExportService $exportService
     * @param ContactGroupRepositoryInterface $contactGroupRepository
     */
    public function __construct(
        CSVImportService $csvImportService, 
        ExportService $exportService,
        ContactGroupRepositoryInterface $contactGroupRepository
    ) {
        $this->csvImportService = $csvImportService;
        $this->exportService = $exportService;
        $this->contactGroupRepository = $contactGroupRepository;
    }

    /**
     * Validate that all provided group IDs exist and belong to the user
     * 
     * @param array $groupIds Array of group IDs to validate
     * @param int|null $userId User ID (if null, validation is skipped)
     * @return array Validation result with 'valid' boolean and 'errors' array
     */
    private function validateGroups(array $groupIds, ?int $userId = null): array
    {
        $errors = [];
        
        if (empty($groupIds)) {
            return ['valid' => true, 'errors' => []];
        }

        foreach ($groupIds as $groupId) {
            if (!is_numeric($groupId) || $groupId <= 0) {
                $errors[] = "Invalid group ID: {$groupId}";
                continue;
            }

            $group = $this->contactGroupRepository->findById((int)$groupId);
            if (!$group) {
                $errors[] = "Group with ID {$groupId} not found";
                continue;
            }

            // If userId is provided, check ownership
            if ($userId !== null && $group->getUserId() !== $userId) {
                $errors[] = "Group with ID {$groupId} does not belong to user {$userId}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Import phone numbers from an array
     * 
     * @Mutation
     * @param string[] $numbers
     * @param bool $skipInvalid
     * @param bool $segmentImmediately
     * @param int[]|null $groupIds Array of group IDs to assign contacts to
     * @param int|null $userId User ID to associate contacts with
     * @return array
     */
    public function importPhoneNumbers(
        array $numbers,
        bool $skipInvalid = true,
        bool $segmentImmediately = true,
        ?array $groupIds = null,
        ?int $userId = null
    ): array {
        // Validate groups if provided
        if (!empty($groupIds)) {
            $validationResult = $this->validateGroups($groupIds, $userId);
            if (!$validationResult['valid']) {
                return [
                    'status' => 'error',
                    'errors' => $validationResult['errors'],
                    'stats' => []
                ];
            }
        }

        $options = [
            'skipInvalid' => $skipInvalid,
            'segmentImmediately' => $segmentImmediately,
            'groupIds' => $groupIds ?? [],
            'userId' => $userId
        ];

        $result = $this->csvImportService->importFromArray($numbers, $options);
        
        // Transform the result to match GraphQL schema expectations
        return $this->transformImportResult($result);
    }

    /**
     * Import phone numbers with additional data
     * 
     * @Mutation
     * @param array $phoneData Array of phone data objects
     * @param bool $skipInvalid
     * @param bool $segmentImmediately
     * @param int[]|null $groupIds Array of group IDs to assign contacts to
     * @param int|null $userId User ID to associate contacts with
     * @return array
     */
    public function importPhoneNumbersWithData(
        array $phoneData,
        bool $skipInvalid = true,
        bool $segmentImmediately = true,
        ?array $groupIds = null,
        ?int $userId = null
    ): array {
        // Validate groups if provided
        if (!empty($groupIds)) {
            $validationResult = $this->validateGroups($groupIds, $userId);
            if (!$validationResult['valid']) {
                return [
                    'status' => 'error',
                    'errors' => $validationResult['errors'],
                    'stats' => []
                ];
            }
        }

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
            'additionalData' => $additionalData,
            'groupIds' => $groupIds ?? [],
            'userId' => $userId
        ];

        $result = $this->csvImportService->importFromArray($numbers, $options);
        
        // Transform the result to match GraphQL schema expectations
        return $this->transformImportResult($result);
    }

    /**
     * Transform import result from CSVImportService to GraphQL format
     * 
     * @param array $result Result from CSVImportService
     * @return array GraphQL-compatible result
     */
    private function transformImportResult(array $result): array
    {
        $stats = $result['stats'] ?? [];
        
        return [
            'status' => $result['status'] ?? 'error',
            'errors' => $result['errors'] ?? [],
            'stats' => [
                'processed' => $stats['total'] ?? 0,
                'successful' => $stats['valid'] ?? 0,
                'failed' => $stats['invalid'] ?? 0,
                'skipped' => 0, // CSVImportService doesn't track skipped separately
                'duplicates' => $stats['duplicates'] ?? 0,
                'groupAssignments' => $stats['groupAssignments'] ?? 0
            ]
        ];
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
