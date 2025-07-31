<?php

declare(strict_types=1);

namespace App\GraphQL\Types\WhatsApp;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * Type GraphQL pour les templates WhatsApp avec construction sécurisée
 * Implémentation plus robuste pour éviter les erreurs de typage
 */
#[Type(name: "WhatsAppTemplate")]
class WhatsAppTemplateSafeType
{
    private string $id = '';
    private string $name = '';
    private string $category = '';
    private string $language = '';
    private string $status = '';
    private array $components = [];
    private ?float $qualityScore = null;
    private ?string $headerFormat = null;
    private ?string $bodyText = null;
    private ?string $footerText = null;
    private ?int $bodyVariablesCount = null;
    private ?int $buttonsCount = null;
    private ?array $buttonsDetails = null;
    private ?string $rejectionReason = null;
    private int $usageCount = 0;
    private ?string $lastUsedAt = null;

    /**
     * Constructeur sécurisé qui gère correctement les valeurs manquantes
     * 
     * @param array $metaTemplate Données du template (potentiellement incomplètes ou invalides)
     */
    public function __construct(?array $metaTemplate = null)
    {
        // Si aucun template fourni, utiliser un template vide mais valide
        if ($metaTemplate === null) {
            $metaTemplate = [
                'id' => 'empty_' . uniqid(),
                'name' => 'Empty Template',
                'category' => 'UNKNOWN',
                'language' => 'unknown',
                'status' => 'UNKNOWN',
                'components' => []
            ];
        }

        // Initialiser les propriétés avec des valeurs par défaut sûres
        $this->id = (string)($metaTemplate['id'] ?? 'id_' . uniqid());
        $this->name = (string)($metaTemplate['name'] ?? 'Unnamed Template');
        $this->category = (string)($metaTemplate['category'] ?? '');
        $this->language = (string)($metaTemplate['language'] ?? '');
        $this->status = (string)($metaTemplate['status'] ?? 'UNKNOWN');
        $this->components = is_array($metaTemplate['components'] ?? null) ? $metaTemplate['components'] : [];
        
        // Propriétés optionnelles qui peuvent être null
        $this->qualityScore = isset($metaTemplate['quality_score']) ? (float)$metaTemplate['quality_score'] : null;
        $this->headerFormat = $metaTemplate['header_format'] ?? null;
        $this->bodyText = $metaTemplate['body_text'] ?? null;
        $this->footerText = $metaTemplate['footer_text'] ?? null;
        $this->bodyVariablesCount = isset($metaTemplate['body_variables_count']) ? (int)$metaTemplate['body_variables_count'] : null;
        $this->buttonsCount = isset($metaTemplate['buttons_count']) ? (int)$metaTemplate['buttons_count'] : null;
        
        // Traitement particulier pour les détails des boutons (JSON ou array)
        if (isset($metaTemplate['buttons_details'])) {
            if (is_string($metaTemplate['buttons_details'])) {
                $this->buttonsDetails = json_decode($metaTemplate['buttons_details'], true) ?: [];
            } elseif (is_array($metaTemplate['buttons_details'])) {
                $this->buttonsDetails = $metaTemplate['buttons_details'];
            }
        }
        
        $this->rejectionReason = $metaTemplate['rejection_reason'] ?? null;
        $this->usageCount = isset($metaTemplate['usage_count']) ? (int)$metaTemplate['usage_count'] : 0;
        
        // Traitement de la date de dernière utilisation
        if (isset($metaTemplate['last_used_at'])) {
            if ($metaTemplate['last_used_at'] instanceof \DateTime) {
                $this->lastUsedAt = $metaTemplate['last_used_at']->format('Y-m-d H:i:s');
            } elseif (is_string($metaTemplate['last_used_at'])) {
                $this->lastUsedAt = $metaTemplate['last_used_at'];
            }
        }
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
        return json_encode($this->components) ?: '[]';
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
        if ($this->bodyVariablesCount !== null) {
            return $this->bodyVariablesCount;
        }

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
        if ($this->buttonsCount !== null) {
            return $this->buttonsCount > 0;
        }

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
        if ($this->buttonsCount !== null) {
            return $this->buttonsCount;
        }

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