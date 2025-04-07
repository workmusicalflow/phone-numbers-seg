<?php

namespace App\GraphQL\Controllers;

use App\Models\PhoneNumber;
use App\Services\Formatters\BatchResultFormatterInterface;
use App\Services\Interfaces\BatchSegmentationServiceInterface;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Mutation;

/**
 * GraphQL controller for batch operations
 */
class BatchController
{
    /**
     * @var BatchSegmentationServiceInterface
     */
    private BatchSegmentationServiceInterface $batchSegmentationService;

    /**
     * @var BatchResultFormatterInterface
     */
    private BatchResultFormatterInterface $resultFormatter;

    /**
     * Constructor
     * 
     * @param BatchSegmentationServiceInterface $batchSegmentationService
     * @param BatchResultFormatterInterface $resultFormatter
     */
    public function __construct(
        BatchSegmentationServiceInterface $batchSegmentationService,
        BatchResultFormatterInterface $resultFormatter
    ) {
        $this->batchSegmentationService = $batchSegmentationService;
        $this->resultFormatter = $resultFormatter;
    }

    /**
     * Process a batch of phone numbers without saving to database
     * 
     * @Mutation
     * @param string[] $phoneNumbers
     * @return array
     */
    public function processPhoneNumbers(array $phoneNumbers): array
    {
        try {
            $result = $this->batchSegmentationService->processPhoneNumbers($phoneNumbers);
            return $this->resultFormatter->formatForGraphQL($result);
        } catch (\Exception $e) {
            return [
                'results' => [],
                'errors' => [
                    [
                        'index' => 0,
                        'number' => '',
                        'message' => $e->getMessage()
                    ]
                ],
                'summary' => [
                    'total' => count($phoneNumbers),
                    'successful' => 0,
                    'failed' => count($phoneNumbers)
                ]
            ];
        }
    }

    /**
     * Process and save a batch of phone numbers to database
     * 
     * @Mutation
     * @param string[] $phoneNumbers
     * @return array
     */
    public function processAndSavePhoneNumbers(array $phoneNumbers): array
    {
        try {
            $result = $this->batchSegmentationService->processAndSavePhoneNumbers($phoneNumbers);
            return $this->resultFormatter->formatForGraphQL($result);
        } catch (\Exception $e) {
            return [
                'results' => [],
                'errors' => [
                    [
                        'index' => 0,
                        'number' => '',
                        'message' => $e->getMessage()
                    ]
                ],
                'summary' => [
                    'total' => count($phoneNumbers),
                    'successful' => 0,
                    'failed' => count($phoneNumbers)
                ]
            ];
        }
    }
}
