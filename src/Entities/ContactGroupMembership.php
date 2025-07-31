<?php

namespace App\Entities;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 * ContactGroupMembership entity
 * 
 * This entity represents a membership of a contact in a contact group.
 */
#[Entity(repositoryClass: "App\Repositories\Doctrine\ContactGroupMembershipRepository")]
#[Table(name: "contact_group_memberships")]
class ContactGroupMembership
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: "integer")]
    private ?int $id = null;

    #[Column(name: "contact_id", type: "integer")]
    private int $contactId;

    #[Column(name: "group_id", type: "integer")]
    private int $groupId;

    #[Column(name: "created_at", type: "datetime")]
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
     * Get the contact ID
     * 
     * @return int The contact ID
     */
    public function getContactId(): int
    {
        return $this->contactId;
    }

    /**
     * Set the contact ID
     * 
     * @param int $contactId The contact ID
     * @return self
     */
    public function setContactId(int $contactId): self
    {
        $this->contactId = $contactId;
        return $this;
    }

    /**
     * Get the group ID
     * 
     * @return int The group ID
     */
    public function getGroupId(): int
    {
        return $this->groupId;
    }

    /**
     * Set the group ID
     * 
     * @param int $groupId The group ID
     * @return self
     */
    public function setGroupId(int $groupId): self
    {
        $this->groupId = $groupId;
        return $this;
    }

    /**
     * Get the created at date
     * 
     * @return \DateTime The created at date
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * Set the created at date
     * 
     * @param \DateTime $createdAt The created at date
     * @return self
     */
    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
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
            'contactId' => $this->contactId,
            'groupId' => $this->groupId,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s')
        ];
    }
}
