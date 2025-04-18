<?php

namespace App\Entities;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 * Segment entity
 * 
 * This entity represents a technical segment of a phone number (e.g., country code, operator code)
 */
#[Entity(repositoryClass: "App\Repositories\Doctrine\SegmentRepository")]
#[Table(name: "technical_segments")]
class Segment
{
    /**
     * Segment type constants
     */
    public const TYPE_COUNTRY_CODE = 'country_code';
    public const TYPE_OPERATOR_CODE = 'operator_code';
    public const TYPE_SUBSCRIBER_NUMBER = 'subscriber_number';
    public const TYPE_OPERATOR_NAME = 'operator_name';

    #[Id]
    #[GeneratedValue]
    #[Column(type: "integer")]
    private ?int $id = null;

    #[Column(name: "phone_number_id", type: "integer")]
    private int $phoneNumberId;

    #[Column(name: "segment_type", type: "string", length: 50)]
    private string $segmentType;

    #[Column(type: "string", length: 255)]
    private string $value;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Default constructor
    }

    /**
     * Get the ID
     * 
     * @return int|null The ID
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the ID
     * 
     * @param int|null $id The ID
     * @return self
     */
    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get the phone number ID
     * 
     * @return int The phone number ID
     */
    public function getPhoneNumberId(): int
    {
        return $this->phoneNumberId;
    }

    /**
     * Set the phone number ID
     * 
     * @param int $phoneNumberId The phone number ID
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
     * @return string The segment type
     */
    public function getSegmentType(): string
    {
        return $this->segmentType;
    }

    /**
     * Set the segment type
     * 
     * @param string $segmentType The segment type
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
     * @return string The value
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Set the value
     * 
     * @param string $value The value
     * @return self
     */
    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Convert the entity to an array
     * 
     * @return array The entity as an array
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
