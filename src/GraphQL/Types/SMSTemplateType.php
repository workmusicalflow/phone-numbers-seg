<?php

namespace App\GraphQL\Types;

use App\Models\SMSTemplate;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Type GraphQL pour les modèles de SMS
 * 
 * @Type(class=SMSTemplate::class)
 */
class SMSTemplateType
{
    /**
     * Retourne l'ID du modèle de SMS
     * 
     * @Field()
     * @param SMSTemplate $smsTemplate
     * @return ID
     */
    public function id(SMSTemplate $smsTemplate): ID
    {
        return new ID($smsTemplate->getId());
    }

    /**
     * Retourne l'ID de l'utilisateur propriétaire du modèle
     * 
     * @Field()
     * @param SMSTemplate $smsTemplate
     * @return int
     */
    public function userId(SMSTemplate $smsTemplate): int
    {
        return $smsTemplate->getUserId();
    }

    /**
     * Retourne le titre du modèle
     * 
     * @Field()
     * @param SMSTemplate $smsTemplate
     * @return string
     */
    public function title(SMSTemplate $smsTemplate): string
    {
        return $smsTemplate->getTitle();
    }

    /**
     * Retourne le contenu du modèle
     * 
     * @Field()
     * @param SMSTemplate $smsTemplate
     * @return string
     */
    public function content(SMSTemplate $smsTemplate): string
    {
        return $smsTemplate->getContent();
    }

    /**
     * Retourne la description du modèle
     * 
     * @Field()
     * @param SMSTemplate $smsTemplate
     * @return string|null
     */
    public function description(SMSTemplate $smsTemplate): ?string
    {
        return $smsTemplate->getDescription();
    }

    /**
     * Retourne la date de création du modèle
     * 
     * @Field()
     * @param SMSTemplate $smsTemplate
     * @return string
     */
    public function createdAt(SMSTemplate $smsTemplate): string
    {
        return $smsTemplate->getCreatedAt();
    }

    /**
     * Retourne la date de dernière modification du modèle
     * 
     * @Field()
     * @param SMSTemplate $smsTemplate
     * @return string
     */
    public function updatedAt(SMSTemplate $smsTemplate): string
    {
        return $smsTemplate->getUpdatedAt();
    }

    /**
     * Retourne les variables du modèle
     * 
     * @Field()
     * @param SMSTemplate $smsTemplate
     * @return string[]
     */
    public function variables(SMSTemplate $smsTemplate): array
    {
        return $smsTemplate->extractVariables();
    }
}
