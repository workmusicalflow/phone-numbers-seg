<?php

declare(strict_types=1);

namespace App\GraphQL\Types\WhatsApp;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Input;

/**
 * Type GraphQL pour les templates WhatsApp (directement depuis Meta)
 */
#[Type(name: "WhatsAppTemplate")]
class WhatsAppTemplateType
{
    private string $id;
    private string $name;
    private string $category;
    private string $language;
    private string $status;
    private array $components;
    private ?float $qualityScore;
    private ?string $headerFormat;
    private ?string $bodyText;
    private ?string $footerText;
    private ?int $bodyVariablesCount;
    private ?int $buttonsCount;
    private ?array $buttonsDetails;
    private ?string $rejectionReason;
    private int $usageCount;
    private ?string $lastUsedAt;

    public function __construct(array $metaTemplate)
    {
        $this->id = $metaTemplate['id'] ?? '';
        $this->name = $metaTemplate['name'] ?? '';
        $this->category = $metaTemplate['category'] ?? '';
        $this->language = $metaTemplate['language'] ?? '';
        $this->status = $metaTemplate['status'] ?? '';
        $this->components = $metaTemplate['components'] ?? [];
        $this->qualityScore = $metaTemplate['quality_score'] ?? null;
        $this->headerFormat = $metaTemplate['header_format'] ?? null;
        $this->bodyText = $metaTemplate['body_text'] ?? null;
        $this->footerText = $metaTemplate['footer_text'] ?? null;
        $this->bodyVariablesCount = $metaTemplate['body_variables_count'] ?? null;
        $this->buttonsCount = $metaTemplate['buttons_count'] ?? null;
        $this->buttonsDetails = isset($metaTemplate['buttons_details']) ? 
            json_decode($metaTemplate['buttons_details'], true) : null;
        $this->rejectionReason = $metaTemplate['rejection_reason'] ?? null;
        $this->usageCount = $metaTemplate['usage_count'] ?? 0;
        $this->lastUsedAt = isset($metaTemplate['last_used_at']) ? 
            $metaTemplate['last_used_at']->format('Y-m-d H:i:s') : null;
    }

    /**
     * ID du template (fourni par Meta)
     */
    #[Field]
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Nom du template
     */
    #[Field]
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Catégorie du template (MARKETING, UTILITY, AUTHENTICATION, etc.)
     */
    #[Field]
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * Code de langue du template (fr, en_US, etc.)
     */
    #[Field]
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Statut du template (APPROVED, PENDING, REJECTED)
     */
    #[Field]
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Composants du template sous forme de chaîne JSON
     */
    #[Field]
    public function getComponentsJson(): string
    {
        return json_encode($this->components);
    }

    /**
     * Description du template (générée à partir des composants)
     */
    #[Field]
    public function getDescription(): string
    {
        $description = '';
        
        // Parcourir les composants pour générer une description
        foreach ($this->components as $component) {
            $type = $component['type'] ?? '';
            
            if ($type === 'BODY' && isset($component['text'])) {
                // Tronquer le texte si trop long
                $text = $component['text'];
                if (strlen($text) > 100) {
                    $text = substr($text, 0, 97) . '...';
                }
                
                // Remplacer les variables {{N}} par [...]
                $text = preg_replace('/{{[0-9]+}}/', '[...]', $text);
                
                $description = $text;
                break; // Utiliser uniquement le corps comme description
            }
        }
        
        return $description;
    }

    /**
     * Détermine si le template contient un header média
     */
    #[Field]
    public function hasMediaHeader(): bool
    {
        foreach ($this->components as $component) {
            if (($component['type'] ?? '') === 'HEADER') {
                $format = $component['format'] ?? '';
                return in_array($format, ['IMAGE', 'VIDEO', 'DOCUMENT']);
            }
        }
        
        return false;
    }

    /**
     * Obtient le type de header (TEXT, IMAGE, VIDEO, DOCUMENT ou null)
     */
    #[Field]
    public function getHeaderType(): ?string
    {
        foreach ($this->components as $component) {
            if (($component['type'] ?? '') === 'HEADER') {
                return $component['format'] ?? null;
            }
        }
        
        return null;
    }

    /**
     * Compte le nombre de variables dans le corps du message
     */
    #[Field]
    public function getBodyVariablesCount(): int
    {
        foreach ($this->components as $component) {
            if (($component['type'] ?? '') === 'BODY' && isset($component['text'])) {
                preg_match_all('/{{[0-9]+}}/', $component['text'], $matches);
                return count($matches[0]);
            }
        }
        
        return 0;
    }

    /**
     * Vérifie si le template contient des boutons
     */
    #[Field]
    public function hasButtons(): bool
    {
        foreach ($this->components as $component) {
            if (($component['type'] ?? '') === 'BUTTONS' && !empty($component['buttons'])) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Obtient le nombre de boutons dans le template
     */
    #[Field]
    public function getButtonsCount(): int
    {
        foreach ($this->components as $component) {
            if (($component['type'] ?? '') === 'BUTTONS' && isset($component['buttons'])) {
                return count($component['buttons']);
            }
        }
        
        return 0;
    }

    /**
     * Vérifie si le template a un footer
     */
    #[Field]
    public function hasFooter(): bool
    {
        foreach ($this->components as $component) {
            if (($component['type'] ?? '') === 'FOOTER' && !empty($component['text'])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Score de qualité du template (si disponible)
     */
    #[Field]
    public function getQualityScore(): ?float
    {
        return $this->qualityScore;
    }
    
    /**
     * Format d'en-tête spécifique (TEXT, IMAGE, VIDEO, DOCUMENT)
     */
    #[Field]
    public function getHeaderFormat(): ?string
    {
        return $this->headerFormat;
    }
    
    /**
     * Texte complet du corps du message
     */
    #[Field]
    public function getFullBodyText(): ?string
    {
        return $this->bodyText;
    }
    
    /**
     * Texte du pied de page
     */
    #[Field]
    public function getFooterText(): ?string
    {
        return $this->footerText;
    }
    
    /**
     * Détails des boutons au format JSON
     */
    #[Field]
    public function getButtonsDetailsJson(): ?string
    {
        return $this->buttonsDetails ? json_encode($this->buttonsDetails) : null;
    }
    
    /**
     * Raison du rejet (pour les templates rejetés)
     */
    #[Field]
    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }
    
    /**
     * Nombre d'utilisations du template
     */
    #[Field]
    public function getUsageCount(): int
    {
        return $this->usageCount;
    }
    
    /**
     * Date de dernière utilisation au format ISO
     */
    #[Field]
    public function getLastUsedAt(): ?string
    {
        return $this->lastUsedAt;
    }
    
    /**
     * Indique si ce template est populaire (utilisé plus de 10 fois)
     */
    #[Field]
    public function isPopular(): bool
    {
        return $this->usageCount > 10;
    }
}

/**
 * Type d'entrée pour les filtres de templates
 */
#[Input(name: "TemplateFilterInput")]
class TemplateFilterInput
{
    #[Field]
    public ?string $name = null;
    
    #[Field]
    public ?string $language = null;
    
    #[Field]
    public ?string $category = null;
    
    #[Field]
    public ?string $status = null;
    
    #[Field]
    public ?string $headerFormat = null;
    
    #[Field]
    public ?bool $hasHeaderMedia = null;
    
    #[Field]
    public ?int $minVariables = null;
    
    #[Field]
    public ?int $maxVariables = null;
    
    #[Field]
    public ?bool $hasButtons = null;
    
    #[Field]
    public ?int $buttonCount = null;
    
    #[Field]
    public ?bool $hasFooter = null;
    
    #[Field]
    public ?string $bodyText = null;
    
    #[Field]
    public ?int $minUsageCount = null;
    
    #[Field]
    public ?string $orderBy = null;
    
    #[Field]
    public ?string $orderDirection = null;
}

/**
 * Type d'entrée pour l'envoi d'un message template
 */
#[Input(name: "SendTemplateInput")]
class SendTemplateInput
{
    #[Field]
    public string $recipientPhoneNumber;
    
    #[Field]
    public string $templateName;
    
    #[Field]
    public string $templateLanguage;
    
    #[Field]
    public string $templateComponentsJsonString;
    
    #[Field]
    public ?string $headerMediaUrl = null;
    
    #[Field]
    public ?string $headerMediaId = null;
    
    #[Field]
    public array $bodyVariables = [];
    
    #[Field]
    public array $buttonVariables = [];
}

/**
 * Type pour les paramètres d'un composant de template
 */
#[Input(name: "TemplateParameterInput")]
class TemplateParameterInput
{
    #[Field]
    public string $type;
    
    #[Field]
    public ?string $text = null;
    
    #[Field]
    public ?string $mediaType = null;
    
    #[Field]
    public ?string $mediaUrl = null;
    
    #[Field]
    public ?string $mediaId = null;
    
    #[Field]
    public ?string $payload = null;
}