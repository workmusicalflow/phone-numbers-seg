<?php

declare(strict_types=1);
namespace App\Entities\WhatsApp;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entité pour les templates WhatsApp
 */
#[ORM\Entity]
#[ORM\Table(name: "whatsapp_templates")]
#[ORM\HasLifecycleCallbacks]
class WhatsAppTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;
    
    #[ORM\Column(type: "string", length: 255)]
    private string $name;
    
    #[ORM\Column(type: "string", length: 10)]
    private string $language;
    
    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $category = null;
    
    #[ORM\Column(type: "string", length: 20)]
    private string $status;
    
    #[ORM\Column(type: "text", nullable: true)]
    private ?string $components = null;
    
    #[ORM\Column(name: "is_active", type: "boolean", options: ["default" => 1])]
    private bool $isActive = true;
    
    #[ORM\Column(name: "meta_template_id", type: "string", length: 255, nullable: true)]
    private ?string $metaTemplateId = null;
    
    #[ORM\Column(name: "created_at", type: "datetime")]
    private \DateTime $createdAt;
    
    #[ORM\Column(name: "updated_at", type: "datetime")]
    private \DateTime $updatedAt;
    
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }
    
    #[ORM\PreUpdate]
    public function onUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }
    
    // Getters et setters
    
    public function getId(): int
    {
        return $this->id;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }
    
    public function getLanguage(): string
    {
        return $this->language;
    }
    
    public function setLanguage(string $language): self
    {
        $this->language = $language;
        return $this;
    }
    
    public function getCategory(): ?string
    {
        return $this->category;
    }
    
    public function setCategory(?string $category): self
    {
        $this->category = $category;
        return $this;
    }
    
    public function getStatus(): string
    {
        return $this->status;
    }
    
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }
    
    public function getComponents(): ?string
    {
        return $this->components;
    }
    
    public function setComponents(?string $components): self
    {
        $this->components = $components;
        return $this;
    }
    
    public function getComponentsAsArray(): array
    {
        return $this->components ? json_decode($this->components, true) ?? [] : [];
    }
    
    public function setComponentsFromArray(array $components): self
    {
        $this->components = json_encode($components);
        return $this;
    }
    
    public function isActive(): bool
    {
        return $this->isActive;
    }
    
    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }
    
    public function getMetaTemplateId(): ?string
    {
        return $this->metaTemplateId;
    }
    
    public function setMetaTemplateId(?string $metaTemplateId): self
    {
        $this->metaTemplateId = $metaTemplateId;
        return $this;
    }
    
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }
    
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }
}