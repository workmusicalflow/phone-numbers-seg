<?php

namespace App\Exceptions;

/**
 * Exception thrown during import operations
 */
class ImportException extends \Exception
{
    /**
     * @var array Additional data about the exception
     */
    protected array $data;

    /**
     * Constructor
     * 
     * @param string $message Exception message
     * @param array $data Additional data about the exception
     * @param int $code Exception code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(string $message, array $data = [], int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->data = $data;
    }

    /**
     * Get additional data about the exception
     * 
     * @return array Additional data
     */
    public function getData(): array
    {
        return $this->data;
    }
}
