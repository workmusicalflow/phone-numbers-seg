<?php

namespace App\GraphQL\DataLoaders;

use App\Repositories\Interfaces\SMSHistoryRepositoryInterface;
use App\GraphQL\Formatters\GraphQLFormatterInterface;
use Psr\Log\LoggerInterface;

/**
 * SMSHistoryDataLoader
 * 
 * DataLoader implementation for SMS history batch processing.
 */
class SMSHistoryDataLoader extends DataLoader
{
    /**
     * @var SMSHistoryRepositoryInterface
     */
    private $smsHistoryRepository;

    /**
     * @var GraphQLFormatterInterface
     */
    private $formatter;

    /**
     * @var int|null
     */
    private $userId;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     * 
     * @param SMSHistoryRepositoryInterface $smsHistoryRepository SMS history repository
     * @param GraphQLFormatterInterface $formatter GraphQL formatter
     * @param LoggerInterface $logger Logger
     */
    public function __construct(
        SMSHistoryRepositoryInterface $smsHistoryRepository,
        GraphQLFormatterInterface $formatter,
        LoggerInterface $logger
    ) {
        $this->smsHistoryRepository = $smsHistoryRepository;
        $this->formatter = $formatter;
        $this->logger = $logger;

        parent::__construct([$this, 'batchLoadSMSHistory'], $logger);
    }

    /**
     * Set the current user ID
     * 
     * @param int $userId The user ID
     * @return self
     */
    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Get the current user ID
     * 
     * @return int|null The user ID
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * Batch load function for SMS history by criteria
     * 
     * @param array $criteriaList List of criteria arrays to batch process
     * @return array Array of SMS history results for each criteria
     */
    public function batchLoadSMSHistory(array $criteriaList): array
    {
        if (empty($criteriaList)) {
            return [];
        }

        // Use static caching for results within a request
        static $batchResults = [];
        
        // Create cache keys for each criteria set
        $cacheKeys = [];
        foreach ($criteriaList as $criteria) {
            $cacheKeys[] = $this->createCriteriaHash($criteria);
        }
        
        // Check if all requested criteria are already cached
        $allAlreadyCached = true;
        foreach ($cacheKeys as $cacheKey) {
            if (!isset($batchResults[$cacheKey])) {
                $allAlreadyCached = false;
                break;
            }
        }
        
        if ($allAlreadyCached) {
            $this->logger->info('CACHE HIT: All ' . count($criteriaList) . ' SMS history criteria already batched and cached');
            $results = [];
            foreach ($cacheKeys as $cacheKey) {
                $results[] = $batchResults[$cacheKey];
            }
            return $results;
        }

        // Log batch optimization metrics
        $this->logger->info('BATCH OPTIMIZATION: Processing ' . count($criteriaList) . ' SMS history criteria in a batch');

        // Merge all criteria for efficient querying
        $mergedCriteria = $this->mergeCriteria($criteriaList);
        
        // Apply security filter if user ID is set
        if ($this->userId !== null) {
            // Only apply userId if not explicitly set in criteria
            // This ensures admin users can still view other users' SMS histories
            $hasUserIdFilter = false;
            foreach ($criteriaList as $criteria) {
                if (isset($criteria['userId'])) {
                    $hasUserIdFilter = true;
                    break;
                }
            }
            
            if (!$hasUserIdFilter) {
                $mergedCriteria['userId'] = $this->userId;
            }
        }

        // Execute the optimized batch query
        try {
            // Find SMS history records with merged criteria
            $allHistoryRecords = $this->smsHistoryRepository->findByCriteria($mergedCriteria);
            $this->logger->info('Fetched ' . count($allHistoryRecords) . ' SMS history records from database in a single query');

            // Format and organize results for each original criteria
            $results = $this->organizeResultsByCriteria($criteriaList, $allHistoryRecords);
            
            // Cache results for future lookups within this request
            foreach ($criteriaList as $index => $criteria) {
                $cacheKey = $this->createCriteriaHash($criteria);
                $batchResults[$cacheKey] = $results[$index];
            }
            
            return $results;
            
        } catch (\Exception $e) {
            $this->logger->error('Error in SMSHistoryDataLoader::batchLoadSMSHistory: ' . $e->getMessage(), [
                'exception' => $e,
                'criteriaCount' => count($criteriaList)
            ]);
            
            // Return empty arrays in case of error
            return array_fill(0, count($criteriaList), []);
        }
    }

    /**
     * Create a unique hash for a criteria array for caching
     * 
     * @param array $criteria The criteria array
     * @return string The hash
     */
    private function createCriteriaHash(array $criteria): string
    {
        // Sort criteria by key to ensure consistent hash regardless of array order
        ksort($criteria);
        return md5(json_encode($criteria));
    }

    /**
     * Merge multiple criteria into a single optimized criteria for batch querying
     * 
     * @param array $criteriaList List of criteria arrays
     * @return array The merged criteria
     */
    private function mergeCriteria(array $criteriaList): array
    {
        $mergedCriteria = [];
        
        // Find common criteria values that can be merged
        $userIds = [];
        $statuses = [];
        $segmentIds = [];
        $searchTerms = [];
        
        foreach ($criteriaList as $criteria) {
            // Collect all possible values for each field
            if (isset($criteria['userId'])) {
                $userIds[] = $criteria['userId'];
            }
            
            if (isset($criteria['status'])) {
                $statuses[] = $criteria['status'];
            }
            
            if (isset($criteria['segmentId'])) {
                $segmentIds[] = $criteria['segmentId'];
            }
            
            if (isset($criteria['search'])) {
                $searchTerms[] = $criteria['search'];
            }
        }
        
        // Deduplicate collected values
        $userIds = array_unique($userIds);
        $statuses = array_unique($statuses);
        $segmentIds = array_unique($segmentIds);
        $searchTerms = array_unique($searchTerms);
        
        // Build the merged criteria based on collected values
        // For optimal querying, we'll use IN conditions when there are multiple values
        if (count($userIds) === 1) {
            $mergedCriteria['userId'] = $userIds[0];
        } elseif (count($userIds) > 1) {
            $mergedCriteria['userIds'] = $userIds;
        }
        
        if (count($statuses) === 1) {
            $mergedCriteria['status'] = $statuses[0];
        } elseif (count($statuses) > 1) {
            $mergedCriteria['statuses'] = $statuses;
        }
        
        if (count($segmentIds) === 1) {
            $mergedCriteria['segmentId'] = $segmentIds[0];
        } elseif (count($segmentIds) > 1) {
            $mergedCriteria['segmentIds'] = $segmentIds;
        }
        
        // Search terms are trickier to merge since they're used in LIKE conditions
        // For simplicity, we'll use the first search term if there's only one
        if (count($searchTerms) === 1) {
            $mergedCriteria['search'] = $searchTerms[0];
        }
        
        return $mergedCriteria;
    }

    /**
     * Organize results from batch query back into individual results for each criteria
     * 
     * @param array $criteriaList Original list of criteria
     * @param array $allHistoryRecords All history records from batch query
     * @return array Array of results for each criteria
     */
    private function organizeResultsByCriteria(array $criteriaList, array $allHistoryRecords): array
    {
        $results = [];
        
        foreach ($criteriaList as $criteria) {
            $matchingRecords = [];
            
            // Filter all records to find those matching this specific criteria
            foreach ($allHistoryRecords as $record) {
                if ($this->recordMatchesCriteria($record, $criteria)) {
                    $matchingRecords[] = $this->formatter->formatSmsHistory($record);
                }
            }
            
            $results[] = $matchingRecords;
        }
        
        return $results;
    }

    /**
     * Check if a record matches specific criteria
     * 
     * @param object $record The SMS history record
     * @param array $criteria The criteria to match
     * @return bool Whether the record matches the criteria
     */
    private function recordMatchesCriteria(object $record, array $criteria): bool
    {
        // Match userId if provided
        if (isset($criteria['userId']) && $record->getUserId() != $criteria['userId']) {
            return false;
        }
        
        // Match status if provided
        if (isset($criteria['status']) && $record->getStatus() != $criteria['status']) {
            return false;
        }
        
        // Match segmentId if provided
        if (isset($criteria['segmentId']) && $record->getSegmentId() != $criteria['segmentId']) {
            return false;
        }
        
        // Match search term (phone number contains) if provided
        if (isset($criteria['search'])) {
            $phoneNumber = $record->getPhoneNumber();
            if (strpos($phoneNumber, $criteria['search']) === false) {
                return false;
            }
        }
        
        // If we get here, the record matches all criteria
        return true;
    }
}