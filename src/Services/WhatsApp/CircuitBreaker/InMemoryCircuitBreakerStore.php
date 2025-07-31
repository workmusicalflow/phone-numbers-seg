<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\CircuitBreaker;

/**
 * Stockage en mémoire de l'état du Circuit Breaker
 */
class InMemoryCircuitBreakerStore implements CircuitBreakerStateStore
{
    private array $states = [];
    
    public function getState(string $name): CircuitBreakerState
    {
        if (!isset($this->states[$name])) {
            $this->states[$name] = new CircuitBreakerState();
        }
        
        return $this->states[$name];
    }
    
    public function setState(string $name, CircuitBreakerState $state): void
    {
        $this->states[$name] = $state;
    }
    
    public function clearState(string $name): void
    {
        unset($this->states[$name]);
    }
}