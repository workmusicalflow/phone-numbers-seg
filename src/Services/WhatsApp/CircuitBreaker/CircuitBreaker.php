<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\CircuitBreaker;

use DateTime;
use DateInterval;

/**
 * Circuit Breaker pattern implementation pour protéger les appels API
 * 
 * États:
 * - CLOSED: Fonctionnement normal, les requêtes passent
 * - OPEN: Circuit ouvert, toutes les requêtes sont rejetées
 * - HALF_OPEN: Test de récupération, quelques requêtes passent
 */
class CircuitBreaker
{
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';

    private string $name;
    private int $failureThreshold;
    private int $successThreshold;
    private int $timeout;
    private CircuitBreakerStateStore $stateStore;

    public function __construct(
        string $name,
        int $failureThreshold = 5,
        int $successThreshold = 2,
        int $timeout = 60,
        ?CircuitBreakerStateStore $stateStore = null
    ) {
        $this->name = $name;
        $this->failureThreshold = $failureThreshold;
        $this->successThreshold = $successThreshold;
        $this->timeout = $timeout;
        $this->stateStore = $stateStore ?? new InMemoryCircuitBreakerStore();
    }

    /**
     * Exécute une fonction avec protection Circuit Breaker
     * 
     * @template T
     * @param callable(): T $operation
     * @return T
     * @throws CircuitBreakerOpenException
     */
    public function call(callable $operation)
    {
        $state = $this->stateStore->getState($this->name);

        if ($this->isOpen($state)) {
            if ($this->shouldAttemptReset($state)) {
                $state->state = self::STATE_HALF_OPEN;
                $state->halfOpenSuccessCount = 0;
                $this->stateStore->setState($this->name, $state);
            } else {
                throw new CircuitBreakerOpenException(
                    "Circuit breaker '{$this->name}' is OPEN"
                );
            }
        }

        try {
            $result = $operation();
            $this->onSuccess($state);
            return $result;
        } catch (\Throwable $exception) {
            $this->onFailure($state);
            throw $exception;
        }
    }

    private function isOpen(CircuitBreakerState $state): bool
    {
        return $state->state === self::STATE_OPEN;
    }

    private function shouldAttemptReset(CircuitBreakerState $state): bool
    {
        if ($state->lastFailureTime === null) {
            return true;
        }

        $lastFailure = new DateTime($state->lastFailureTime);
        $timeout = new DateInterval("PT{$this->timeout}S");
        $now = new DateTime();

        return $lastFailure->add($timeout) <= $now;
    }

    private function onSuccess(CircuitBreakerState $state): void
    {
        $state->failureCount = 0;

        if ($state->state === self::STATE_HALF_OPEN) {
            $state->halfOpenSuccessCount++;
            
            if ($state->halfOpenSuccessCount >= $this->successThreshold) {
                $state->state = self::STATE_CLOSED;
                $state->halfOpenSuccessCount = 0;
            }
        }

        $this->stateStore->setState($this->name, $state);
    }

    private function onFailure(CircuitBreakerState $state): void
    {
        $state->failureCount++;
        $state->lastFailureTime = (new DateTime())->format('Y-m-d H:i:s');

        if ($state->state === self::STATE_HALF_OPEN) {
            $state->state = self::STATE_OPEN;
            $state->halfOpenSuccessCount = 0;
        } elseif ($state->failureCount >= $this->failureThreshold) {
            $state->state = self::STATE_OPEN;
        }

        $this->stateStore->setState($this->name, $state);
    }

    public function getState(): string
    {
        return $this->stateStore->getState($this->name)->state;
    }

    public function reset(): void
    {
        $state = new CircuitBreakerState();
        $state->state = self::STATE_CLOSED;
        $this->stateStore->setState($this->name, $state);
    }
}