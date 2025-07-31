<?php

namespace App\Entities;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;

/**
 * PhoneNumberSegment entity
 * 
 * Represents a relationship between a phone number and a custom segment
 */
#[Entity]
#[Table(name: "phone_number_segments")]
class PhoneNumberSegment
{
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue]
    private ?int $id = null;

    #[Column(name: "phone_number_id", type: "integer")]
    private int $phoneNumberId;

    #[Column(name: "custom_segment_id", type: "integer")]
    private int $customSegmentId;

    #[Column(type: "datetime")]
    private \DateTime $createdAt;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
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
     * Get the custom segment ID
     * 
     * @return int The custom segment ID
     */
    public function getCustomSegmentId(): int
    {
        return $this->customSegmentId;
    }

    /**
     * Set the custom segment ID
     * 
     * @param int $customSegmentId The custom segment ID
     * @return self
     */
    public function setCustomSegmentId(int $customSegmentId): self
    {
        $this->customSegmentId = $customSegmentId;
        return $this;
    }

    /**
     * Get the creation date
     * 
     * @return \DateTime The creation date
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * Set the creation date
     * 
     * @param \DateTime $createdAt The creation date
     * @return self
     */
    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
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
            'customSegmentId' => $this->customSegmentId,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s')
        ];
    }
}
