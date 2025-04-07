<?php

namespace App\Exceptions;

/**
 * Exception thrown when a phone number is a duplicate
 */
class DuplicatePhoneNumberException extends ImportException
{
    /**
     * @var string The duplicate phone number
     */
    protected string $phoneNumber;

    /**
     * @var int|string|null The index or identifier of the phone number in a batch
     */
    protected $index;

    /**
     * @var bool Whether the number is a duplicate within the current batch or in the database
     */
    protected bool $isDuplicateInBatch;

    /**
     * Constructor
     * 
     * @param string $message Exception message
     * @param string $phoneNumber The duplicate phone number
     * @param bool $isDuplicateInBatch Whether the number is a duplicate within the current batch
     * @param int|string|null $index The index or identifier of the phone number in a batch
     * @param array $data Additional data about the exception
     * @param int $code Exception code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message,
        string $phoneNumber,
        bool $isDuplicateInBatch = true,
        $index = null,
        array $data = [],
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $data, $code, $previous);
        $this->phoneNumber = $phoneNumber;
        $this->isDuplicateInBatch = $isDuplicateInBatch;
        $this->index = $index;
    }

    /**
     * Get the duplicate phone number
     * 
     * @return string The duplicate phone number
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

    /**
     * Check if the number is a duplicate within the current batch
     * 
     * @return bool True if the number is a duplicate within the current batch, false if it's a duplicate in the database
     */
    public function isDuplicateInBatch(): bool
    {
        return $this->isDuplicateInBatch;
    }
}
