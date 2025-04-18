<?php

namespace App\Entities;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;

/**
 * PhoneNumber entity
 * 
 * Represents a phone number in the system with business information
 */
#[Entity]
#[Table(name: "phone_numbers")]
class PhoneNumber
{
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue]
    private ?int $id = null;

    #[Column(type: "string", length: 20)]
    private string $number;

    #[Column(type: "string", length: 10, nullable: true)]
    private ?string $civility = null;

    #[Column(type: "string", length: 100, nullable: true)]
    private ?string $firstName = null;

    #[Column(type: "string", length: 100, nullable: true)]
    private ?string $name = null;

    #[Column(type: "string", length: 255, nullable: true)]
    private ?string $company = null;

    #[Column(type: "string", length: 100, nullable: true)]
    private ?string $sector = null;

    #[Column(type: "text", nullable: true)]
    private ?string $notes = null;

    #[Column(name: "date_added", type: "datetime")]
    private \DateTime $dateAdded;

    /**
     * Technical segments associated with this phone number
     * This is a transient property, not stored in the database
     */
    private array $technicalSegments = [];

    /**
     * Custom segments associated with this phone number
     * This is a transient property, not stored in the database
     */
    private array $customSegments = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dateAdded = new \DateTime();
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
     * Get the phone number
     * 
     * @return string The phone number
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * Set the phone number
     * 
     * @param string $number The phone number
     * @return self
     */
    public function setNumber(string $number): self
    {
        $this->number = $this->normalizeNumber($number);
        return $this;
    }

    /**
     * Get the civility
     * 
     * @return string|null The civility
     */
    public function getCivility(): ?string
    {
        return $this->civility;
    }

    /**
     * Set the civility
     * 
     * @param string|null $civility The civility
     * @return self
     */
    public function setCivility(?string $civility): self
    {
        $this->civility = $civility;
        return $this;
    }

    /**
     * Get the first name
     * 
     * @return string|null The first name
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * Set the first name
     * 
     * @param string|null $firstName The first name
     * @return self
     */
    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    /**
     * Get the last name
     * 
     * @return string|null The last name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the last name
     * 
     * @param string|null $name The last name
     * @return self
     */
    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the company
     * 
     * @return string|null The company
     */
    public function getCompany(): ?string
    {
        return $this->company;
    }

    /**
     * Set the company
     * 
     * @param string|null $company The company
     * @return self
     */
    public function setCompany(?string $company): self
    {
        $this->company = $company;
        return $this;
    }

    /**
     * Get the sector
     * 
     * @return string|null The sector
     */
    public function getSector(): ?string
    {
        return $this->sector;
    }

    /**
     * Set the sector
     * 
     * @param string|null $sector The sector
     * @return self
     */
    public function setSector(?string $sector): self
    {
        $this->sector = $sector;
        return $this;
    }

    /**
     * Get the notes
     * 
     * @return string|null The notes
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * Set the notes
     * 
     * @param string|null $notes The notes
     * @return self
     */
    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    /**
     * Get the date added
     * 
     * @return \DateTime The date added
     */
    public function getDateAdded(): \DateTime
    {
        return $this->dateAdded;
    }

    /**
     * Set the date added
     * 
     * @param \DateTime $dateAdded The date added
     * @return self
     */
    public function setDateAdded(\DateTime $dateAdded): self
    {
        $this->dateAdded = $dateAdded;
        return $this;
    }

    /**
     * Get the technical segments
     * 
     * @return array The technical segments
     */
    public function getTechnicalSegments(): array
    {
        return $this->technicalSegments;
    }

    /**
     * Set the technical segments
     * 
     * @param array $segments The technical segments
     * @return self
     */
    public function setTechnicalSegments(array $segments): self
    {
        $this->technicalSegments = $segments;
        return $this;
    }

    /**
     * Add a technical segment
     * 
     * @param Segment $segment The segment to add
     * @return self
     */
    public function addTechnicalSegment(Segment $segment): self
    {
        $this->technicalSegments[] = $segment;
        return $this;
    }

    /**
     * Get the custom segments
     * 
     * @return array The custom segments
     */
    public function getCustomSegments(): array
    {
        return $this->customSegments;
    }

    /**
     * Set the custom segments
     * 
     * @param array $segments The custom segments
     * @return self
     */
    public function setCustomSegments(array $segments): self
    {
        $this->customSegments = $segments;
        return $this;
    }

    /**
     * Add a custom segment
     * 
     * @param CustomSegment $segment The segment to add
     * @return self
     */
    public function addCustomSegment($segment): self
    {
        $this->customSegments[] = $segment;
        return $this;
    }

    /**
     * For backward compatibility
     * 
     * @return array
     */
    public function getSegments(): array
    {
        return $this->technicalSegments;
    }

    /**
     * For backward compatibility
     * 
     * @param array $segments
     * @return self
     */
    public function setSegments(array $segments): self
    {
        $this->technicalSegments = $segments;
        return $this;
    }

    /**
     * For backward compatibility
     * 
     * @param Segment $segment
     * @return self
     */
    public function addSegment(Segment $segment): self
    {
        $this->technicalSegments[] = $segment;
        return $this;
    }

    /**
     * Normalize a phone number to a standard format
     * 
     * @param string $number
     * @return string
     */
    private function normalizeNumber(string $number): string
    {
        // Remove any non-numeric characters except the leading +
        $number = preg_replace('/[^0-9+]/', '', $number);

        // Handle different formats
        if (substr($number, 0, 1) === '+') {
            // Format: +2250777104936 - already in international format with +
            return $number;
        } elseif (substr($number, 0, 4) === '0022') {
            // Format: 002250777104936 - convert to +225...
            return '+225' . substr($number, 5);
        } elseif (substr($number, 0, 1) === '0') {
            // Format: 0777104936 - convert to +225...
            return '+2250' . substr($number, 1);
        }

        // If none of the above, assume it's already normalized or invalid
        return $number;
    }

    /**
     * Validate if the phone number is a valid Côte d'Ivoire number
     * 
     * @return bool
     */
    public function isValid(): bool
    {
        // Check if the number is in the correct format after normalization
        if (!preg_match('/^\+225[0-9]{10}$/', $this->number)) {
            return false;
        }

        return true;
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
            'number' => $this->number,
            'civility' => $this->civility,
            'firstName' => $this->firstName,
            'name' => $this->name,
            'company' => $this->company,
            'sector' => $this->sector,
            'notes' => $this->notes,
            'dateAdded' => $this->dateAdded->format('Y-m-d H:i:s'),
            'technicalSegments' => array_map(function ($segment) {
                return $segment->toArray();
            }, $this->technicalSegments),
            'customSegments' => array_map(function ($segment) {
                return $segment instanceof \stdClass ? $segment : $segment->toArray();
            }, $this->customSegments)
        ];
    }
}
