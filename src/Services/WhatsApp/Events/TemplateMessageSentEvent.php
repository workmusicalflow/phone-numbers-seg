<?php

namespace App\Services\WhatsApp\Events;

use App\Entities\User;
use App\Entities\WhatsApp\WhatsAppMessageHistory;

/**
 * Événement déclenché lorsqu'un message template est envoyé avec succès
 */
class TemplateMessageSentEvent extends AbstractEvent
{
    private User $user;
    private WhatsAppMessageHistory $messageHistory;
    private string $templateName;
    private string $recipient;

    public function __construct(
        User $user,
        WhatsAppMessageHistory $messageHistory,
        string $templateName,
        string $recipient,
        array $metadata = []
    ) {
        parent::__construct($metadata);
        $this->user = $user;
        $this->messageHistory = $messageHistory;
        $this->templateName = $templateName;
        $this->recipient = $recipient;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'whatsapp.template_message.sent';
    }

    /**
     * {@inheritdoc}
     */
    public function getData(): array
    {
        return [
            'user_id' => $this->user->getId(),
            'message_id' => $this->messageHistory->getWabaMessageId(),
            'template_name' => $this->templateName,
            'recipient' => $this->recipient,
            'timestamp' => $this->messageHistory->getTimestamp()->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Getters pour accès direct aux propriétés
     */
    public function getUser(): User
    {
        return $this->user;
    }

    public function getMessageHistory(): WhatsAppMessageHistory
    {
        return $this->messageHistory;
    }

    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    public function getRecipient(): string
    {
        return $this->recipient;
    }
}