<?php

declare(strict_types=1);

namespace App\GraphQL\Types\WhatsApp;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Input;

/**
 * Type d'entrée pour l'envoi de templates WhatsApp
 */
#[Input]
class SendTemplateInput
{
    public function __construct(
        private string $recipientPhoneNumber,
        private string $templateName,
        private string $templateLanguage,
        private ?string $templateComponentsJsonString = null,
        private ?string $headerMediaUrl = null,
        private ?array $bodyVariables = [],
        private ?array $buttonVariables = []
    ) {}

    /**
     * Numéro de téléphone du destinataire
     */
    #[Field]
    public function getRecipientPhoneNumber(): string
    {
        return $this->recipientPhoneNumber;
    }

    /**
     * Nom du template à utiliser
     */
    #[Field]
    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    /**
     * Code de langue du template
     */
    #[Field]
    public function getTemplateLanguage(): string
    {
        return $this->templateLanguage;
    }

    /**
     * Composants du template au format JSON
     */
    #[Field]
    public function getTemplateComponentsJsonString(): ?string
    {
        return $this->templateComponentsJsonString;
    }

    /**
     * URL du média d'en-tête (pour les templates avec en-tête média)
     */
    #[Field]
    public function getHeaderMediaUrl(): ?string
    {
        return $this->headerMediaUrl;
    }

    /**
     * Variables pour le corps du template
     */
    #[Field]
    public function getBodyVariables(): ?array
    {
        return $this->bodyVariables;
    }

    /**
     * Variables pour les boutons du template
     */
    #[Field]
    public function getButtonVariables(): ?array
    {
        return $this->buttonVariables;
    }
}