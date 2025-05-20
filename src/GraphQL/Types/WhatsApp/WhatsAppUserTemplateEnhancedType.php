<?php

declare(strict_types=1);

namespace App\GraphQL\Types\WhatsApp;

use App\Entities\WhatsApp\WhatsAppUserTemplate;
use App\Entities\WhatsApp\WhatsAppTemplate;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Type GraphQL enrichi pour représenter un template WhatsApp utilisateur avec les détails du template
 * 
 * @Type(name="WhatsAppUserTemplateEnhanced")
 */
class WhatsAppUserTemplateEnhancedType
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Field
     */
    public function getId(WhatsAppUserTemplate $userTemplate): ID
    {
        return new ID($userTemplate->getId());
    }
    
    /**
     * @Field
     */
    public function template_id(WhatsAppUserTemplate $userTemplate): string
    {
        // Utiliser l'ID de l'entité comme substitut pour le template_id
        return (string)$userTemplate->getId();
    }

    /**
     * @Field
     */
    public function getTemplate(WhatsAppUserTemplate $userTemplate): ?WhatsAppTemplate
    {
        return $this->entityManager->getRepository(WhatsAppTemplate::class)
            ->findOneBy([
                'name' => $userTemplate->getTemplateName(),
                'language' => $userTemplate->getLanguageCode()
            ]);
    }

    /**
     * @Field
     */
    public function getName(WhatsAppUserTemplate $userTemplate): string
    {
        return $userTemplate->getTemplateName();
    }

    /**
     * @Field
     */
    public function getLanguage(WhatsAppUserTemplate $userTemplate): string
    {
        return $userTemplate->getLanguageCode();
    }

    /**
     * @Field
     */
    public function getBodyVariablesCount(WhatsAppUserTemplate $userTemplate): int
    {
        return $userTemplate->getBodyVariablesCount();
    }

    /**
     * @Field
     */
    public function getHasHeaderMedia(WhatsAppUserTemplate $userTemplate): bool
    {
        return $userTemplate->hasHeaderMedia();
    }

    /**
     * @Field
     */
    public function getHeaderMediaUrl(WhatsAppUserTemplate $userTemplate): ?string
    {
        return $userTemplate->getHeaderMediaUrl();
    }
}