<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when there is an error in repository operations
 */
class RepositoryException extends Exception
{
    /**
     * Constructor
     * 
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = "Repository operation error", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
