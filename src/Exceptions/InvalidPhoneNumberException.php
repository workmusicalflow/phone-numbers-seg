<?php

namespace App\Exceptions;

/**
 * Exception thrown when a phone number has an invalid format
 */
class InvalidPhoneNumberException extends ImportException
{
    /**
     * @var string The invalid phone number
     */
    protected string $phoneNumber;

    /**
     * @var int|string|null The index or identifier of the phone number in a batch
     */
    protected $index;

    /**
     * Constructor
     * 
     * @param string $message Exception message
     * @param string $phoneNumber The invalid phone number
     * @param int|string|null $index The index or identifier of the phone number in a batch
     * @param array $data Additional data about the exception
     * @param int $code Exception code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message,
        string $phoneNumber,
        $index = null,
        array $data = [],
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $data, $code, $previous);
        $this->phoneNumber = $phoneNumber;
        $this->index = $index;
    }

    /**
     * Get the invalid phone number
     * 
     * @return string The invalid phone number
     */
    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    /**
     * Get the index or identifier of the phone number in a batch
     * 
     * @return int|string|null The index or identifier
     */
    public function getIndex()
    {
        return $this->index;
    }
}
