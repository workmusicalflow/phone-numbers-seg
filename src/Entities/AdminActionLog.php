<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repositories\Doctrine\AdminActionLogRepository")
 * @ORM\Table(name="admin_action_logs")
 */
class AdminActionLog
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entities\User")
     * @ORM\JoinColumn(name="admin_id", referencedColumnName="id", nullable=false)
     */
    private User $admin;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $actionType;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $targetId = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $targetType = null;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $details = null;

    /**
     * @ORM\Column(type="datetime")
     */
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdmin(): User
    {
        return $this->admin;
    }

    public function setAdmin(User $admin): self
    {
        $this->admin = $admin;
        return $this;
    }

    public function getActionType(): string
    {
        return $this->actionType;
    }

    public function setActionType(string $actionType): self
    {
        $this->actionType = $actionType;
        return $this;
    }

    public function getTargetId(): ?int
    {
        return $this->targetId;
    }

    public function setTargetId(?int $targetId): self
    {
        $this->targetId = $targetId;
        return $this;
    }

    public function getTargetType(): ?string
    {
        return $this->targetType;
    }

    public function setTargetType(?string $targetType): self
    {
        $this->targetType = $targetType;
        return $this;
    }

    public function getDetails(): ?array
    {
        return $this->details;
    }

    public function setDetails(?array $details): self
    {
        $this->details = $details;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
