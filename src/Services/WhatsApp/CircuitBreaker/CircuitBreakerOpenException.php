<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\CircuitBreaker;

/**
 * Exception levée quand le Circuit Breaker est ouvert
 */
class CircuitBreakerOpenException extends \RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}