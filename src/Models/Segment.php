<?php

namespace App\Models;

/**
 * Segment Model
 * 
 * Represents a technical segment of a phone number (e.g., country code, operator code)
 */
class Segment
{
    /**
     * @var int|null The ID of the segment
     */
    private ?int $id;

    /**
     * @var int The ID of the phone number this segment belongs to
     */
    private int $phoneNumberId;

    /**
     * @var string The type of segment (e.g., country_code, operator_code)
     */
    private string $segmentType;

    /**
     * @var string The value of the segment
     */
    private string $value;

    /**
     * Segment type constants
     */
    public const TYPE_COUNTRY_CODE = 'country_code';
    public const TYPE_OPERATOR_CODE = 'operator_code';
    public const TYPE_SUBSCRIBER_NUMBER = 'subscriber_number';
    public const TYPE_OPERATOR_NAME = 'operator_name';

    /**
     * Constructor
     * 
     * @param string $segmentType The type of segment
     * @param string $value The value of the segment
     * @param int $phoneNumberId The ID of the phone number this segment belongs to
     * @param int|null $id The ID (null for new records)
     */
    public function __construct(string $segmentType, string $value, int $phoneNumberId = 0, ?int $id = null)
    {
        $this->segmentType = $segmentType;
        $this->value = $value;
        $this->phoneNumberId = $phoneNumberId;
        $this->id = $id;
    }

    /**
     * Get the ID
     * 
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the ID
     * 
     * @param int $id
     * @return self
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get the phone number ID
     * 
     * @return int
     */
    public function getPhoneNumberId(): int
    {
        return $this->phoneNumberId;
    }

    /**
     * Set the phone number ID
     * 
     * @param int $phoneNumberId
     * @return self
     */
    public function setPhoneNumberId(int $phoneNumberId): self
    {
        $this->phoneNumberId = $phoneNumberId;
        return $this;
    }

    /**
     * Get the segment type
     * 
     * @return string
     */
    public function getSegmentType(): string
    {
        return $this->segmentType;
    }

    /**
     * Set the segment type
     * 
     * @param string $segmentType
     * @return self
     */
    public function setSegmentType(string $segmentType): self
    {
        $this->segmentType = $segmentType;
        return $this;
    }

    /**
     * Get the value
     * 
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Set the value
     * 
     * @param string $value
     * @return self
     */
    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Convert the object to an array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'phoneNumberId' => $this->phoneNumberId,
            'segmentType' => $this->segmentType,
            'value' => $this->value
        ];
    }
}
