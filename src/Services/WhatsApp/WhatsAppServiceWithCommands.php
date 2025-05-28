<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Services\WhatsApp\Commands\SendTemplateCommand;
use App\Services\WhatsApp\Bus\CommandBus;
use App\Services\WhatsApp\Events\EventDispatcher;
use App\Services\WhatsApp\Bus\LoggingMiddleware;
use App\Services\WhatsApp\Listeners\CreditDeductionListener;
use App\Services\WhatsApp\Listeners\NotificationListener;
use App\Entities\User;
use App\Entities\WhatsApp\WhatsAppMessageHistory;
use Psr\Log\LoggerInterface;

/**
 * Service WhatsApp utilisant les patterns Command et Observer
 * 
 * Cette version démontre l'utilisation des patterns:
 * - Command Pattern pour encapsuler les actions
 * - Observer Pattern pour les événements
 * - Command Bus pour l'orchestration
 */
class WhatsAppServiceWithCommands extends WhatsAppServiceEnhanced
{
    private CommandBus $commandBus;
    private EventDispatcher $eventDispatcher;
    private LoggerInterface $logger;

    public function __construct(
        \App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface $apiClient,
        \App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface $messageRepository,
        \App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface $templateRepository,
        LoggerInterface $logger,
        array $config,
        \App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface $templateService,
        ?\App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateHistoryRepositoryInterface $templateHistoryRepository = null
    ) {
        parent::__construct(
            $apiClient,
            $messageRepository,
            $templateRepository,
            $logger,
            $config,
            $templateService,
            $templateHistoryRepository
        );

        // Stocker le logger pour usage local
        $this->logger = $logger;

        // Initialiser le Command Bus
        $this->commandBus = new CommandBus($logger);
        $this->commandBus->addMiddleware(new LoggingMiddleware($logger));

        // Initialiser l'Event Dispatcher
        $this->eventDispatcher = new EventDispatcher($logger);
    }

    /**
     * Configure les listeners d'événements
     */
    public function configureEventListeners(
        \App\Repositories\Interfaces\UserRepositoryInterface $userRepository,
        \App\Services\Interfaces\NotificationServiceInterface $notificationService
    ): void {
        // Listener pour déduire les crédits
        $this->eventDispatcher->addListener(
            'whatsapp.template_message.sent',
            new CreditDeductionListener($userRepository, $this->logger),
            100 // Priorité élevée
        );

        // Listener pour les notifications
        $this->eventDispatcher->addListener(
            'whatsapp.template_message.sent',
            new NotificationListener($notificationService),
            50
        );

        $this->eventDispatcher->addListener(
            'whatsapp.template_message.failed',
            new NotificationListener($notificationService),
            50
        );
    }

    /**
     * {@inheritdoc}
     * 
     * Version utilisant le Command Pattern
     */
    public function sendTemplateMessage(
        User $user,
        string $recipient,
        string $templateName,
        string $languageCode,
        ?string $headerImageUrl = null,
        array $bodyParams = []
    ): WhatsAppMessageHistory {
        // Créer la commande
        $command = new SendTemplateCommand(
            $user,
            $recipient,
            $templateName,
            $languageCode,
            $headerImageUrl,
            $bodyParams,
            $this, // Passer le service parent pour l'exécution réelle
            $this->eventDispatcher,
            $this->logger,
            [
                'source' => 'api',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]
        );

        // Exécuter via le Command Bus
        $result = $this->commandBus->handle($command);

        if (!$result->isSuccess()) {
            throw new \RuntimeException(
                $result->getMessage() ?? 'Failed to send template message',
                0,
                isset($result->getErrors()['exception']) ? 
                    new \Exception($result->getErrors()['error']) : null
            );
        }

        return $result->getData();
    }

    /**
     * Envoie plusieurs messages en batch
     * 
     * Démontre l'utilisation du Command Bus pour des opérations batch
     */
    public function sendBatchTemplateMessages(array $messages): array
    {
        $commands = [];

        foreach ($messages as $key => $message) {
            $commands[$key] = new SendTemplateCommand(
                $message['user'],
                $message['recipient'],
                $message['templateName'],
                $message['languageCode'],
                $message['headerImageUrl'] ?? null,
                $message['bodyParams'] ?? [],
                $this,
                $this->eventDispatcher,
                $this->logger,
                ['batch_id' => uniqid('batch_', true)]
            );
        }

        return $this->commandBus->handleBatch($commands);
    }

    /**
     * Récupère les statistiques du Command Bus
     */
    public function getCommandStatistics(): array
    {
        return $this->commandBus->getStatistics();
    }

    /**
     * Vérifie si des listeners sont configurés pour un événement
     */
    public function hasEventListeners(string $eventName): bool
    {
        return $this->eventDispatcher->hasListeners($eventName);
    }
}