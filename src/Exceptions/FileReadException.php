<?php

namespace App\Exceptions;

/**
 * Exception thrown when a file cannot be read
 */
class FileReadException extends ImportException
{
    /**
     * @var string Path to the file that could not be read
     */
    protected string $filePath;

    /**
     * Constructor
     * 
     * @param string $message Exception message
     * @param string $filePath Path to the file that could not be read
     * @param array $data Additional data about the exception
     * @param int $code Exception code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(string $message, string $filePath, array $data = [], int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $data, $code, $previous);
        $this->filePath = $filePath;
    }

    /**
     * Get the path to the file that could not be read
     * 
     * @return string File path
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }
}
