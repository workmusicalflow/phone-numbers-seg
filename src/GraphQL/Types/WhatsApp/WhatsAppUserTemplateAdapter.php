<?php

declare(strict_types=1);

namespace App\GraphQL\Types\WhatsApp;

use App\Entities\WhatsApp\WhatsAppTemplate;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Adaptateur pour exposer WhatsAppTemplate comme WhatsAppUserTemplate
 * 
 * @Type(class=WhatsAppTemplate::class, name="WhatsAppUserTemplate")
 */
class WhatsAppUserTemplateAdapter
{
    /**
     * @Field
     */
    public function getId(WhatsAppTemplate $template): ID
    {
        return new ID($template->getId());
    }
    
    /**
     * @Field
     */
    public function template_id(WhatsAppTemplate $template): string
    {
        return (string)$template->getId();
    }

    /**
     * @Field
     */
    public function templateName(WhatsAppTemplate $template): string
    {
        return $template->getName();
    }

    /**
     * @Field
     */
    public function languageCode(WhatsAppTemplate $template): string
    {
        return $template->getLanguage();
    }

    /**
     * @Field
     */
    public function bodyVariablesCount(WhatsAppTemplate $template): int
    {
        return $template->getBodyVariablesCount();
    }

    /**
     * @Field
     */
    public function hasHeaderMedia(WhatsAppTemplate $template): bool
    {
        return $template->hasHeaderMedia();
    }

    /**
     * @Field
     */
    public function isSpecialTemplate(WhatsAppTemplate $template): bool
    {
        // Les templates globaux sont considérés comme spéciaux
        return $template->isGlobal();
    }

    /**
     * @Field
     */
    public function headerMediaUrl(WhatsAppTemplate $template): ?string
    {
        // Pour l'instant on retourne null car on n'a pas de champ URL média dans le template
        return null;
    }

    /**
     * @Field
     */
    public function createdAt(WhatsAppTemplate $template): string
    {
        return $template->getCreatedAt()->format('Y-m-d H:i:s');
    }

    /**
     * @Field
     */
    public function updatedAt(WhatsAppTemplate $template): string
    {
        return $template->getUpdatedAt()->format('Y-m-d H:i:s');
    }
    
    /**
     * @Field
     */
    public function name(WhatsAppTemplate $template): string
    {
        return $template->getName();
    }

    /**
     * @Field
     */
    public function language(WhatsAppTemplate $template): string
    {
        return $template->getLanguage();
    }

    /**
     * @Field
     */
    public function status(WhatsAppTemplate $template): string
    {
        return $template->getStatus();
    }

    /**
     * @Field
     */
    public function bodyText(WhatsAppTemplate $template): string
    {
        return $template->getBodyText();
    }

    /**
     * @Field
     */
    public function isActive(WhatsAppTemplate $template): bool
    {
        return $template->isActive();
    }

    /**
     * @Field
     */
    public function isGlobal(WhatsAppTemplate $template): bool
    {
        return $template->isGlobal();
    }
}