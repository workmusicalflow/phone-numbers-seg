<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Retry;

/**
 * Politique de retry avec backoff exponentiel
 */
class RetryPolicy
{
    private int $maxAttempts;
    private int $baseDelayMs;
    private float $multiplier;
    private int $maxDelayMs;
    private array $retryableExceptions;
    private $onRetry;
    
    public function __construct(
        int $maxAttempts = 3,
        int $baseDelayMs = 1000,
        float $multiplier = 2.0,
        int $maxDelayMs = 30000,
        array $retryableExceptions = [],
        ?callable $onRetry = null
    ) {
        $this->maxAttempts = $maxAttempts;
        $this->baseDelayMs = $baseDelayMs;
        $this->multiplier = $multiplier;
        $this->maxDelayMs = $maxDelayMs;
        $this->retryableExceptions = $retryableExceptions ?: [
            \RuntimeException::class,
            \GuzzleHttp\Exception\ConnectException::class,
            \GuzzleHttp\Exception\ServerException::class,
        ];
        $this->onRetry = $onRetry;
    }
    
    /**
     * Exécute une opération avec retry
     * 
     * @template T
     * @param callable(): T $operation
     * @return T
     * @throws \Throwable
     */
    public function execute(callable $operation)
    {
        $lastException = null;
        
        for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
            try {
                return $operation();
            } catch (\Throwable $exception) {
                $lastException = $exception;
                
                if (!$this->shouldRetry($exception, $attempt)) {
                    throw $exception;
                }
                
                $delay = $this->calculateDelay($attempt);
                
                if ($this->onRetry !== null) {
                    ($this->onRetry)($exception, $attempt, $delay);
                }
                
                usleep($delay * 1000);
            }
        }
        
        throw $lastException;
    }
    
    private function shouldRetry(\Throwable $exception, int $attempt): bool
    {
        if ($attempt >= $this->maxAttempts) {
            return false;
        }
        
        foreach ($this->retryableExceptions as $retryableClass) {
            if ($exception instanceof $retryableClass) {
                return true;
            }
        }
        
        // Vérifier les codes de statut HTTP pour les exceptions Guzzle
        if (method_exists($exception, 'getResponse') && method_exists($exception, 'getCode')) {
            $code = $exception->getCode();
            // Retry sur erreurs 5xx et certaines erreurs 4xx
            return in_array($code, [429, 502, 503, 504]);
        }
        
        return false;
    }
    
    private function calculateDelay(int $attempt): int
    {
        $delay = (int) ($this->baseDelayMs * pow($this->multiplier, $attempt - 1));
        
        // Ajouter du jitter pour éviter thundering herd
        $jitterRange = (int) ($delay / 4);
        $jitter = rand(-$jitterRange, $jitterRange);
        $delay += $jitter;
        
        return min($delay, $this->maxDelayMs);
    }
}