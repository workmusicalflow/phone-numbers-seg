<?php

namespace App\Entities;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany; // Add ManyToMany
use Doctrine\ORM\Mapping\JoinTable; // Add JoinTable
use Doctrine\ORM\Mapping\JoinColumn; // Add JoinColumn
use Doctrine\ORM\Mapping\InverseJoinColumn; // Add InverseJoinColumn
use Doctrine\ORM\Mapping\Table;
use Doctrine\Common\Collections\ArrayCollection; // Add ArrayCollection
use Doctrine\Common\Collections\Collection; // Add Collection
use App\Entities\PhoneNumber; // Add PhoneNumber use statement

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
     * @var Collection<int, PhoneNumber> Phone numbers associated with this segment
     */
    #[ManyToMany(targetEntity: PhoneNumber::class, mappedBy: "customSegments")]
    private Collection $phoneNumbers;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->phoneNumbers = new ArrayCollection(); // Initialize collection
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
     * @return Collection<int, PhoneNumber> The phone numbers
     */
    public function getPhoneNumbers(): Collection
    {
        return $this->phoneNumbers;
    }

    /**
    // Setting the entire collection might not be typical, consider removing or adjusting
    // /**
    //  * Set the phone numbers
    //  * 
    //  * @param Collection<int, PhoneNumber> $phoneNumbers The phone numbers
    //  * @return self
    //  */
    // public function setPhoneNumbers(Collection $phoneNumbers): self
    // {
    //     $this->phoneNumbers = $phoneNumbers;
    //     return $this;
    // }

    /**
     * Add a phone number
     * 
     * @param PhoneNumber $phoneNumber The phone number
     * @return self
     */
    public function addPhoneNumber(PhoneNumber $phoneNumber): self
    {
        if (!$this->phoneNumbers->contains($phoneNumber)) {
            $this->phoneNumbers->add($phoneNumber);
            // If the relationship is bidirectional, set the other side:
            // $phoneNumber->addCustomSegment($this); // Requires addCustomSegment method in PhoneNumber
        }
        return $this;
    }

    /**
     * Remove a phone number
     * 
     * @param PhoneNumber $phoneNumber The phone number to remove
     * @return self
     */
    public function removePhoneNumber(PhoneNumber $phoneNumber): self
    {
        if ($this->phoneNumbers->removeElement($phoneNumber)) {
            // If the relationship is bidirectional, remove from the other side:
            // $phoneNumber->removeCustomSegment($this); // Requires removeCustomSegment method in PhoneNumber
        }
        return $this;
    }

    /**
     * Convert the entity to an array
     * 
     * @return array The entity as an array
     */
    public function toArray(): array
    {
        // Note: Including related entities in toArray can lead to performance issues
        // or circular references if not handled carefully. Often better handled by serializers/formatters.
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'pattern' => $this->pattern,
            // 'phoneNumbers' => $this->phoneNumbers->map(fn(PhoneNumber $p) => $p->getId())->toArray() // Example: return only IDs
        ];
    }
}
