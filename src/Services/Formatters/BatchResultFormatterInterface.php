<?php

namespace App\Services\Formatters;

/**
 * Interface for batch result formatters
 */
interface BatchResultFormatterInterface
{
    /**
     * Format batch processing results for API response
     * 
     * @param array $processResults Results from batch processing
     * @return array Formatted results for API response
     */
    public function formatResults(array $processResults): array;

    /**
     * Format batch result for GraphQL response
     * 
     * @param array $result
     * @return array
     */
    public function formatForGraphQL(array $result): array;
}
