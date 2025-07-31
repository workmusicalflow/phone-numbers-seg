<?php

namespace App\Services\WhatsApp\Events;

use App\Entities\User;

/**
 * Événement déclenché lorsqu'un message template échoue
 */
class TemplateMessageFailedEvent extends AbstractEvent
{
    private User $user;
    private string $templateName;
    private string $recipient;
    private \Exception $exception;

    public function __construct(
        User $user,
        string $templateName,
        string $recipient,
        \Exception $exception,
        array $metadata = []
    ) {
        parent::__construct($metadata);
        $this->user = $user;
        $this->templateName = $templateName;
        $this->recipient = $recipient;
        $this->exception = $exception;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'whatsapp.template_message.failed';
    }

    /**
     * {@inheritdoc}
     */
    public function getData(): array
    {
        return [
            'user_id' => $this->user->getId(),
            'template_name' => $this->templateName,
            'recipient' => $this->recipient,
            'error' => $this->exception->getMessage(),
            'error_code' => $this->exception->getCode(),
            'error_class' => get_class($this->exception)
        ];
    }

    /**
     * Getters pour accès direct aux propriétés
     */
    public function getUser(): User
    {
        return $this->user;
    }

    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    public function getRecipient(): string
    {
        return $this->recipient;
    }

    public function getException(): \Exception
    {
        return $this->exception;
    }
}