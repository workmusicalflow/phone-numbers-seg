<?php

namespace App\Services;

use App\Exceptions\BatchProcessingException;
use App\Exceptions\RepositoryException;
use App\Models\PhoneNumber;
use App\Repositories\PhoneNumberRepository;
use App\Repositories\TechnicalSegmentRepository;
use App\Services\Formatters\BatchResultFormatterInterface;
use App\Services\Interfaces\BatchSegmentationServiceInterface;
use App\Services\Interfaces\PhoneSegmentationServiceInterface;

/**
 * BatchSegmentationService
 * 
 * Service for batch processing of phone numbers
 */
class BatchSegmentationService implements BatchSegmentationServiceInterface
{
    /**
     * @var PhoneSegmentationServiceInterface
     */
    private PhoneSegmentationServiceInterface $segmentationService;

    /**
     * @var PhoneNumberRepository
     */
    private PhoneNumberRepository $phoneNumberRepository;

    /**
     * @var TechnicalSegmentRepository
     */
    private TechnicalSegmentRepository $technicalSegmentRepository;

    /**
     * @var BatchResultFormatterInterface
     */
    private BatchResultFormatterInterface $resultFormatter;

    /**
     * Constructor
     * 
     * @param PhoneSegmentationServiceInterface $segmentationService
     * @param PhoneNumberRepository $phoneNumberRepository
     * @param TechnicalSegmentRepository $technicalSegmentRepository
     * @param BatchResultFormatterInterface $resultFormatter
     */
    public function __construct(
        PhoneSegmentationServiceInterface $segmentationService,
        PhoneNumberRepository $phoneNumberRepository,
        TechnicalSegmentRepository $technicalSegmentRepository,
        BatchResultFormatterInterface $resultFormatter
    ) {
        $this->segmentationService = $segmentationService;
        $this->phoneNumberRepository = $phoneNumberRepository;
        $this->technicalSegmentRepository = $technicalSegmentRepository;
        $this->resultFormatter = $resultFormatter;
    }

    /**
     * Process multiple phone numbers without saving to database
     * 
     * @param array $phoneNumbers Array of phone number strings
     * @return array Array of segmented PhoneNumber objects
     * @throws BatchProcessingException If there are errors during processing
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

        $processResults = [
            'results' => $results,
            'errors' => $errors
        ];

        // If all numbers failed, throw an exception
        if (empty($results) && !empty($errors)) {
            throw new BatchProcessingException(
                'All phone numbers failed to process',
                $errors
            );
        }

        return $processResults;
    }

    /**
     * Process and save multiple phone numbers to database
     * 
     * @param array $phoneNumbers Array of phone number strings
     * @return array Array of results and errors
     * @throws BatchProcessingException If there are errors during processing
     * @throws RepositoryException If there are errors during database operations
     */
    public function processAndSavePhoneNumbers(array $phoneNumbers): array
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
                try {
                    $savedPhoneNumber = $this->phoneNumberRepository->save($segmentedPhoneNumber);
                    $results[$index] = $savedPhoneNumber;
                } catch (\Exception $e) {
                    throw new RepositoryException(
                        'Failed to save phone number: ' . $e->getMessage(),
                        $e->getCode(),
                        $e
                    );
                }
            } catch (RepositoryException $e) {
                // Re-throw repository exceptions
                throw $e;
            } catch (\Exception $e) {
                $errors[$index] = [
                    'number' => $number,
                    'error' => $e->getMessage()
                ];
            }
        }

        $processResults = [
            'results' => $results,
            'errors' => $errors
        ];

        // If all numbers failed, throw an exception
        if (empty($results) && !empty($errors)) {
            throw new BatchProcessingException(
                'All phone numbers failed to process and save',
                $errors
            );
        }

        return $processResults;
    }
}
