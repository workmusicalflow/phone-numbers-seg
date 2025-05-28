<?php

namespace App\Services\WhatsApp\Commands;

use App\Entities\User;
use App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface;
use App\Services\WhatsApp\Events\EventDispatcher;
use App\Services\WhatsApp\Events\TemplateMessageSentEvent;
use App\Services\WhatsApp\Events\TemplateMessageFailedEvent;
use Psr\Log\LoggerInterface;

/**
 * Commande pour envoyer un message template WhatsApp
 * 
 * Cette commande encapsule toute la logique d'envoi d'un template,
 * permettant de la réutiliser, la mettre en queue, ou l'annuler.
 */
class SendTemplateCommand implements CommandInterface
{
    private User $user;
    private string $recipient;
    private string $templateName;
    private string $languageCode;
    private ?string $headerImageUrl;
    private array $bodyParams;
    private WhatsAppServiceInterface $whatsappService;
    private EventDispatcher $eventDispatcher;
    private LoggerInterface $logger;
    private array $metadata;

    public function __construct(
        User $user,
        string $recipient,
        string $templateName,
        string $languageCode,
        ?string $headerImageUrl,
        array $bodyParams,
        WhatsAppServiceInterface $whatsappService,
        EventDispatcher $eventDispatcher,
        LoggerInterface $logger,
        array $metadata = []
    ) {
        $this->user = $user;
        $this->recipient = $recipient;
        $this->templateName = $templateName;
        $this->languageCode = $languageCode;
        $this->headerImageUrl = $headerImageUrl;
        $this->bodyParams = $bodyParams;
        $this->whatsappService = $whatsappService;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
        $this->metadata = array_merge([
            'command_id' => uniqid('cmd_', true),
            'created_at' => new \DateTime()
        ], $metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): CommandResult
    {
        $this->logger->info('Executing SendTemplateCommand', [
            'command_id' => $this->metadata['command_id'],
            'template' => $this->templateName,
            'recipient' => $this->recipient
        ]);

        try {
            // Vérifier si on peut exécuter
            if (!$this->canExecute()) {
                return CommandResult::failure(
                    'Cannot execute command: validation failed',
                    ['validation' => 'User has insufficient credits or is inactive'],
                    $this->metadata
                );
            }

            // Exécuter l'envoi via le service
            $messageHistory = $this->whatsappService->sendTemplateMessage(
                $this->user,
                $this->recipient,
                $this->templateName,
                $this->languageCode,
                $this->headerImageUrl,
                $this->bodyParams
            );

            // Dispatcher l'événement de succès
            $this->eventDispatcher->dispatch(
                new TemplateMessageSentEvent(
                    $this->user,
                    $messageHistory,
                    $this->templateName,
                    $this->recipient,
                    $this->metadata
                )
            );

            return CommandResult::success(
                $messageHistory,
                'Template message sent successfully',
                array_merge($this->metadata, [
                    'message_id' => $messageHistory->getWabaMessageId(),
                    'executed_at' => new \DateTime()
                ])
            );

        } catch (\Exception $e) {
            $this->logger->error('SendTemplateCommand failed', [
                'command_id' => $this->metadata['command_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Dispatcher l'événement d'échec
            $this->eventDispatcher->dispatch(
                new TemplateMessageFailedEvent(
                    $this->user,
                    $this->templateName,
                    $this->recipient,
                    $e,
                    $this->metadata
                )
            );

            return CommandResult::failure(
                'Failed to send template message',
                [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode()
                ],
                $this->metadata
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function canExecute(): bool
    {
        // Vérifier que l'utilisateur a des crédits
        if ($this->user->getSmsCredit() <= 0) {
            return false;
        }

        // Vérifier que nous avons les données minimales
        if (empty($this->recipient) || empty($this->templateName)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'send_template_message';
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata(): array
    {
        return array_merge($this->metadata, [
            'user_id' => $this->user->getId(),
            'recipient' => $this->recipient,
            'template_name' => $this->templateName,
            'language' => $this->languageCode,
            'has_media' => $this->headerImageUrl !== null,
            'body_params_count' => count($this->bodyParams)
        ]);
    }

    /**
     * Getters pour permettre l'inspection de la commande
     */
    public function getUser(): User
    {
        return $this->user;
    }

    public function getRecipient(): string
    {
        return $this->recipient;
    }

    public function getTemplateName(): string
    {
        return $this->templateName;
    }
}