<?php

declare(strict_types=1);

namespace App\GraphQL\Types\WhatsApp;

/**
 * Cette classe a été conservée pour référence mais n'est plus utilisée activement.
 * L'envoi de templates WhatsApp est maintenant géré exclusivement via l'API REST.
 * 
 * @deprecated Utiliser l'API REST à la place (/api/whatsapp/send-template-v2.php)
 */
class SendTemplateResult
{
    private $success;
    private $messageId;
    private $error;

    public function __construct(bool $success, ?string $messageId = null, ?string $error = null)
    {
        $this->success = $success;
        $this->messageId = $messageId;
        $this->error = $error;
    }

    /**
     * Indique si l'opération a réussi
     */
    public function getSuccess(): bool
    {
        return $this->success === null ? false : $this->success;
    }

    /**
     * Retourne l'ID du message envoyé
     */
    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    /**
     * Retourne le message d'erreur en cas d'échec
     */
    public function getError(): ?string
    {
        return $this->error;
    }
}