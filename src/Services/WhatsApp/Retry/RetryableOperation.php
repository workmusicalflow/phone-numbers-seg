<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Retry;

/**
 * Wrapper pour une opération retryable avec contexte
 */
class RetryableOperation
{
    private string $name;
    private callable $operation;
    private array $context;
    
    public function __construct(string $name, callable $operation, array $context = [])
    {
        $this->name = $name;
        $this->operation = $operation;
        $this->context = $context;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getOperation(): callable
    {
        return $this->operation;
    }
    
    public function getContext(): array
    {
        return $this->context;
    }
    
    /**
     * Exécute l'opération
     * 
     * @return mixed
     */
    public function execute()
    {
        return ($this->operation)();
    }
}