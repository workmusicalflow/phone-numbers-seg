<?php

namespace App\Models;

/**
 * PhoneNumber Model
 * 
 * Represents a phone number in the system with business information
 */
class PhoneNumber
{
    /**
     * @var int|null The ID of the phone number
     */
    private ?int $id;

    /**
     * @var string The phone number string
     */
    private string $number;

    /**
     * @var string|null The civility (M., Mme, Mlle) associated with this phone number
     */
    private ?string $civility;

    /**
     * @var string|null The first name associated with this phone number
     */
    private ?string $firstName;

    /**
     * @var string|null The last name associated with this phone number
     */
    private ?string $name;

    /**
     * @var string|null The company associated with this phone number
     */
    private ?string $company;

    /**
     * @var string|null The business sector associated with this phone number
     */
    private ?string $sector;

    /**
     * @var string|null Additional notes about this phone number
     */
    private ?string $notes;

    /**
     * @var string The date the phone number was added
     */
    private string $dateAdded;

    /**
     * @var array The technical segments associated with this phone number
     */
    private array $technicalSegments = [];

    /**
     * @var array The custom segments associated with this phone number
     */
    private array $customSegments = [];

    /**
     * Constructor
     * 
     * @param string $number The phone number
     * @param int|null $id The ID (null for new records)
     * @param string|null $civility The civility (M., Mme, Mlle) associated with this phone number
     * @param string|null $firstName The first name associated with this phone number
     * @param string|null $name The last name associated with this phone number
     * @param string|null $company The company associated with this phone number
     * @param string|null $sector The business sector associated with this phone number
     * @param string|null $notes Additional notes about this phone number
     * @param string|null $dateAdded The date added (null for current timestamp)
     */
    public function __construct(
        string $number,
        ?int $id = null,
        ?string $civility = null,
        ?string $firstName = null,
        ?string $name = null,
        ?string $company = null,
        ?string $sector = null,
        ?string $notes = null,
        ?string $dateAdded = null
    ) {
        $this->number = $this->normalizeNumber($number);
        $this->id = $id;
        $this->civility = $civility;
        $this->firstName = $firstName;
        $this->name = $name;
        $this->company = $company;
        $this->sector = $sector;
        $this->notes = $notes;
        $this->dateAdded = $dateAdded ?? date('Y-m-d H:i:s');
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
     * Get the phone number
     * 
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * Set the phone number
     * 
     * @param string $number
     * @return self
     */
    public function setNumber(string $number): self
    {
        $this->number = $this->normalizeNumber($number);
        return $this;
    }

    /**
     * Get the date added
     * 
     * @return string
     */
    public function getDateAdded(): string
    {
        return $this->dateAdded;
    }

    /**
     * Get the civility
     * 
     * @return string|null
     */
    public function getCivility(): ?string
    {
        return $this->civility;
    }

    /**
     * Set the civility
     * 
     * @param string|null $civility
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
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * Set the first name
     * 
     * @param string|null $firstName
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
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the last name
     * 
     * @param string|null $name
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
     * @return string|null
     */
    public function getCompany(): ?string
    {
        return $this->company;
    }

    /**
     * Set the company
     * 
     * @param string|null $company
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
     * @return string|null
     */
    public function getSector(): ?string
    {
        return $this->sector;
    }

    /**
     * Set the sector
     * 
     * @param string|null $sector
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
     * @return string|null
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * Set the notes
     * 
     * @param string|null $notes
     * @return self
     */
    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    /**
     * Get the technical segments
     * 
     * @return array
     */
    public function getTechnicalSegments(): array
    {
        return $this->technicalSegments;
    }

    /**
     * Set the technical segments
     * 
     * @param array $segments
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
     * @param Segment $segment
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
     * @return array
     */
    public function getCustomSegments(): array
    {
        return $this->customSegments;
    }

    /**
     * Set the custom segments
     * 
     * @param array $segments
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
     * @param CustomSegment $segment
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
            'dateAdded' => $this->dateAdded,
            'technicalSegments' => array_map(function ($segment) {
                return $segment->toArray();
            }, $this->technicalSegments),
            'customSegments' => array_map(function ($segment) {
                return $segment instanceof \stdClass ? $segment : $segment->toArray();
            }, $this->customSegments)
        ];
    }
}
