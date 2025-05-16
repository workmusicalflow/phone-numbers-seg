<?php

declare(strict_types=1);

namespace App\Entities\WhatsApp;

use App\Entities\User;
use Doctrine\ORM\Mapping as ORM;
use DateTime;

#[ORM\Entity]
#[ORM\Table(name: "whatsapp_user_templates")]
#[ORM\HasLifecycleCallbacks]
class WhatsAppUserTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;
    
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false)]
    private User $user;
    
    #[ORM\Column(name: "template_name", type: "string", length: 255)]
    private string $templateName;
    
    #[ORM\Column(name: "language_code", type: "string", length: 10)]
    private string $languageCode;
    
    #[ORM\Column(name: "body_variables_count", type: "integer", options: ["default" => 0])]
    private int $bodyVariablesCount = 0;
    
    #[ORM\Column(name: "has_header_media", type: "boolean", options: ["default" => false])]
    private bool $hasHeaderMedia = false;
    
    #[ORM\Column(name: "is_special_template", type: "boolean", options: ["default" => false])]
    private bool $isSpecialTemplate = false;
    
    #[ORM\Column(name: "header_media_url", type: "string", length: 500, nullable: true)]
    private ?string $headerMediaUrl = null;
    
    #[ORM\Column(name: "created_at", type: "datetime")]
    private DateTime $createdAt;
    
    #[ORM\Column(name: "updated_at", type: "datetime")]
    private DateTime $updatedAt;
    
    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }
    
    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }
    
    public function getId(): int
    {
        return $this->id;
    }
    
    public function getUser(): User
    {
        return $this->user;
    }
    
    public function setUser(User $user): void
    {
        $this->user = $user;
    }
    
    public function getTemplateName(): string
    {
        return $this->templateName;
    }
    
    public function setTemplateName(string $templateName): void
    {
        $this->templateName = $templateName;
    }
    
    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }
    
    public function setLanguageCode(string $languageCode): void
    {
        $this->languageCode = $languageCode;
    }
    
    public function getBodyVariablesCount(): int
    {
        return $this->bodyVariablesCount;
    }
    
    public function setBodyVariablesCount(int $bodyVariablesCount): void
    {
        $this->bodyVariablesCount = $bodyVariablesCount;
    }
    
    public function hasHeaderMedia(): bool
    {
        return $this->hasHeaderMedia;
    }
    
    public function setHasHeaderMedia(bool $hasHeaderMedia): void
    {
        $this->hasHeaderMedia = $hasHeaderMedia;
    }
    
    public function isSpecialTemplate(): bool
    {
        return $this->isSpecialTemplate;
    }
    
    public function setIsSpecialTemplate(bool $isSpecialTemplate): void
    {
        $this->isSpecialTemplate = $isSpecialTemplate;
    }
    
    public function getHeaderMediaUrl(): ?string
    {
        return $this->headerMediaUrl;
    }
    
    public function setHeaderMediaUrl(?string $headerMediaUrl): void
    {
        $this->headerMediaUrl = $headerMediaUrl;
    }
    
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }
    
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }
}