<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when there is an error in batch processing
 */
class BatchProcessingException extends Exception
{
    /**
     * @var array
     */
    private array $errors;

    /**
     * Constructor
     * 
     * @param string $message
     * @param array $errors
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = "Batch processing error", array $errors = [], int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    /**
     * Get the errors that occurred during batch processing
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
