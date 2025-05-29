<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Handlers;

use App\Services\WhatsApp\Commands\BulkSendResult;
use App\Services\WhatsApp\Commands\BulkSendTemplateCommand;
use App\Services\WhatsApp\Commands\CommandInterface;
use App\Services\WhatsApp\Events\EventDispatcher;
use App\Services\WhatsApp\Events\BulkSendStartedEvent;
use App\Services\WhatsApp\Events\BulkSendProgressEvent;
use App\Services\WhatsApp\Events\BulkSendCompletedEvent;
use App\Services\WhatsApp\Events\BatchProcessedEvent;
use App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface;
use App\Entities\WhatsApp\WhatsAppMessageHistory;
use Psr\Log\LoggerInterface;

/**
 * Handler pour le traitement des commandes d'envoi en masse
 * 
 * Gère l'envoi de messages template WhatsApp à plusieurs destinataires
 * avec traitement par batch, gestion d'erreurs et événements de progression.
 */
class BulkSendHandler implements HandlerInterface
{
    /**
     * Limite maximale de destinataires par requête pour éviter les abus
     */
    private const MAX_RECIPIENTS_PER_REQUEST = 500;
    
    public function __construct(
        private readonly WhatsAppServiceInterface $whatsAppService,
        private readonly EventDispatcher $eventDispatcher,
        private readonly LoggerInterface $logger
    ) {}

    public function supports(CommandInterface $command): bool
    {
        return $command instanceof BulkSendTemplateCommand;
    }

    /**
     * @param BulkSendTemplateCommand $command
     */
    public function handle(CommandInterface $command): BulkSendResult
    {
        if (!$this->supports($command)) {
            throw new \InvalidArgumentException('Commande non supportée');
        }

        // Vérifier la limite de destinataires
        if ($command->getRecipientCount() > self::MAX_RECIPIENTS_PER_REQUEST) {
            throw new \InvalidArgumentException(sprintf(
                'Le nombre de destinataires (%d) dépasse la limite autorisée (%d)',
                $command->getRecipientCount(),
                self::MAX_RECIPIENTS_PER_REQUEST
            ));
        }

        $this->logger->info('Démarrage de l\'envoi en masse', [
            'template' => $command->getTemplateName(),
            'recipients' => $command->getRecipientCount(),
            'batchSize' => $command->getBatchSize()
        ]);

        // Émettre l'événement de démarrage
        $this->eventDispatcher->dispatch(new BulkSendStartedEvent(
            $command->getUser(),
            $command->getTemplateName(),
            $command->getRecipientCount()
        ));

        $successfulSends = [];
        $failedSends = [];
        $batches = $command->getBatches();
        $totalBatches = count($batches);

        foreach ($batches as $batchIndex => $batch) {
            $batchNumber = $batchIndex + 1;
            
            $this->logger->debug('Traitement du batch', [
                'batch' => $batchNumber,
                'total' => $totalBatches,
                'size' => count($batch)
            ]);

            // Traiter le batch
            $batchResult = $this->processBatch(
                $command,
                $batch,
                $successfulSends,
                $failedSends
            );

            // Émettre l'événement de progression
            $totalProcessed = count($successfulSends) + count($failedSends);
            $this->eventDispatcher->dispatch(new BulkSendProgressEvent(
                $command->getUser(),
                $command->getTemplateName(),
                $totalProcessed,
                $command->getRecipientCount(),
                count($successfulSends),
                count($failedSends)
            ));

            // Émettre l'événement de batch traité
            $this->eventDispatcher->dispatch(new BatchProcessedEvent(
                $batchNumber,
                $totalBatches,
                $batchResult['successful'],
                $batchResult['failed']
            ));

            // Vérifier si on doit arrêter en cas d'erreur
            if ($command->shouldStopOnError() && $batchResult['failed'] > 0) {
                $this->logger->warning('Arrêt de l\'envoi en masse suite à des erreurs', [
                    'batch' => $batchNumber,
                    'errors' => $batchResult['failed']
                ]);
                break;
            }

            // Attendre entre les batches (sauf pour le dernier)
            if ($batchIndex < $totalBatches - 1 && $command->getDelayBetweenBatches() > 0) {
                usleep($command->getDelayBetweenBatches() * 1000); // Convertir ms en μs
            }
        }

        // Créer le résultat final
        $result = new BulkSendResult($successfulSends, $failedSends, [
            'batches' => $totalBatches,
            'batchSize' => $command->getBatchSize(),
            'template' => $command->getTemplateName()
        ]);

        // Émettre l'événement de fin
        $this->eventDispatcher->dispatch(new BulkSendCompletedEvent(
            $command->getUser(),
            $command->getTemplateName(),
            $result
        ));

        $this->logger->info('Envoi en masse terminé', [
            'template' => $command->getTemplateName(),
            'successful' => $result->getTotalSent(),
            'failed' => $result->getTotalFailed(),
            'successRate' => $result->getSuccessRate()
        ]);

        return $result;
    }

    /**
     * Traite un batch de destinataires
     * 
     * @param BulkSendTemplateCommand $command
     * @param array<int, string> $batch
     * @param array<string, WhatsAppMessageHistory> $successfulSends
     * @param array<string, array{error: string, code?: string}> $failedSends
     * @return array{successful: int, failed: int}
     */
    private function processBatch(
        BulkSendTemplateCommand $command,
        array $batch,
        array &$successfulSends,
        array &$failedSends
    ): array {
        $batchSuccessful = 0;
        $batchFailed = 0;

        foreach ($batch as $recipient) {
            try {
                // Obtenir les paramètres pour ce destinataire (pour rétrocompatibilité)
                $legacyParameters = $command->getParametersForRecipient($recipient);

                // Utiliser les nouveaux paramètres structurés ou fallback sur legacy
                $bodyVariables = !empty($command->getBodyVariables()) 
                    ? $command->getBodyVariables() 
                    : ($legacyParameters['bodyParams'] ?? []);
                
                $headerMediaUrl = $command->getHeaderMediaUrl() 
                    ?? $legacyParameters['headerImageUrl'] 
                    ?? null;

                // Envoyer le message avec les paramètres appropriés
                $message = $this->whatsAppService->sendTemplateMessage(
                    $command->getUser(),
                    $recipient,
                    $command->getTemplateName(),
                    $command->getTemplateLanguage(),
                    $headerMediaUrl,
                    $bodyVariables,
                    $command->getHeaderVariables(),
                    $command->getHeaderMediaId()
                );

                $successfulSends[$recipient] = $message;
                $batchSuccessful++;

                $this->logger->debug('Message envoyé avec succès', [
                    'recipient' => $recipient,
                    'messageId' => $message->getWabaMessageId()
                ]);
            } catch (\Exception $e) {
                $failedSends[$recipient] = [
                    'error' => $e->getMessage(),
                    'code' => method_exists($e, 'getCode') ? (string)$e->getCode() : 'UNKNOWN'
                ];
                $batchFailed++;

                $this->logger->error('Échec de l\'envoi du message', [
                    'recipient' => $recipient,
                    'error' => $e->getMessage(),
                    'exception' => get_class($e)
                ]);
            }
        }

        return [
            'successful' => $batchSuccessful,
            'failed' => $batchFailed
        ];
    }
}