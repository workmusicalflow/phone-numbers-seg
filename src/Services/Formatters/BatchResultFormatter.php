<?php

namespace App\Services\Formatters;

use App\Models\PhoneNumber;

/**
 * Formatter for batch processing results
 */
class BatchResultFormatter implements BatchResultFormatterInterface
{
    /**
     * Format batch processing results for API response
     * 
     * @param array $processResults Results from batch processing
     * @return array Formatted results for API response
     */
    public function formatResults(array $processResults): array
    {
        $formattedResults = [];
        $formattedErrors = [];

        foreach ($processResults['results'] as $index => $phoneNumber) {
            if ($phoneNumber instanceof PhoneNumber) {
                $formattedResults[$index] = [
                    'phoneNumber' => $phoneNumber,
                    'success' => true
                ];
            } else {
                $formattedResults[$index] = $phoneNumber;
            }
        }

        foreach ($processResults['errors'] as $index => $error) {
            $formattedErrors[] = [
                'index' => $index,
                'number' => $error['number'] ?? '',
                'message' => $error['error'] ?? 'Unknown error'
            ];
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

    /**
     * Format batch result for GraphQL response
     * 
     * @param array $result
     * @return array
     */
    public function formatForGraphQL(array $result): array
    {
        $formattedResult = $this->formatResults($result);

        // Convert PhoneNumber objects to arrays for GraphQL
        if (isset($formattedResult['results'])) {
            foreach ($formattedResult['results'] as $index => $phoneNumberResult) {
                if (isset($phoneNumberResult['phoneNumber']) && $phoneNumberResult['phoneNumber'] instanceof PhoneNumber) {
                    // Keep the structure as is, GraphQL will handle the PhoneNumber object
                } elseif ($phoneNumberResult instanceof PhoneNumber) {
                    $formattedResult['results'][$index] = [
                        'phoneNumber' => $phoneNumberResult,
                        'success' => true
                    ];
                }
            }
        }

        return $formattedResult;
    }
}
