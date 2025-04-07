<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Represents an Admin Contact entity.
 * These are contacts managed specifically by the administrator, potentially separate
 * from the main phone_numbers table used by regular users.
 */
class AdminContact
{
    private ?int $id;
    private ?int $segmentId; // Optional link to a custom segment
    private string $phoneNumber;
    private ?string $name;
    private ?string $createdAt;
    private ?string $updatedAt;

    // Optional: Store related CustomSegment object if loaded
    private ?CustomSegment $segment = null;

    public function __construct(
        ?int $id = null,
        string $phoneNumber,
        ?string $name = null,
        ?int $segmentId = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->phoneNumber = $this->normalizePhoneNumber($phoneNumber); // Normalize on creation
        $this->name = $name;
        $this->segmentId = $segmentId;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
        $this->updatedAt = $updatedAt;
    }

    // --- Getters ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getSegmentId(): ?int
    {
        return $this->segmentId;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function getSegment(): ?CustomSegment
    {
        return $this->segment;
    }

    // --- Setters ---

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function setPhoneNumber(string $phoneNumber): void
    {
        $this->phoneNumber = $this->normalizePhoneNumber($phoneNumber);
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function setSegmentId(?int $segmentId): void
    {
        $this->segmentId = $segmentId;
        // Reset loaded segment if ID changes
        if ($this->segment !== null && $this->segment->getId() !== $segmentId) {
            $this->segment = null;
        }
    }

    public function setSegment(?CustomSegment $segment): void
    {
        $this->segment = $segment;
        $this->segmentId = $segment ? $segment->getId() : null;
    }

    /**
     * Normalize phone number to a standard format (e.g., +225XXXXXXXXXX).
     * Adapts the logic from PhoneNumber model or SMSRepository.
     */
    private function normalizePhoneNumber(string $number): string
    {
        // Remove non-numeric characters except '+'
        $cleaned = preg_replace('/[^\d+]/', '', $number);

        // Remove leading '+' if present for initial check
        $checkNumber = ltrim($cleaned, '+');

        // Check if it starts with Ivory Coast code
        if (strpos($checkNumber, '225') === 0) {
            // Already has country code, ensure '+' prefix
            if (strpos($cleaned, '+') !== 0) {
                return '+' . $checkNumber;
            }
            return $cleaned; // Already in +225 format
        }

        // Assume local format (8 or 10 digits)
        if (strlen($checkNumber) === 10 && substr($checkNumber, 0, 1) === '0') {
            // 10 digits starting with 0 (e.g., 07...)
            return '+225' . $checkNumber;
        } elseif (strlen($checkNumber) === 8) {
            // 8 digits (e.g., 77...) - assume needs 0 prefix for +225 format
            return '+2250' . $checkNumber;
        }

        // If it doesn't match known formats, return the cleaned version or original?
        // Returning cleaned might be safer, but could be incorrect if it's an international number from another country.
        // For now, return cleaned, assuming local context primarily.
        // Consider adding validation or throwing an error for unsupported formats.
        if (strpos($cleaned, '+') !== 0 && !empty($cleaned)) {
            // Attempt to prefix if it looks like a number but lacks '+'
            // This is a guess and might be wrong for non-CI numbers.
            // return '+' . $cleaned; 
        }

        return $cleaned; // Return cleaned number if format is unknown/unsupported
    }


    /**
     * Create an AdminContact object from a database row.
     */
    public static function fromArray(array $row): self
    {
        return new self(
            isset($row['id']) ? (int)$row['id'] : null,
            $row['phone_number'],
            $row['name'] ?? null,
            isset($row['segment_id']) ? (int)$row['segment_id'] : null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
