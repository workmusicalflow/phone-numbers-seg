<?php

namespace App\Services;

use App\Models\PhoneNumber;
use App\Repositories\PhoneNumberRepository;
use App\Repositories\TechnicalSegmentRepository;

/**
 * BatchSegmentationService
 * 
 * Service for batch processing of phone numbers
 */
class BatchSegmentationService
{
    /**
     * @var PhoneSegmentationService
     */
    private PhoneSegmentationService $segmentationService;

    /**
     * @var PhoneNumberRepository|null
     */
    private ?PhoneNumberRepository $phoneNumberRepository;

    /**
     * @var TechnicalSegmentRepository|null
     */
    private ?TechnicalSegmentRepository $technicalSegmentRepository;

    /**
     * Constructor
     * 
     * @param PhoneSegmentationService $segmentationService
     * @param PhoneNumberRepository|null $phoneNumberRepository
     * @param TechnicalSegmentRepository|null $technicalSegmentRepository
     */
    public function __construct(
        PhoneSegmentationService $segmentationService,
        ?PhoneNumberRepository $phoneNumberRepository = null,
        ?TechnicalSegmentRepository $technicalSegmentRepository = null
    ) {
        $this->segmentationService = $segmentationService;
        $this->phoneNumberRepository = $phoneNumberRepository;
        $this->technicalSegmentRepository = $technicalSegmentRepository;
    }

    /**
     * Process multiple phone numbers without saving to database
     * 
     * @param array $phoneNumbers Array of phone number strings
     * @return array Array of segmented PhoneNumber objects
     */
    public function processPhoneNumbers(array $phoneNumbers): array
    {
        $results = [];
        $errors = [];

        foreach ($phoneNumbers as $index => $number) {
            try {
                $phoneNumber = new PhoneNumber($number);

                if (!$phoneNumber->isValid()) {
                    $errors[$index] = [
                        'number' => $number,
                        'error' => 'Invalid phone number format'
                    ];
                    continue;
                }

                $segmentedPhoneNumber = $this->segmentationService->segmentPhoneNumber($phoneNumber);
                $results[$index] = $segmentedPhoneNumber;
            } catch (\Exception $e) {
                $errors[$index] = [
                    'number' => $number,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'results' => $results,
            'errors' => $errors
        ];
    }

    /**
     * Process and save multiple phone numbers to database
     * 
     * @param array $phoneNumbers Array of phone number strings
     * @return array Array of results and errors
     * @throws \RuntimeException If repositories are not provided
     */
    public function processAndSavePhoneNumbers(array $phoneNumbers): array
    {
        if ($this->phoneNumberRepository === null || $this->technicalSegmentRepository === null) {
            throw new \RuntimeException('Phone number and technical segment repositories are required for saving to database');
        }

        $results = [];
        $errors = [];

        foreach ($phoneNumbers as $index => $number) {
            try {
                $phoneNumber = new PhoneNumber($number);

                if (!$phoneNumber->isValid()) {
                    $errors[$index] = [
                        'number' => $number,
                        'error' => 'Invalid phone number format'
                    ];
                    continue;
                }

                // Check if the phone number already exists
                $existingPhoneNumber = $this->phoneNumberRepository->findByNumber($phoneNumber->getNumber());
                if ($existingPhoneNumber) {
                    $errors[$index] = [
                        'number' => $number,
                        'error' => 'Phone number already exists'
                    ];
                    continue;
                }

                // Segment the phone number
                $segmentedPhoneNumber = $this->segmentationService->segmentPhoneNumber($phoneNumber);

                // Save the phone number with segments
                $savedPhoneNumber = $this->phoneNumberRepository->save($segmentedPhoneNumber);

                $results[$index] = $savedPhoneNumber;
            } catch (\Exception $e) {
                $errors[$index] = [
                    'number' => $number,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'results' => $results,
            'errors' => $errors
        ];
    }

    /**
     * Format the results for API response
     * 
     * @param array $processResults Results from processPhoneNumbers or processAndSavePhoneNumbers
     * @return array Formatted results for API response
     */
    public function formatResults(array $processResults): array
    {
        $formattedResults = [];
        $formattedErrors = [];

        foreach ($processResults['results'] as $index => $phoneNumber) {
            $formattedResults[$index] = $phoneNumber->toArray();
        }

        foreach ($processResults['errors'] as $index => $error) {
            $formattedErrors[$index] = $error;
        }

        return [
            'results' => $formattedResults,
            'errors' => $formattedErrors,
            'summary' => [
                'total' => count($processResults['results']) + count($processResults['errors']),
                'successful' => count($processResults['results']),
                'failed' => count($processResults['errors'])
            ]
        ];
    }
}
