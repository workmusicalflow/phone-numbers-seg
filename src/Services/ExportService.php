<?php

namespace App\Services;

use App\Repositories\Interfaces\PhoneNumberRepositoryInterface;
use App\Models\PhoneNumber;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Service for exporting phone numbers and segments to various formats
 */
class ExportService
{
    /**
     * @var PhoneNumberRepositoryInterface
     */
    private PhoneNumberRepositoryInterface $phoneNumberRepository;

    /**
     * @var array Export statistics
     */
    private array $stats = [
        'total' => 0,
        'exported' => 0,
        'failed' => 0
    ];

    /**
     * @var array Export errors
     */
    private array $errors = [];

    /**
     * Constructor
     * 
     * @param PhoneNumberRepositoryInterface $phoneNumberRepository
     */
    public function __construct(PhoneNumberRepositoryInterface $phoneNumberRepository)
    {
        $this->phoneNumberRepository = $phoneNumberRepository;
    }

    /**
     * Export phone numbers to CSV format
     * 
     * @param array $options Export options
     * @return array|string Export results or CSV content
     */
    public function exportToCSV(array $options = [])
    {
        // Reset statistics and errors
        $this->resetStats();

        // Set default options
        $options = array_merge([
            'includeHeaders' => true,
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
            'includeSegments' => true,
            'includeContactInfo' => true,
            'downloadFile' => true,
            'filename' => 'phone_numbers_export_' . date('Y-m-d_H-i-s') . '.csv',
            'filters' => [],
            'limit' => 5000,
            'offset' => 0
        ], $options);

        try {
            // Get phone numbers based on filters
            $phoneNumbers = $this->getPhoneNumbers($options['filters'], $options['limit'], $options['offset']);
            $this->stats['total'] = count($phoneNumbers);

            if (empty($phoneNumbers)) {
                $this->errors[] = "No phone numbers found matching the specified criteria.";
                return $this->getResults();
            }

            // Create a temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'csv_export_');
            $handle = fopen($tempFile, 'w');

            if ($handle === false) {
                $this->errors[] = "Failed to create temporary file for export.";
                return $this->getResults();
            }

            // Write headers if requested
            if ($options['includeHeaders']) {
                $headers = ['Number'];

                // Add contact info headers if requested
                if ($options['includeContactInfo']) {
                    $headers = array_merge($headers, ['Civility', 'First Name', 'Name', 'Company', 'Sector', 'Notes']);
                }

                // Add segment headers if requested
                if ($options['includeSegments']) {
                    // Get all unique segment types
                    $segmentTypes = [];
                    foreach ($phoneNumbers as $phoneNumber) {
                        foreach ($phoneNumber->getTechnicalSegments() as $segment) {
                            $segmentTypes[$segment->getType()] = true;
                        }
                        foreach ($phoneNumber->getCustomSegments() as $segment) {
                            $segmentTypes[$segment->getType()] = true;
                        }
                    }
                    $segmentTypes = array_keys($segmentTypes);
                    sort($segmentTypes);

                    // Add segment types to headers
                    foreach ($segmentTypes as $segmentType) {
                        $headers[] = $segmentType;
                    }
                }

                // Write headers to file
                fputcsv($handle, $headers, $options['delimiter'], $options['enclosure'], $options['escape']);
            }

            // Write data rows
            foreach ($phoneNumbers as $phoneNumber) {
                $row = [$phoneNumber->getNumber()];

                // Add contact info if requested
                if ($options['includeContactInfo']) {
                    $row = array_merge($row, [
                        $phoneNumber->getCivility(),
                        $phoneNumber->getFirstName(),
                        $phoneNumber->getName(),
                        $phoneNumber->getCompany(),
                        $phoneNumber->getSector(),
                        $phoneNumber->getNotes()
                    ]);
                }

                // Add segments if requested
                if ($options['includeSegments']) {
                    // Get all segments (technical and custom)
                    $segments = [];
                    foreach ($phoneNumber->getTechnicalSegments() as $segment) {
                        $segments[$segment->getType()] = $segment->getValue();
                    }
                    foreach ($phoneNumber->getCustomSegments() as $segment) {
                        $segments[$segment->getType()] = $segment->getValue();
                    }

                    // Add segment values in the same order as headers
                    foreach ($segmentTypes as $segmentType) {
                        $row[] = isset($segments[$segmentType]) ? $segments[$segmentType] : '';
                    }
                }

                // Write row to file
                fputcsv($handle, $row, $options['delimiter'], $options['enclosure'], $options['escape']);
                $this->stats['exported']++;
            }

            fclose($handle);

            // Read the file content
            $content = file_get_contents($tempFile);
            unlink($tempFile);

            if ($options['downloadFile']) {
                // Set headers for file download
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="' . $options['filename'] . '"');
                header('Content-Length: ' . strlen($content));
                echo $content;
                exit;
            }

            return $content;
        } catch (\Exception $e) {
            $this->errors[] = "Error exporting to CSV: " . $e->getMessage();
            return $this->getResults();
        }
    }

    /**
     * Export phone numbers to Excel format
     * 
     * @param array $options Export options
     * @return array|string Export results or Excel content
     */
    public function exportToExcel(array $options = [])
    {
        // Check if PhpSpreadsheet is installed
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            $this->errors[] = "PhpSpreadsheet is not installed. Please run 'composer require phpoffice/phpspreadsheet'.";
            return $this->getResults();
        }

        // Reset statistics and errors
        $this->resetStats();

        // Set default options
        $options = array_merge([
            'includeHeaders' => true,
            'includeSegments' => true,
            'includeContactInfo' => true,
            'downloadFile' => true,
            'filename' => 'phone_numbers_export_' . date('Y-m-d_H-i-s') . '.xlsx',
            'filters' => [],
            'limit' => 5000,
            'offset' => 0
        ], $options);

        try {
            // Get phone numbers based on filters
            $phoneNumbers = $this->getPhoneNumbers($options['filters'], $options['limit'], $options['offset']);
            $this->stats['total'] = count($phoneNumbers);

            if (empty($phoneNumbers)) {
                $this->errors[] = "No phone numbers found matching the specified criteria.";
                return $this->getResults();
            }

            // Create a new Spreadsheet
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Phone Numbers');

            // Prepare headers
            $headers = ['Number'];

            // Add contact info headers if requested
            if ($options['includeContactInfo']) {
                $headers = array_merge($headers, ['Civility', 'First Name', 'Name', 'Company', 'Sector', 'Notes']);
            }

            // Add segment headers if requested
            if ($options['includeSegments']) {
                // Get all unique segment types
                $segmentTypes = [];
                foreach ($phoneNumbers as $phoneNumber) {
                    foreach ($phoneNumber->getTechnicalSegments() as $segment) {
                        $segmentTypes[$segment->getType()] = true;
                    }
                    foreach ($phoneNumber->getCustomSegments() as $segment) {
                        $segmentTypes[$segment->getType()] = true;
                    }
                }
                $segmentTypes = array_keys($segmentTypes);
                sort($segmentTypes);

                // Add segment types to headers
                foreach ($segmentTypes as $segmentType) {
                    $headers[] = $segmentType;
                }
            }

            // Write headers if requested
            $row = 1;
            if ($options['includeHeaders']) {
                $col = 0;
                foreach ($headers as $header) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $sheet->setCellValue($colLetter . $row, $header);
                    $col++;
                }
                $row++;
            }

            // Write data rows
            foreach ($phoneNumbers as $phoneNumber) {
                $col = 0;
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $sheet->setCellValue($colLetter . $row, $phoneNumber->getNumber());
                $col++;

                // Add contact info if requested
                if ($options['includeContactInfo']) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $sheet->setCellValue($colLetter . $row, $phoneNumber->getCivility());
                    $col++;

                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $sheet->setCellValue($colLetter . $row, $phoneNumber->getFirstName());
                    $col++;

                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $sheet->setCellValue($colLetter . $row, $phoneNumber->getName());
                    $col++;

                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $sheet->setCellValue($colLetter . $row, $phoneNumber->getCompany());
                    $col++;

                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $sheet->setCellValue($colLetter . $row, $phoneNumber->getSector());
                    $col++;

                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $sheet->setCellValue($colLetter . $row, $phoneNumber->getNotes());
                    $col++;
                }

                // Add segments if requested
                if ($options['includeSegments']) {
                    // Get all segments (technical and custom)
                    $segments = [];
                    foreach ($phoneNumber->getTechnicalSegments() as $segment) {
                        $segments[$segment->getType()] = $segment->getValue();
                    }
                    foreach ($phoneNumber->getCustomSegments() as $segment) {
                        $segments[$segment->getType()] = $segment->getValue();
                    }

                    // Add segment values in the same order as headers
                    foreach ($segmentTypes as $segmentType) {
                        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                        $sheet->setCellValue($colLetter . $row, isset($segments[$segmentType]) ? $segments[$segmentType] : '');
                        $col++;
                    }
                }

                $row++;
                $this->stats['exported']++;
            }

            // Auto-size columns
            foreach (range(0, count($headers) - 1) as $col) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }

            // Create a temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'excel_export_');
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($tempFile);

            // Read the file content
            $content = file_get_contents($tempFile);
            unlink($tempFile);

            if ($options['downloadFile']) {
                // Set headers for file download
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $options['filename'] . '"');
                header('Content-Length: ' . strlen($content));
                echo $content;
                exit;
            }

            return $content;
        } catch (\Exception $e) {
            $this->errors[] = "Error exporting to Excel: " . $e->getMessage();
            return $this->getResults();
        }
    }

    /**
     * Get phone numbers based on filters
     * 
     * @param array $filters Filters to apply
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @return array Array of PhoneNumber objects
     */
    private function getPhoneNumbers(array $filters = [], int $limit = 5000, int $offset = 0): array
    {
        // Apply search filter
        if (isset($filters['search']) && !empty($filters['search'])) {
            return $this->phoneNumberRepository->search($filters['search'], $limit, $offset);
        }

        // Apply advanced filters
        $advancedFilters = [];

        // Filter by operator
        if (isset($filters['operator']) && !empty($filters['operator'])) {
            $advancedFilters['operator'] = $filters['operator'];
        }

        // Filter by country
        if (isset($filters['country']) && !empty($filters['country'])) {
            $advancedFilters['country'] = $filters['country'];
        }

        // Filter by date range
        if (isset($filters['dateFrom']) && !empty($filters['dateFrom'])) {
            $advancedFilters['dateFrom'] = $filters['dateFrom'];
        }

        if (isset($filters['dateTo']) && !empty($filters['dateTo'])) {
            $advancedFilters['dateTo'] = $filters['dateTo'];
        }

        // Filter by segment
        if (isset($filters['segment']) && !empty($filters['segment'])) {
            $advancedFilters['segment'] = $filters['segment'];
        }

        // Apply advanced filters if any
        if (!empty($advancedFilters)) {
            return $this->phoneNumberRepository->findByFilters($advancedFilters, $limit, $offset);
        }

        // Return all phone numbers if no filters are applied
        return $this->phoneNumberRepository->findAll($limit, $offset);
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
            'exported' => 0,
            'failed' => 0
        ];
        $this->errors = [];
    }

    /**
     * Get export results
     * 
     * @return array Export results
     */
    private function getResults(): array
    {
        return [
            'status' => empty($this->errors) ? 'success' : 'error',
            'stats' => $this->stats,
            'errors' => $this->errors
        ];
    }
}
