<?php

namespace App\Controllers;

use App\Services\CSVImportService;
use App\Services\ExportService;

/**
 * Controller for handling import and export operations
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
     * Handle import CSV request
     * 
     * @param array $data Request data
     * @return array Response data
     */
    public function importCSV(array $data): array
    {
        // Validate request
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            return [
                'status' => 'error',
                'message' => 'No file uploaded or upload error occurred'
            ];
        }

        $file = $_FILES['csv_file'];

        // Check file type
        $mimeType = mime_content_type($file['tmp_name']);
        if (!in_array($mimeType, ['text/csv', 'text/plain', 'application/vnd.ms-excel'])) {
            return [
                'status' => 'error',
                'message' => 'Invalid file type. Only CSV files are allowed.'
            ];
        }

        // Prepare import options
        $options = [
            'hasHeader' => isset($data['has_header']) ? (bool)$data['has_header'] : true,
            'phoneColumn' => isset($data['phone_column']) ? (int)$data['phone_column'] : 0,
            'civilityColumn' => isset($data['civility_column']) ? (int)$data['civility_column'] : -1,
            'firstNameColumn' => isset($data['first_name_column']) ? (int)$data['first_name_column'] : -1,
            'nameColumn' => isset($data['name_column']) ? (int)$data['name_column'] : -1,
            'companyColumn' => isset($data['company_column']) ? (int)$data['company_column'] : -1,
            'sectorColumn' => isset($data['sector_column']) ? (int)$data['sector_column'] : -1,
            'notesColumn' => isset($data['notes_column']) ? (int)$data['notes_column'] : -1,
            'skipInvalid' => isset($data['skip_invalid']) ? (bool)$data['skip_invalid'] : true,
            'segmentImmediately' => isset($data['segment_immediately']) ? (bool)$data['segment_immediately'] : true
        ];

        // Process the file
        $result = $this->csvImportService->importFromFile($file['tmp_name'], $options);

        return $result;
    }

    /**
     * Handle import from text request
     * 
     * @param array $data Request data
     * @return array Response data
     */
    public function importFromText(array $data): array
    {
        // Validate request
        if (!isset($data['numbers']) || empty($data['numbers'])) {
            return [
                'status' => 'error',
                'message' => 'No phone numbers provided'
            ];
        }

        // Parse numbers from text
        $numbersText = $data['numbers'];
        $numbers = preg_split('/[\s,;]+/', $numbersText, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($numbers)) {
            return [
                'status' => 'error',
                'message' => 'No valid phone numbers found in the provided text'
            ];
        }

        // Prepare import options
        $options = [
            'skipInvalid' => isset($data['skip_invalid']) ? (bool)$data['skip_invalid'] : true,
            'segmentImmediately' => isset($data['segment_immediately']) ? (bool)$data['segment_immediately'] : true
        ];

        // Process the numbers
        $result = $this->csvImportService->importFromArray($numbers, $options);

        return $result;
    }

    /**
     * Handle export to CSV request
     * 
     * @param array $data Request data
     * @return array|string Response data or CSV content
     */
    public function exportToCSV(array $data)
    {
        // Prepare export options
        $options = [
            'includeHeaders' => isset($data['include_headers']) ? (bool)$data['include_headers'] : true,
            'delimiter' => isset($data['delimiter']) ? $data['delimiter'] : ',',
            'enclosure' => isset($data['enclosure']) ? $data['enclosure'] : '"',
            'escape' => isset($data['escape']) ? $data['escape'] : '\\',
            'includeSegments' => isset($data['include_segments']) ? (bool)$data['include_segments'] : true,
            'includeContactInfo' => isset($data['include_contact_info']) ? (bool)$data['include_contact_info'] : true,
            'downloadFile' => isset($data['download_file']) ? (bool)$data['download_file'] : true,
            'filename' => isset($data['filename']) ? $data['filename'] : 'phone_numbers_export_' . date('Y-m-d_H-i-s') . '.csv',
            'filters' => [],
            'limit' => isset($data['limit']) ? (int)$data['limit'] : 5000,
            'offset' => isset($data['offset']) ? (int)$data['offset'] : 0
        ];

        // Add search filter if provided
        if (isset($data['search']) && !empty($data['search'])) {
            $options['filters']['search'] = $data['search'];
        }

        // Process the export
        return $this->exportService->exportToCSV($options);
    }

    /**
     * Handle export to Excel request
     * 
     * @param array $data Request data
     * @return array|string Response data or Excel content
     */
    public function exportToExcel(array $data)
    {
        // Prepare export options
        $options = [
            'includeHeaders' => isset($data['include_headers']) ? (bool)$data['include_headers'] : true,
            'includeSegments' => isset($data['include_segments']) ? (bool)$data['include_segments'] : true,
            'includeContactInfo' => isset($data['include_contact_info']) ? (bool)$data['include_contact_info'] : true,
            'downloadFile' => isset($data['download_file']) ? (bool)$data['download_file'] : true,
            'filename' => isset($data['filename']) ? $data['filename'] : 'phone_numbers_export_' . date('Y-m-d_H-i-s') . '.xlsx',
            'filters' => [],
            'limit' => isset($data['limit']) ? (int)$data['limit'] : 5000,
            'offset' => isset($data['offset']) ? (int)$data['offset'] : 0
        ];

        // Add search filter if provided
        if (isset($data['search']) && !empty($data['search'])) {
            $options['filters']['search'] = $data['search'];
        }

        // Process the export
        return $this->exportService->exportToExcel($options);
    }
}
