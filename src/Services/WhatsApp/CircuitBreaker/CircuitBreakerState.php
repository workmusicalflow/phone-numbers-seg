<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\CircuitBreaker;

/**
 * État du Circuit Breaker
 */
class CircuitBreakerState
{
    public string $state = 'closed';
    public int $failureCount = 0;
    public int $halfOpenSuccessCount = 0;
    public ?string $lastFailureTime = null;
    
    public function toArray(): array
    {
        return [
            'state' => $this->state,
            'failureCount' => $this->failureCount,
            'halfOpenSuccessCount' => $this->halfOpenSuccessCount,
            'lastFailureTime' => $this->lastFailureTime,
        ];
    }
    
    public static function fromArray(array $data): self
    {
        $state = new self();
        $state->state = $data['state'] ?? 'closed';
        $state->failureCount = $data['failureCount'] ?? 0;
        $state->halfOpenSuccessCount = $data['halfOpenSuccessCount'] ?? 0;
        $state->lastFailureTime = $data['lastFailureTime'] ?? null;
        
        return $state;
    }
}