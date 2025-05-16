<?php

declare(strict_types=1);

namespace App\GraphQL\Types\WhatsApp;

use App\Entities\WhatsApp\WhatsAppUserTemplate;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Type GraphQL pour représenter un template WhatsApp utilisateur
 * 
 * @Type(class=WhatsAppUserTemplate::class, name="WhatsAppUserTemplate")
 */
class WhatsAppUserTemplateType
{
    /**
     * @Field
     */
    public function getId(WhatsAppUserTemplate $template): ID
    {
        return new ID($template->getId());
    }

    /**
     * @Field
     */
    public function getTemplateName(WhatsAppUserTemplate $template): string
    {
        return $template->getTemplateName();
    }

    /**
     * @Field
     */
    public function getLanguageCode(WhatsAppUserTemplate $template): string
    {
        return $template->getLanguageCode();
    }

    /**
     * @Field
     */
    public function getBodyVariablesCount(WhatsAppUserTemplate $template): int
    {
        return $template->getBodyVariablesCount();
    }

    /**
     * @Field
     */
    public function getHasHeaderMedia(WhatsAppUserTemplate $template): bool
    {
        return $template->hasHeaderMedia();
    }

    /**
     * @Field
     */
    public function getIsSpecialTemplate(WhatsAppUserTemplate $template): bool
    {
        return $template->isSpecialTemplate();
    }

    /**
     * @Field
     */
    public function getHeaderMediaUrl(WhatsAppUserTemplate $template): ?string
    {
        return $template->getHeaderMediaUrl();
    }

    /**
     * @Field
     */
    public function getCreatedAt(WhatsAppUserTemplate $template): string
    {
        return $template->getCreatedAt()->format('Y-m-d H:i:s');
    }

    /**
     * @Field
     */
    public function getUpdatedAt(WhatsAppUserTemplate $template): string
    {
        return $template->getUpdatedAt()->format('Y-m-d H:i:s');
    }
}