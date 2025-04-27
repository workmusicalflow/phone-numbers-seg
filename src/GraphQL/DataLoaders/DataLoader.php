<?php

namespace App\GraphQL\DataLoaders;

/**
 * Base DataLoader class
 * 
 * A simple DataLoader implementation for batching requests.
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
     * Constructor
     * 
     * @param callable $batchLoadFn The batch load function
     */
    public function __construct(callable $batchLoadFn)
    {
        $this->batchLoadFn = $batchLoadFn;
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
            return $this->cache[$cacheKey];
        }
        
        // Add key to the queue
        $this->queue[$cacheKey] = $key;
        
        // Dispatch the queue on next tick
        $result = $this->dispatchQueue();
        
        // Return the result for the requested key
        return $result[$cacheKey] ?? null;
    }

    /**
     * Load multiple keys
     * 
     * @param array $keys The keys to load
     * @return array The values for the keys
     */
    public function loadMany(array $keys)
    {
        $results = [];
        
        foreach ($keys as $key) {
            $cacheKey = $this->getCacheKey($key);
            
            // Return cached value if it exists
            if (isset($this->cache[$cacheKey])) {
                $results[$cacheKey] = $this->cache[$cacheKey];
                continue;
            }
            
            // Add key to the queue
            $this->queue[$cacheKey] = $key;
        }
        
        if (!empty($this->queue)) {
            // Dispatch the queue on next tick
            $dispatchResults = $this->dispatchQueue();
            
            // Merge with existing results
            $results = array_merge($results, $dispatchResults);
        }
        
        // Return the results in the same order as the keys
        $orderedResults = [];
        foreach ($keys as $key) {
            $cacheKey = $this->getCacheKey($key);
            $orderedResults[] = $results[$cacheKey] ?? null;
        }
        
        return $orderedResults;
    }

    /**
     * Clear cache
     * 
     * @return void
     */
    public function clearCache()
    {
        $this->cache = [];
    }

    /**
     * Get cache key
     * 
     * @param mixed $key The key
     * @return string The cache key
     */
    private function getCacheKey($key)
    {
        if (is_object($key)) {
            return spl_object_hash($key);
        }
        
        return (string) $key;
    }

    /**
     * Dispatch the queue
     * 
     * @return array The results
     */
    private function dispatchQueue()
    {
        if (empty($this->queue)) {
            return [];
        }
        
        $queue = array_values($this->queue);
        $queueKeys = array_keys($this->queue);
        $this->queue = [];
        
        // Call the batch load function with the queue
        $results = call_user_func($this->batchLoadFn, $queue);
        
        // Ensure the batch load function returns an array with the same count as the queue
        if (!is_array($results) || count($results) !== count($queue)) {
            throw new \Exception("Batch load function must return an array with the same count as the queue");
        }
        
        // Cache the results
        $mappedResults = [];
        foreach ($queueKeys as $i => $cacheKey) {
            $this->cache[$cacheKey] = $results[$i];
            $mappedResults[$cacheKey] = $results[$i];
        }
        
        return $mappedResults;
    }
}