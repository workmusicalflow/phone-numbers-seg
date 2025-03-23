<?php

namespace App\Models;

/**
 * PhoneNumber Model
 * 
 * Represents a phone number in the system
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
     * @var string The date the phone number was added
     */
    private string $dateAdded;

    /**
     * @var array The segments associated with this phone number
     */
    private array $segments = [];

    /**
     * Constructor
     * 
     * @param string $number The phone number
     * @param int|null $id The ID (null for new records)
     * @param string|null $dateAdded The date added (null for current timestamp)
     */
    public function __construct(string $number, ?int $id = null, ?string $dateAdded = null)
    {
        $this->number = $this->normalizeNumber($number);
        $this->id = $id;
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
     * Get the segments
     * 
     * @return array
     */
    public function getSegments(): array
    {
        return $this->segments;
    }

    /**
     * Set the segments
     * 
     * @param array $segments
     * @return self
     */
    public function setSegments(array $segments): self
    {
        $this->segments = $segments;
        return $this;
    }

    /**
     * Add a segment
     * 
     * @param Segment $segment
     * @return self
     */
    public function addSegment(Segment $segment): self
    {
        $this->segments[] = $segment;
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
            'dateAdded' => $this->dateAdded,
            'segments' => array_map(function ($segment) {
                return $segment->toArray();
            }, $this->segments)
        ];
    }
}
