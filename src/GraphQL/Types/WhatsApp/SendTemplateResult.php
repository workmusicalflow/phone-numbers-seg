<?php

declare(strict_types=1);

namespace App\GraphQL\Types\WhatsApp;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * Résultat d'envoi de template WhatsApp
 * 
 * @Type(name="SendTemplateResult")
 */
class SendTemplateResult
{
    /**
     * @var bool
     */
    private $success;
    
    /**
     * @var string|null
     */
    private $messageId;
    
    /**
     * @var string|null
     */
    private $error;

    public function __construct(bool $success, ?string $messageId = null, ?string $error = null)
    {
        $this->success = $success;
        $this->messageId = $messageId;
        $this->error = $error;
    }

    /**
     * @Field(name="success")
     */
    public function getSuccess(): bool
    {
        // Si pour une raison quelconque success est null, on retourne false
        return $this->success === null ? false : $this->success;
    }

    /**
     * @Field(name="messageId")
     */
    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    /**
     * @Field(name="error")
     */
    public function getError(): ?string
    {
        return $this->error;
    }
}