<?php

namespace App\Services;

use App\Models\PhoneNumber;
use App\Repositories\PhoneNumberRepository;
use App\Services\Interfaces\PhoneNumberImporterInterface;
use App\Services\Interfaces\PhoneSegmentationServiceInterface;

/**
 * Service for importing phone numbers into the system
 */
class PhoneNumberImporter implements PhoneNumberImporterInterface
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
     * @var array Import statistics
     */
    private array $stats = [
        'processed' => 0,
        'duplicates' => 0,
        'updated' => 0,
        'errors' => 0
    ];

    /**
     * @var array Error messages
     */
    private array $errors = [];

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
     * Import a phone number into the system
     * 
     * @param string $number Normalized phone number
     * @param array $fields Additional fields (civility, firstName, name, company, sector, notes)
     * @param bool $segment Whether to segment the number immediately
     * @return PhoneNumber|null The imported phone number or null if it already exists
     */
    public function importPhoneNumber(string $number, array $fields = [], bool $segment = true): ?PhoneNumber
    {
        try {
            // Check if the number already exists in the database
            $existingNumber = $this->phoneNumberRepository->findByNumber($number);

            if ($existingNumber === null) {
                // Create a new phone number with additional fields
                $phoneNumber = new PhoneNumber(
                    $number,
                    null, // id
                    $fields['civility'] ?? null,
                    $fields['firstName'] ?? null,
                    $fields['name'] ?? null,
                    $fields['company'] ?? null,
                    $fields['sector'] ?? null,
                    $fields['notes'] ?? null
                );

                $this->phoneNumberRepository->save($phoneNumber);

                // Segment the number if requested
                if ($segment) {
                    $this->segmentationService->segmentPhoneNumber($phoneNumber);
                }

                $this->stats['processed']++;
                return $phoneNumber;
            } else {
                // Number already exists, increment duplicates count
                $this->stats['duplicates']++;

                // Optionally update existing record with new information if provided
                $updated = $this->updateExistingPhoneNumber($existingNumber, $fields);

                if ($updated) {
                    $this->stats['updated']++;
                }

                return null;
            }
        } catch (\Exception $e) {
            $this->errors[] = "Error processing number {$number}: " . $e->getMessage();
            $this->stats['errors']++;
            return null;
        }
    }

    /**
     * Import multiple phone numbers into the system
     * 
     * @param array $batch Array of phone numbers with additional fields
     * @param bool $segment Whether to segment the numbers immediately
     * @return array Import results
     */
    public function importBatch(array $batch, bool $segment = true): array
    {
        // Reset statistics
        $this->resetStats();

        foreach ($batch as $item) {
            $number = $item['number'];
            $fields = $item['fields'] ?? [];

            $this->importPhoneNumber($number, $fields, $segment);
        }

        return $this->getResults();
    }

    /**
     * Update an existing phone number with new information if provided
     * 
     * @param PhoneNumber $phoneNumber Existing phone number
     * @param array $fields New fields to update
     * @return bool Whether the phone number was updated
     */
    private function updateExistingPhoneNumber(PhoneNumber $phoneNumber, array $fields): bool
    {
        $updated = false;

        if (!empty(array_filter($fields, function ($value) {
            return $value !== null;
        }))) {
            if (isset($fields['civility']) && $fields['civility'] !== null && $phoneNumber->getCivility() === null) {
                $phoneNumber->setCivility($fields['civility']);
                $updated = true;
            }

            if (isset($fields['firstName']) && $fields['firstName'] !== null && $phoneNumber->getFirstName() === null) {
                $phoneNumber->setFirstName($fields['firstName']);
                $updated = true;
            }

            if (isset($fields['name']) && $fields['name'] !== null && $phoneNumber->getName() === null) {
                $phoneNumber->setName($fields['name']);
                $updated = true;
            }

            if (isset($fields['company']) && $fields['company'] !== null && $phoneNumber->getCompany() === null) {
                $phoneNumber->setCompany($fields['company']);
                $updated = true;
            }

            if (isset($fields['sector']) && $fields['sector'] !== null && $phoneNumber->getSector() === null) {
                $phoneNumber->setSector($fields['sector']);
                $updated = true;
            }

            if (isset($fields['notes']) && $fields['notes'] !== null && $phoneNumber->getNotes() === null) {
                $phoneNumber->setNotes($fields['notes']);
                $updated = true;
            }

            if ($updated) {
                $this->phoneNumberRepository->save($phoneNumber);
            }
        }

        return $updated;
    }

    /**
     * Reset statistics and errors
     * 
     * @return void
     */
    private function resetStats(): void
    {
        $this->stats = [
            'processed' => 0,
            'duplicates' => 0,
            'updated' => 0,
            'errors' => 0
        ];
        $this->errors = [];
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
            'errors' => $this->errors
        ];
    }
}
