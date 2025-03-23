<?php

namespace App\Models;

/**
 * CustomSegment Model
 * 
 * Represents a custom business segment for phone numbers
 */
class CustomSegment
{
    /**
     * @var int|null The ID of the segment
     */
    private ?int $id;

    /**
     * @var string The name of the segment
     */
    private string $name;

    /**
     * @var string|null The description of the segment
     */
    private ?string $description;

    /**
     * @var array Phone numbers associated with this segment
     */
    private array $phoneNumbers = [];

    /**
     * Constructor
     * 
     * @param string $name The name of the segment
     * @param string|null $description The description of the segment
     * @param int|null $id The ID (null for new records)
     */
    public function __construct(string $name, ?string $description = null, ?int $id = null)
    {
        $this->name = $name;
        $this->description = $description;
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
     * Get the name
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name
     * 
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the description
     * 
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set the description
     * 
     * @param string|null $description
     * @return self
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get the phone numbers
     * 
     * @return array
     */
    public function getPhoneNumbers(): array
    {
        return $this->phoneNumbers;
    }

    /**
     * Set the phone numbers
     * 
     * @param array $phoneNumbers
     * @return self
     */
    public function setPhoneNumbers(array $phoneNumbers): self
    {
        $this->phoneNumbers = $phoneNumbers;
        return $this;
    }

    /**
     * Add a phone number
     * 
     * @param PhoneNumber $phoneNumber
     * @return self
     */
    public function addPhoneNumber(PhoneNumber $phoneNumber): self
    {
        $this->phoneNumbers[] = $phoneNumber;
        return $this;
    }

    /**
     * Convert the object to an array
     * 
     * @param bool $includePhoneNumbers Whether to include phone numbers in the array
     * @return array
     */
    public function toArray(bool $includePhoneNumbers = false): array
    {
        $array = [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description
        ];

        if ($includePhoneNumbers) {
            $array['phoneNumbers'] = array_map(function ($phoneNumber) {
                return $phoneNumber->toArray();
            }, $this->phoneNumbers);
        }

        return $array;
    }
}
