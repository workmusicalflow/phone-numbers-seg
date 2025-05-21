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
    public function template_id(WhatsAppUserTemplate $template): string
    {
        // Comme WhatsAppUserTemplate ne contient pas de template_id, 
        // nous utilisons l'ID de l'entité comme fallback
        return (string)$template->getId();
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
    public function getCategory(WhatsAppUserTemplate $template): string
    {
        // Récupérer la catégorie depuis l'entité WhatsAppTemplate associée 
        $entityManager = \App\GraphQL\DIContainer::getInstance()->get('\Doctrine\ORM\EntityManagerInterface');
        $templateEntity = $entityManager->getRepository('\App\Entities\WhatsApp\WhatsAppTemplate')
            ->findOneBy([
                'name' => $template->getTemplateName(), 
                'language' => $template->getLanguageCode()
            ]);
        
        // Si on trouve le template, on retourne sa catégorie, sinon 'UTILITY' par défaut
        return $templateEntity ? $templateEntity->getCategory() ?? 'UTILITY' : 'UTILITY';
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
    
    /**
     * @Field
     */
    public function name(WhatsAppUserTemplate $template): string
    {
        return $template->getTemplateName();
    }
    
    /**
     * @Field
     */
    public function language(WhatsAppUserTemplate $template): string
    {
        return $template->getLanguageCode();
    }
    
    /**
     * @Field
     */
    public function status(WhatsAppUserTemplate $template): string
    {
        // Statut par défaut pour les templates utilisateur
        return 'APPROVED';
    }
    
    /**
     * @Field
     */
    public function componentsJson(WhatsAppUserTemplate $template): string
    {
        // Récupérer les composants depuis l'entité WhatsAppTemplate associée 
        // via le repository ou l'entity manager
        $entityManager = \App\GraphQL\DIContainer::getInstance()->get('\Doctrine\ORM\EntityManagerInterface');
        $templateEntity = $entityManager->getRepository('\App\Entities\WhatsApp\WhatsAppTemplate')
            ->findOneBy([
                'name' => $template->getTemplateName(), 
                'language' => $template->getLanguageCode()
            ]);
        
        // Si on trouve le template, on retourne ses composants, sinon on retourne un objet vide
        return $templateEntity ? $templateEntity->getComponents() ?? '{}' : '{}';
    }
}