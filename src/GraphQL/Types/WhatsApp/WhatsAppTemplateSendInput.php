<?php

namespace App\GraphQL\Types\WhatsApp;

use TheCodingMachine\GraphQLite\Annotations\Input;
use TheCodingMachine\GraphQLite\Annotations\Field;

/**
 * Type d'entrée GraphQL pour l'envoi de templates WhatsApp
 * 
 * @Input(name="WhatsAppTemplateSendInput")
 */
class WhatsAppTemplateSendInput
{
    /**
     * @Field
     */
    public string $recipient;

    /**
     * @Field
     */
    public string $templateName;

    /**
     * @Field
     */
    public string $languageCode;

    /**
     * @Field
     */
    public ?string $headerImageUrl = null;

    /**
     * @Field
     */
    public ?string $body1Param = null;

    /**
     * @Field
     */
    public ?string $body2Param = null;

    /**
     * @Field
     */
    public ?string $body3Param = null;
}