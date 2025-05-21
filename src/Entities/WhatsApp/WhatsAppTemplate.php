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
    // Constantes pour les catégories de templates
    public const CATEGORY_MARKETING = 'MARKETING';
    public const CATEGORY_UTILITY = 'UTILITY';
    public const CATEGORY_AUTHENTICATION = 'AUTHENTICATION';
    public const CATEGORY_STANDARD = 'STANDARD';
    
    // Constantes pour les statuts de templates
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_REJECTED = 'REJECTED';
    
    // Constantes pour les types de composants
    public const COMPONENT_TYPE_HEADER = 'HEADER';
    public const COMPONENT_TYPE_BODY = 'BODY';
    public const COMPONENT_TYPE_FOOTER = 'FOOTER';
    public const COMPONENT_TYPE_BUTTONS = 'BUTTONS';
    
    // Constantes pour les formats d'en-tête
    public const HEADER_FORMAT_TEXT = 'TEXT';
    public const HEADER_FORMAT_IMAGE = 'IMAGE';
    public const HEADER_FORMAT_VIDEO = 'VIDEO';
    public const HEADER_FORMAT_DOCUMENT = 'DOCUMENT';
    public const HEADER_FORMAT_LOCATION = 'LOCATION';
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
    
    #[ORM\Column(name: "is_global", type: "boolean", options: ["default" => 0])]
    private bool $isGlobal = false;
    
    #[ORM\Column(name: "meta_template_id", type: "string", length: 255, nullable: true)]
    private ?string $metaTemplateId = null;
    
    #[ORM\Column(name: "quality_score", type: "float", nullable: true)]
    private ?float $qualityScore = null;
    
    #[ORM\Column(name: "header_format", type: "string", length: 20, nullable: true)]
    private ?string $headerFormat = null;
    
    #[ORM\Column(name: "body_text", type: "text", nullable: true)]
    private ?string $bodyText = null;
    
    #[ORM\Column(name: "footer_text", type: "text", nullable: true)]
    private ?string $footerText = null;
    
    #[ORM\Column(name: "body_variables_count", type: "integer", nullable: true)]
    private ?int $bodyVariablesCount = null;
    
    #[ORM\Column(name: "buttons_count", type: "integer", nullable: true)]
    private ?int $buttonsCount = null;
    
    #[ORM\Column(name: "buttons_details", type: "text", nullable: true)]
    private ?string $buttonsDetails = null;
    
    #[ORM\Column(name: "rejection_reason", type: "text", nullable: true)]
    private ?string $rejectionReason = null;

    #[ORM\Column(name: "usage_count", type: "integer", options: ["default" => 0])]
    private int $usageCount = 0;
    
    #[ORM\Column(name: "last_used_at", type: "datetime", nullable: true)]
    private ?\DateTime $lastUsedAt = null;
    
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
    
    /**
     * Extrait le texte du corps à partir des composants
     */
    public function extractBodyTextFromComponents(): string
    {
        $components = $this->getComponentsAsArray();
        
        foreach ($components as $component) {
            if (isset($component['type']) && strtoupper($component['type']) === 'BODY' && isset($component['text'])) {
                return $component['text'];
            }
        }
        
        return '';
    }
    
    /**
     * Compte le nombre de variables dans le corps du message
     */
    public function countBodyVariables(): int
    {
        $bodyText = $this->extractBodyTextFromComponents();
        preg_match_all('/{{[0-9]+}}/', $bodyText, $matches);
        return count($matches[0]);
    }
    
    /**
     * Détermine si le template a un en-tête média
     */
    public function hasHeaderMedia(): bool
    {
        $components = $this->getComponentsAsArray();
        
        foreach ($components as $component) {
            if (isset($component['type']) && strtoupper($component['type']) === 'HEADER') {
                $format = $component['format'] ?? '';
                return in_array(strtoupper($format), ['IMAGE', 'VIDEO', 'DOCUMENT'], true);
            }
        }
        
        return false;
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
    
    public function isGlobal(): bool
    {
        return $this->isGlobal;
    }
    
    public function setIsGlobal(bool $isGlobal): self
    {
        $this->isGlobal = $isGlobal;
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
    
    public function getQualityScore(): ?float
    {
        return $this->qualityScore;
    }
    
    public function setQualityScore(?float $qualityScore): self
    {
        $this->qualityScore = $qualityScore;
        return $this;
    }
    
    public function getHeaderFormat(): ?string
    {
        return $this->headerFormat;
    }
    
    public function setHeaderFormat(?string $headerFormat): self
    {
        $this->headerFormat = $headerFormat;
        return $this;
    }
    
    public function getBodyText(): ?string
    {
        return $this->bodyText;
    }
    
    public function setBodyText(?string $bodyText): self
    {
        $this->bodyText = $bodyText;
        return $this;
    }
    
    public function getFooterText(): ?string
    {
        return $this->footerText;
    }
    
    public function setFooterText(?string $footerText): self
    {
        $this->footerText = $footerText;
        return $this;
    }
    
    public function getBodyVariablesCount(): ?int
    {
        return $this->bodyVariablesCount;
    }
    
    public function setBodyVariablesCount(?int $bodyVariablesCount): self
    {
        $this->bodyVariablesCount = $bodyVariablesCount;
        return $this;
    }
    
    public function getButtonsCount(): ?int
    {
        return $this->buttonsCount;
    }
    
    public function setButtonsCount(?int $buttonsCount): self
    {
        $this->buttonsCount = $buttonsCount;
        return $this;
    }
    
    public function getButtonsDetails(): ?string
    {
        return $this->buttonsDetails;
    }
    
    public function setButtonsDetails(?string $buttonsDetails): self
    {
        $this->buttonsDetails = $buttonsDetails;
        return $this;
    }
    
    public function getButtonsDetailsAsArray(): array
    {
        return $this->buttonsDetails ? json_decode($this->buttonsDetails, true) ?? [] : [];
    }
    
    public function setButtonsDetailsFromArray(array $buttonsDetails): self
    {
        $this->buttonsDetails = json_encode($buttonsDetails);
        return $this;
    }
    
    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }
    
    public function setRejectionReason(?string $rejectionReason): self
    {
        $this->rejectionReason = $rejectionReason;
        return $this;
    }
    
    public function getUsageCount(): int
    {
        return $this->usageCount;
    }
    
    public function setUsageCount(int $usageCount): self
    {
        $this->usageCount = $usageCount;
        return $this;
    }
    
    public function incrementUsageCount(): self
    {
        $this->usageCount++;
        $this->lastUsedAt = new \DateTime();
        return $this;
    }
    
    public function getLastUsedAt(): ?\DateTime
    {
        return $this->lastUsedAt;
    }
    
    public function setLastUsedAt(?\DateTime $lastUsedAt): self
    {
        $this->lastUsedAt = $lastUsedAt;
        return $this;
    }
    
    /**
     * Extrait et stocke les composants du template à partir d'un tableau
     *
     * @param array $components Composants provenant de l'API Meta
     * @return self
     */
    public function extractComponentDetails(array $components): self
    {
        foreach ($components as $component) {
            $type = $component['type'] ?? '';
            
            switch ($type) {
                case self::COMPONENT_TYPE_HEADER:
                    $this->setHeaderFormat($component['format'] ?? null);
                    break;
                    
                case self::COMPONENT_TYPE_BODY:
                    if (isset($component['text'])) {
                        $this->setBodyText($component['text']);
                        
                        // Compter les variables
                        preg_match_all('/{{[0-9]+}}/', $component['text'], $matches);
                        $this->setBodyVariablesCount(count(array_unique($matches[0])));
                    }
                    break;
                    
                case self::COMPONENT_TYPE_FOOTER:
                    if (isset($component['text'])) {
                        $this->setFooterText($component['text']);
                    }
                    break;
                    
                case self::COMPONENT_TYPE_BUTTONS:
                    if (isset($component['buttons']) && is_array($component['buttons'])) {
                        $this->setButtonsCount(count($component['buttons']));
                        $this->setButtonsDetailsFromArray($component['buttons']);
                    }
                    break;
            }
        }
        
        return $this;
    }
    
    /**
     * Détermine si le template peut inclure un média d'en-tête
     */
    public function canHaveHeaderMedia(): bool
    {
        return in_array($this->headerFormat, [
            self::HEADER_FORMAT_IMAGE,
            self::HEADER_FORMAT_VIDEO,
            self::HEADER_FORMAT_DOCUMENT
        ], true);
    }
    
    /**
     * Détermine si le template a des boutons
     */
    public function hasButtons(): bool
    {
        return $this->buttonsCount > 0;
    }
}