<?php

namespace App\GraphQL\Types\WhatsApp;

use App\Entities\WhatsApp\WhatsAppMessage;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Type GraphQL pour représenter un message WhatsApp
 * 
 * @Type(class=WhatsAppMessage::class, name="WhatsAppMessage")
 */
class WhatsAppMessageType
{
    /**
     * @Field(name="id")
     */
    public function getId(WhatsAppMessage $message): ID
    {
        return new ID($message->getId());
    }

    /**
     * @Field
     */
    public function getMessageId(WhatsAppMessage $message): string
    {
        return $message->getMessageId();
    }

    /**
     * @Field
     */
    public function getSender(WhatsAppMessage $message): string
    {
        return $message->getSender();
    }

    /**
     * @Field
     */
    public function getRecipient(WhatsAppMessage $message): ?string
    {
        return $message->getRecipient();
    }

    /**
     * @Field
     */
    public function getTimestamp(WhatsAppMessage $message): int
    {
        return $message->getTimestamp();
    }

    /**
     * @Field
     */
    public function getType(WhatsAppMessage $message): string
    {
        return $message->getType();
    }

    /**
     * @Field
     */
    public function getContent(WhatsAppMessage $message): ?string
    {
        return $message->getContent();
    }

    /**
     * @Field
     */
    public function getMediaUrl(WhatsAppMessage $message): ?string
    {
        return $message->getMediaUrl();
    }

    /**
     * @Field
     */
    public function getMediaType(WhatsAppMessage $message): ?string
    {
        return $message->getMediaType();
    }

    /**
     * @Field
     */
    public function getStatus(WhatsAppMessage $message): ?string
    {
        return $message->getStatus();
    }

    /**
     * @Field
     */
    public function getCreatedAt(WhatsAppMessage $message): int
    {
        return $message->getCreatedAt();
    }

    /**
     * @Field
     */
    public function getFormattedTimestamp(WhatsAppMessage $message): string
    {
        return date('Y-m-d H:i:s', $message->getTimestamp());
    }

    /**
     * @Field
     */
    public function getFormattedCreatedAt(WhatsAppMessage $message): string
    {
        return date('Y-m-d H:i:s', $message->getCreatedAt());
    }
}