<?php

declare(strict_types=1);

namespace App\GraphQL\Types\WhatsApp;

use App\Entities\WhatsApp\WhatsAppMessageHistory;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Type GraphQL pour représenter l'historique d'un message WhatsApp
 * 
 * @Type(class=WhatsAppMessageHistory::class, name="WhatsAppMessageHistory")
 */
class WhatsAppMessageHistoryType
{
    /**
     * @Field
     */
    public function getId(WhatsAppMessageHistory $message): ID
    {
        return new ID($message->getId());
    }

    /**
     * @Field
     */
    public function getWabaMessageId(WhatsAppMessageHistory $message): string
    {
        return $message->getWabaMessageId();
    }

    /**
     * @Field
     */
    public function getPhoneNumber(WhatsAppMessageHistory $message): string
    {
        return $message->getPhoneNumber();
    }

    /**
     * @Field
     */
    public function getDirection(WhatsAppMessageHistory $message): string
    {
        return $message->getDirection();
    }

    /**
     * @Field
     */
    public function getType(WhatsAppMessageHistory $message): string
    {
        return $message->getType();
    }

    /**
     * @Field
     */
    public function getContent(WhatsAppMessageHistory $message): ?string
    {
        return $message->getContent();
    }

    /**
     * @Field
     */
    public function getStatus(WhatsAppMessageHistory $message): string
    {
        return $message->getStatus();
    }

    /**
     * @Field
     */
    public function getErrorCode(WhatsAppMessageHistory $message): ?int
    {
        return $message->getErrorCode();
    }

    /**
     * @Field
     */
    public function getErrorMessage(WhatsAppMessageHistory $message): ?string
    {
        return $message->getErrorMessage();
    }

    /**
     * @Field
     */
    public function getTimestamp(WhatsAppMessageHistory $message): string
    {
        return $message->getTimestamp()->format('Y-m-d H:i:s');
    }

    /**
     * @Field
     */
    public function getConversationId(WhatsAppMessageHistory $message): ?string
    {
        return $message->getConversationId();
    }

    /**
     * @Field
     */
    public function getPricingCategory(WhatsAppMessageHistory $message): ?string
    {
        return $message->getPricingCategory();
    }

    /**
     * @Field
     */
    public function getMediaId(WhatsAppMessageHistory $message): ?string
    {
        return $message->getMediaId();
    }

    /**
     * @Field
     */
    public function getTemplateName(WhatsAppMessageHistory $message): ?string
    {
        return $message->getTemplateName();
    }

    /**
     * @Field
     */
    public function getTemplateLanguage(WhatsAppMessageHistory $message): ?string
    {
        return $message->getTemplateLanguage();
    }

    /**
     * @Field
     */
    public function getContextData(WhatsAppMessageHistory $message): ?string
    {
        return $message->getContextData();
    }

    /**
     * @Field
     */
    public function getCreatedAt(WhatsAppMessageHistory $message): string
    {
        return $message->getCreatedAt()->format('Y-m-d H:i:s');
    }

    /**
     * @Field
     */
    public function getUpdatedAt(WhatsAppMessageHistory $message): ?string
    {
        $updatedAt = $message->getUpdatedAt();
        return $updatedAt ? $updatedAt->format('Y-m-d H:i:s') : null;
    }
}