<?php

namespace App\GraphQL\DataLoaders;

use Psr\Log\LoggerInterface;

/**
 * Base DataLoader class
 * 
 * A DataLoader implementation for batching requests with request-scoped batching.
 */
class DataLoader
{
    /**
     * @var callable
     */
    private $batchLoadFn;

    /**
     * @var array
     */
    private $queue = [];

    /**
     * @var array
     */
    private $cache = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $dispatchInProgress = false;

    /**
     * @var bool
     */
    private $isBatchScheduled = false;

    /**
     * @var array
     */
    private $promises = [];

    /**
     * Constructor
     * 
     * @param callable $batchLoadFn The batch load function
     * @param LoggerInterface $logger Logger
     */
    public function __construct(callable $batchLoadFn, LoggerInterface $logger)
    {
        $this->batchLoadFn = $batchLoadFn;
        $this->logger = $logger;
    }

    /**
     * Load a key
     * 
     * @param mixed $key The key to load
     * @return mixed The value for the key
     */
    public function load($key)
    {
        $cacheKey = $this->getCacheKey($key);
        
        // Return cached value if it exists
        if (isset($this->cache[$cacheKey])) {
            $this->logger->debug("DataLoader: Cache hit for key: $cacheKey");
            return $this->cache[$cacheKey];
        }
        
        // Add key to the queue
        $this->queue[$cacheKey] = $key;
        $this->logger->debug("DataLoader: Queueing key: $cacheKey");
        
        // Check if we already have a promise for this key
        if (isset($this->promises[$cacheKey])) {
            return $this->promises[$cacheKey];
        }

        // Create a promise for this key
        $this->promises[$cacheKey] = [];
        
        // Schedule batch execution if not already scheduled
        $this->scheduleBatch();
        
        // Perform the actual dispatch if we have a good number of items or 
        // if this is likely the last key in this execution cycle
        if (count($this->queue) >= 10 || !$this->areMoreKeysExpected()) {
            $this->dispatchQueue();
        }
        
        // Return the cached value that should now be available
        return $this->cache[$cacheKey] ?? null;
    }

    /**
     * Load multiple keys
     * 
     * @param array $keys The keys to load
     * @return array The values for the keys
     */
    public function loadMany(array $keys)
    {
        $cacheKeys = [];
        $results = [];
        $needsDispatch = false;
        
        foreach ($keys as $key) {
            $cacheKey = $this->getCacheKey($key);
            $cacheKeys[] = $cacheKey;
            
            // Return cached value if it exists
            if (isset($this->cache[$cacheKey])) {
                $this->logger->debug("DataLoader: Cache hit for key: $cacheKey");
                $results[$cacheKey] = $this->cache[$cacheKey];
                continue;
            }
            
            // Add key to the queue
            $this->queue[$cacheKey] = $key;
            $this->logger->debug("DataLoader: Queueing key: $cacheKey");
            $needsDispatch = true;
            
            // Create a promise for this key if it doesn't exist
            if (!isset($this->promises[$cacheKey])) {
                $this->promises[$cacheKey] = [];
            }
        }
        
        // Schedule batch execution if not already scheduled
        if ($needsDispatch) {
            $this->scheduleBatch();
            
            // Force dispatch since we're explicitly asking for these values now
            $this->dispatchQueue();
        }
        
        // Return the results in the same order as the keys
        $orderedResults = [];
        foreach ($cacheKeys as $cacheKey) {
            $orderedResults[] = $this->cache[$cacheKey] ?? null;
        }
        
        return $orderedResults;
    }

    /**
     * Prime the cache with a value
     * 
     * @param mixed $key The key
     * @param mixed $value The value
     * @return self
     */
    public function prime($key, $value): self
    {
        $cacheKey = $this->getCacheKey($key);
        $this->cache[$cacheKey] = $value;
        $this->logger->debug("DataLoader: Primed cache for key: $cacheKey");
        return $this;
    }

    /**
     * Clear cache
     * 
     * @return self
     */
    public function clearCache(): self
    {
        $this->cache = [];
        $this->logger->debug("DataLoader: Cache cleared");
        return $this;
    }

    /**
     * Get the cache
     * 
     * @return array The cache
     */
    public function getCache(): array
    {
        return $this->cache;
    }

    /**
     * Get cache key
     * 
     * @param mixed $key The key
     * @return string The cache key
     */
    private function getCacheKey($key): string
    {
        if (is_object($key)) {
            return spl_object_hash($key);
        }
        
        if (is_array($key)) {
            return 'array_' . md5(serialize($key));
        }
        
        return (string) $key;
    }

    /**
     * Schedule a batch execution at the end of the current execution cycle
     * 
     * @return void
     */
    private function scheduleBatch(): void
    {
        if ($this->isBatchScheduled) {
            return;
        }
        
        $this->isBatchScheduled = true;
        
        // In a more sophisticated setup, we would register a callback to be
        // executed at the end of the current request cycle. However, since
        // PHP doesn't have native async/await or promises, we'll use a flag
        // to track if we've scheduled the batch and rely on explicit dispatch calls.
    }
    
    /**
     * Determine if more keys are likely to be requested in this execution cycle
     * 
     * This is a heuristic to optimize batch execution timing.
     * 
     * @return bool Whether more keys are expected
     */
    private function areMoreKeysExpected(): bool
    {
        // This is a simple heuristic that can be improved with more context
        // about the typical GraphQL query patterns in your application
        return false; // Conservative approach - dispatch immediately
    }

    /**
     * Dispatch the queue
     * 
     * @return array The results
     */
    public function dispatchQueue(): array
    {
        if (empty($this->queue) || $this->dispatchInProgress) {
            return [];
        }
        
        // Set flag to prevent recursive calls
        $this->dispatchInProgress = true;
        
        try {
            $queue = array_values($this->queue);
            $queueKeys = array_keys($this->queue);
            $this->queue = [];
            $this->isBatchScheduled = false;
            
            $this->logger->debug("DataLoader: Dispatching batch of " . count($queue) . " keys");
            
            // Call the batch load function with the queue
            $results = call_user_func($this->batchLoadFn, $queue);
            
            // Ensure the batch load function returns an array with the same count as the queue
            if (!is_array($results) || count($results) !== count($queue)) {
                $this->logger->error("DataLoader: Batch load function returned invalid results", [
                    'resultsCount' => is_array($results) ? count($results) : 'not array',
                    'queueCount' => count($queue)
                ]);
                throw new \Exception("Batch load function must return an array with the same count as the queue");
            }
            
            // Cache the results
            $mappedResults = [];
            foreach ($queueKeys as $i => $cacheKey) {
                $this->cache[$cacheKey] = $results[$i];
                $mappedResults[$cacheKey] = $results[$i];
                
                // Resolve any promises waiting for this key
                if (isset($this->promises[$cacheKey])) {
                    unset($this->promises[$cacheKey]);
                }
            }
            
            $this->logger->debug("DataLoader: Batch complete, cached " . count($results) . " results");
            
            return $mappedResults;
        } finally {
            // Reset flag
            $this->dispatchInProgress = false;
        }
    }
}