<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\CircuitBreaker;

/**
 * Interface pour stocker l'état du Circuit Breaker
 */
interface CircuitBreakerStateStore
{
    public function getState(string $name): CircuitBreakerState;
    
    public function setState(string $name, CircuitBreakerState $state): void;
    
    public function clearState(string $name): void;
}