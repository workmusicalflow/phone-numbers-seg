<?php

namespace App\GraphQL\Types\WhatsApp;

use TheCodingMachine\GraphQLite\Annotations\Input;
use TheCodingMachine\GraphQLite\Annotations\Field;

/**
 * Type d'entrée GraphQL pour les messages WhatsApp
 * 
 * @Input(name="WhatsAppMessageInput")
 */
class WhatsAppMessageInputType
{
    /**
     * @Field
     */
    public string $recipient;

    /**
     * @Field
     */
    public string $type;

    /**
     * @Field
     */
    public ?string $content = null;

    /**
     * @Field
     */
    public ?string $mediaUrl = null;

    /**
     * @Field
     */
    public ?string $mediaType = null;

    /**
     * @Field
     */
    public ?string $templateName = null;

    /**
     * @Field
     */
    public ?string $languageCode = null;

    /**
     * @Field
     */
    public ?string $templateParams = null;
}