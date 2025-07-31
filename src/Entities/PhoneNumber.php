<?php

namespace App\Entities;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\OneToMany; // Add OneToMany
use Doctrine\ORM\Mapping\ManyToMany; // Add ManyToMany
use Doctrine\ORM\Mapping\JoinTable; // Add JoinTable
use Doctrine\ORM\Mapping\JoinColumn; // Add JoinColumn
use Doctrine\ORM\Mapping\InverseJoinColumn; // Add InverseJoinColumn
use Doctrine\Common\Collections\ArrayCollection; // Add ArrayCollection
use Doctrine\Common\Collections\Collection; // Add Collection
use App\Entities\Segment; // Add Segment use statement
use App\Entities\CustomSegment; // Add CustomSegment use statement

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
     * @var Collection<int, Segment> Technical segments associated with this phone number
     */
    #[OneToMany(targetEntity: Segment::class, mappedBy: "phoneNumber", cascade: ["persist", "remove"], orphanRemoval: true)]
    private Collection $technicalSegments;

    /**
     * Custom segments associated with this phone number
     * @var Collection<int, CustomSegment> Custom segments associated with this phone number
     */
    #[ManyToMany(targetEntity: CustomSegment::class, inversedBy: "phoneNumbers")]
    #[JoinTable(name: "phone_number_custom_segment")]
    #[JoinColumn(name: "phone_number_id", referencedColumnName: "id", onDelete: "CASCADE")]
    #[InverseJoinColumn(name: "custom_segment_id", referencedColumnName: "id", onDelete: "CASCADE")]
    private Collection $customSegments;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dateAdded = new \DateTime();
        $this->technicalSegments = new ArrayCollection(); // Initialize collection
        $this->customSegments = new ArrayCollection(); // Initialize collection
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
     * @return Collection<int, Segment> The technical segments
     */
    public function getTechnicalSegments(): Collection
    {
        return $this->technicalSegments;
    }

    /**
    // Setting the entire collection might not be typical, consider removing or adjusting
    // /**
    //  * Set the technical segments
    //  * 
    //  * @param Collection<int, Segment> $segments The technical segments
    //  * @return self
    //  */
    // public function setTechnicalSegments(Collection $segments): self
    // {
    //     // Clear existing segments and add new ones, managing the inverse side
    //     $this->technicalSegments->clear();
    //     foreach ($segments as $segment) {
    //         $this->addTechnicalSegment($segment);
    //     }
    //     return $this;
    // }

    /**
     * Add a technical segment
     * 
     * @param Segment $segment The segment to add
     * @return self
     */
    public function addTechnicalSegment(Segment $segment): self
    {
        if (!$this->technicalSegments->contains($segment)) {
            $this->technicalSegments->add($segment);
            $segment->setPhoneNumber($this); // Set the inverse side
        }
        return $this;
    }

    /**
     * Remove a technical segment
     * 
     * @param Segment $segment The segment to remove
     * @return self
     */
    public function removeTechnicalSegment(Segment $segment): self
    {
        if ($this->technicalSegments->removeElement($segment)) {
            // If orphanRemoval=true, setting the inverse side to null is important
            // if ($segment->getPhoneNumber() === $this) {
            //     $segment->setPhoneNumber(null);
            // }
            // Note: With orphanRemoval=true, Doctrine handles removal, setting null might not be needed
            // depending on exact cascade/fetch configurations. Test carefully.
        }
        return $this;
    }

    /**
     * Get the custom segments
     * 
     * @return Collection<int, CustomSegment> The custom segments
     */
    public function getCustomSegments(): Collection
    {
        return $this->customSegments;
    }

    /**
    // Setting the entire collection might not be typical, consider removing or adjusting
    // /**
    //  * Set the custom segments
    //  * 
    //  * @param Collection<int, CustomSegment> $segments The custom segments
    //  * @return self
    //  */
    // public function setCustomSegments(Collection $segments): self
    // {
    //     // Clear existing and add new, managing both sides if bidirectional
    //     $this->customSegments->clear();
    //     foreach ($segments as $segment) {
    //         $this->addCustomSegment($segment);
    //     }
    //     return $this;
    // }

    /**
     * @param CustomSegment $segment The segment to add
     * @return self
     */
    public function addCustomSegment(CustomSegment $segment): self
    {
        if (!$this->customSegments->contains($segment)) {
            $this->customSegments->add($segment);
            $segment->addPhoneNumber($this); // Add to the inverse side
        }
        return $this;
    }

    /**
     * Remove a custom segment
     * 
     * @param CustomSegment $segment The segment to remove
     * @return self
     */
    public function removeCustomSegment(CustomSegment $segment): self
    {
        if ($this->customSegments->removeElement($segment)) {
            $segment->removePhoneNumber($this); // Remove from the inverse side
        }
        return $this;
    }

    // Remove legacy compatibility methods
    // public function getSegments(): array ...
    // public function setSegments(array $segments): self ...
    // public function addSegment(Segment $segment): self ...

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
            // Avoid including full related entities in basic toArray to prevent issues.
            // Formatters/Serializers should handle this based on context (e.g., GraphQL query).
            // 'technicalSegments' => $this->technicalSegments->map(fn(Segment $s) => $s->getId())->toArray(),
            // 'customSegments' => $this->customSegments->map(fn(CustomSegment $cs) => $cs->getId())->toArray(),
        ];
    }
}
