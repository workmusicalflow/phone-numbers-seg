<?php

declare(strict_types=1);

namespace App\GraphQL\Types\WhatsApp;

use App\Entities\WhatsApp\WhatsAppTemplate;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Type GraphQL pour représenter un template WhatsApp
 * 
 * @Type(class=WhatsAppTemplate::class, name="WhatsAppTemplate")
 */
class WhatsAppTemplateType
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
    public function getName(WhatsAppTemplate $template): string
    {
        return $template->getName();
    }

    /**
     * @Field
     */
    public function getLanguage(WhatsAppTemplate $template): string
    {
        return $template->getLanguage();
    }

    /**
     * @Field
     */
    public function getCategory(WhatsAppTemplate $template): ?string
    {
        return $template->getCategory();
    }

    /**
     * @Field
     */
    public function getStatus(WhatsAppTemplate $template): string
    {
        return $template->getStatus();
    }

    /**
     * @Field
     */
    public function getComponents(WhatsAppTemplate $template): ?string
    {
        return $template->getComponents();
    }

    /**
     * @Field
     */
    public function getIsActive(WhatsAppTemplate $template): bool
    {
        return $template->getIsActive();
    }

    /**
     * @Field
     */
    public function getMetaTemplateId(WhatsAppTemplate $template): ?string
    {
        return $template->getMetaTemplateId();
    }

    /**
     * @Field
     */
    public function getCreatedAt(WhatsAppTemplate $template): string
    {
        return $template->getCreatedAt()->format('Y-m-d H:i:s');
    }

    /**
     * @Field
     */
    public function getUpdatedAt(WhatsAppTemplate $template): string
    {
        return $template->getUpdatedAt()->format('Y-m-d H:i:s');
    }
}