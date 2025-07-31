<?php

namespace App\Services\WhatsApp\Listeners;

use App\Services\WhatsApp\Events\ListenerInterface;
use App\Services\WhatsApp\Events\EventInterface;
use App\Services\WhatsApp\Events\TemplateMessageSentEvent;
use App\Services\WhatsApp\Events\TemplateMessageFailedEvent;
use App\Services\Interfaces\NotificationServiceInterface;

/**
 * Listener qui envoie des notifications en temps réel
 */
class NotificationListener implements ListenerInterface
{
    private NotificationServiceInterface $notificationService;

    public function __construct(NotificationServiceInterface $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(EventInterface $event): void
    {
        switch (true) {
            case $event instanceof TemplateMessageSentEvent:
                $this->handleMessageSent($event);
                break;
                
            case $event instanceof TemplateMessageFailedEvent:
                $this->handleMessageFailed($event);
                break;
        }
    }

    /**
     * Gère les notifications de succès
     */
    private function handleMessageSent(TemplateMessageSentEvent $event): void
    {
        $this->notificationService->notify(
            $event->getUser(),
            'whatsapp.message_sent',
            [
                'type' => 'success',
                'message' => 'Message WhatsApp envoyé avec succès',
                'details' => [
                    'recipient' => $event->getRecipient(),
                    'template' => $event->getTemplateName(),
                    'message_id' => $event->getMessageHistory()->getWabaMessageId()
                ]
            ]
        );
    }

    /**
     * Gère les notifications d'échec
     */
    private function handleMessageFailed(TemplateMessageFailedEvent $event): void
    {
        $this->notificationService->notify(
            $event->getUser(),
            'whatsapp.message_failed',
            [
                'type' => 'error',
                'message' => 'Échec de l\'envoi du message WhatsApp',
                'details' => [
                    'recipient' => $event->getRecipient(),
                    'template' => $event->getTemplateName(),
                    'error' => $event->getException()->getMessage()
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function supportsAsync(): bool
    {
        // Les notifications peuvent être asynchrones
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'notification_listener';
    }
}