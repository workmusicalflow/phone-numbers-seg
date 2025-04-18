<?php

namespace App\Entities;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 * CustomSegment entity
 * 
 * This entity represents a custom business segment for phone numbers
 */
#[Entity(repositoryClass: "App\Repositories\Doctrine\CustomSegmentRepository")]
#[Table(name: "custom_segments")]
class CustomSegment
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: "integer")]
    private ?int $id = null;

    #[Column(type: "string", length: 255)]
    private string $name;

    #[Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[Column(type: "text", nullable: true)]
    private ?string $pattern = null;

    /**
     * @var array Phone numbers associated with this segment (not persisted)
     */
    private array $phoneNumbers = [];

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
     * Get the name
     * 
     * @return string The name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name
     * 
     * @param string $name The name
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
     * @return string|null The description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set the description
     * 
     * @param string|null $description The description
     * @return self
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get the pattern
     * 
     * @return string|null The pattern
     */
    public function getPattern(): ?string
    {
        return $this->pattern;
    }

    /**
     * Set the pattern
     * 
     * @param string|null $pattern The pattern
     * @return self
     */
    public function setPattern(?string $pattern): self
    {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * Get the phone numbers
     * 
     * @return array The phone numbers
     */
    public function getPhoneNumbers(): array
    {
        return $this->phoneNumbers;
    }

    /**
     * Set the phone numbers
     * 
     * @param array $phoneNumbers The phone numbers
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
     * @param object $phoneNumber The phone number
     * @return self
     */
    public function addPhoneNumber(object $phoneNumber): self
    {
        $this->phoneNumbers[] = $phoneNumber;
        return $this;
    }

    /**
     * Convert the entity to an array
     * 
     * @param bool $includePhoneNumbers Whether to include phone numbers in the array
     * @return array The entity as an array
     */
    public function toArray(bool $includePhoneNumbers = false): array
    {
        $array = [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'pattern' => $this->pattern
        ];

        if ($includePhoneNumbers) {
            $array['phoneNumbers'] = array_map(function ($phoneNumber) {
                return method_exists($phoneNumber, 'toArray') ? $phoneNumber->toArray() : $phoneNumber;
            }, $this->phoneNumbers);
        }

        return $array;
    }
}
