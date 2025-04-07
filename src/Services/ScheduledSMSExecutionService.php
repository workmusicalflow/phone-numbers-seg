<?php

namespace App\Services;

use App\Models\ScheduledSMS;
use App\Models\ScheduledSMSLog;
use App\Repositories\ScheduledSMSRepository;
use App\Repositories\ScheduledSMSLogRepository;
use App\Repositories\SenderNameRepository;
use App\Services\Interfaces\ScheduledSMSExecutionServiceInterface;
use App\Services\Interfaces\SMSSenderServiceInterface;
use App\Services\Interfaces\RealtimeNotificationServiceInterface;
use App\Services\Interfaces\ErrorLoggerServiceInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Service pour l'exécution des SMS planifiés
 */
class ScheduledSMSExecutionService implements ScheduledSMSExecutionServiceInterface
{
    /**
     * @var ScheduledSMSRepository Repository pour les SMS planifiés
     */
    private ScheduledSMSRepository $scheduledSMSRepository;

    /**
     * @var ScheduledSMSLogRepository Repository pour les logs d'exécution des SMS planifiés
     */
    private ScheduledSMSLogRepository $scheduledSMSLogRepository;

    /**
     * @var SenderNameRepository Repository pour les noms d'expéditeur
     */
    private SenderNameRepository $senderNameRepository;

    /**
     * @var SMSSenderServiceInterface Service d'envoi de SMS
     */
    private SMSSenderServiceInterface $smsSenderService;

    /**
     * @var RealtimeNotificationServiceInterface|null Service de notification en temps réel
     */
    private ?RealtimeNotificationServiceInterface $realtimeNotificationService;

    /**
     * @var ErrorLoggerServiceInterface|null Service de journalisation des erreurs
     */
    private ?ErrorLoggerServiceInterface $errorLoggerService;

    /**
     * @var LoggerInterface|null Logger
     */
    private ?LoggerInterface $logger;

    /**
     * Constructeur
     * 
     * @param ScheduledSMSRepository $scheduledSMSRepository
     * @param ScheduledSMSLogRepository $scheduledSMSLogRepository
     * @param SenderNameRepository $senderNameRepository
     * @param SMSSenderServiceInterface $smsSenderService
     * @param RealtimeNotificationServiceInterface|null $realtimeNotificationService
     * @param ErrorLoggerServiceInterface|null $errorLoggerService
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        ScheduledSMSRepository $scheduledSMSRepository,
        ScheduledSMSLogRepository $scheduledSMSLogRepository,
        SenderNameRepository $senderNameRepository,
        SMSSenderServiceInterface $smsSenderService,
        ?RealtimeNotificationServiceInterface $realtimeNotificationService = null,
        ?ErrorLoggerServiceInterface $errorLoggerService = null,
        ?LoggerInterface $logger = null
    ) {
        $this->scheduledSMSRepository = $scheduledSMSRepository;
        $this->scheduledSMSLogRepository = $scheduledSMSLogRepository;
        $this->senderNameRepository = $senderNameRepository;
        $this->smsSenderService = $smsSenderService;
        $this->realtimeNotificationService = $realtimeNotificationService;
        $this->errorLoggerService = $errorLoggerService;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function executeScheduledSMS(?int $limit = 100): array
    {
        $results = [];
        $dueSMS = $this->scheduledSMSRepository->findDueSMS($limit);

        if (empty($dueSMS)) {
            $this->log('info', 'No scheduled SMS due for execution');
            return ['status' => 'success', 'message' => 'No scheduled SMS due for execution', 'executed' => 0];
        }

        $this->log('info', sprintf('Found %d scheduled SMS due for execution', count($dueSMS)));

        foreach ($dueSMS as $scheduledSMS) {
            try {
                $result = $this->executeOne($scheduledSMS);
                $results[$scheduledSMS->getId()] = $result;
            } catch (Exception $e) {
                $this->log('error', sprintf('Error executing scheduled SMS #%d: %s', $scheduledSMS->getId(), $e->getMessage()));
                $this->logError($e, sprintf('Error executing scheduled SMS #%d', $scheduledSMS->getId()));
                $results[$scheduledSMS->getId()] = [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }

        return [
            'status' => 'success',
            'message' => sprintf('Executed %d scheduled SMS', count($dueSMS)),
            'executed' => count($dueSMS),
            'results' => $results
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function executeSpecificScheduledSMS(int $scheduledSmsId): array
    {
        $scheduledSMS = $this->scheduledSMSRepository->findById($scheduledSmsId);

        if (!$scheduledSMS) {
            $this->log('error', sprintf('Scheduled SMS #%d not found', $scheduledSmsId));
            return ['status' => 'error', 'message' => sprintf('Scheduled SMS #%d not found', $scheduledSmsId)];
        }

        try {
            $result = $this->executeOne($scheduledSMS);
            return [
                'status' => 'success',
                'message' => sprintf('Executed scheduled SMS #%d', $scheduledSmsId),
                'result' => $result
            ];
        } catch (Exception $e) {
            $this->log('error', sprintf('Error executing scheduled SMS #%d: %s', $scheduledSmsId, $e->getMessage()));
            $this->logError($e, sprintf('Error executing scheduled SMS #%d', $scheduledSmsId));
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Exécute un SMS planifié
     * 
     * @param ScheduledSMS $scheduledSMS SMS planifié à exécuter
     * @return array Résultat de l'exécution
     * @throws Exception Si une erreur survient lors de l'exécution
     */
    private function executeOne(ScheduledSMS $scheduledSMS): array
    {
        $this->log('info', sprintf('Executing scheduled SMS #%d: %s', $scheduledSMS->getId(), $scheduledSMS->getName()));

        // Récupérer le nom d'expéditeur
        $senderName = $this->senderNameRepository->findById($scheduledSMS->getSenderNameId());
        if (!$senderName) {
            throw new Exception(sprintf('Sender name #%d not found', $scheduledSMS->getSenderNameId()));
        }

        // Récupérer les destinataires
        $recipientsData = $scheduledSMS->getRecipientsDataAsArray();
        if (empty($recipientsData)) {
            throw new Exception('No recipients found');
        }

        // Préparer les variables pour le log
        $executionDate = date('Y-m-d H:i:s');
        $totalRecipients = count($recipientsData);
        $successfulSends = 0;
        $failedSends = 0;
        $errors = [];

        // Envoyer les SMS
        foreach ($recipientsData as $recipient) {
            try {
                // Vérifier le format du destinataire selon le type de destinataire
                $recipientNumber = $this->getRecipientNumber($recipient, $scheduledSMS->getRecipientsType());

                // Envoyer le SMS
                $this->smsSenderService->sendSMS(
                    $recipientNumber,
                    $scheduledSMS->getMessage(),
                    $senderName->getName()
                );

                $successfulSends++;
            } catch (Exception $e) {
                $failedSends++;
                $errors[] = sprintf('Error sending to %s: %s', json_encode($recipient), $e->getMessage());
                $this->log('error', sprintf(
                    'Error sending scheduled SMS #%d to %s: %s',
                    $scheduledSMS->getId(),
                    json_encode($recipient),
                    $e->getMessage()
                ));
            }
        }

        // Déterminer le statut global
        $status = $this->determineStatus($successfulSends, $failedSends, $totalRecipients);

        // Créer un log d'exécution
        $log = new ScheduledSMSLog(
            0, // ID sera généré par la base de données
            $scheduledSMS->getId(),
            $executionDate,
            $status,
            $totalRecipients,
            $successfulSends,
            $failedSends,
            !empty($errors) ? json_encode($errors) : null
        );
        $this->scheduledSMSLogRepository->save($log);

        // Mettre à jour le SMS planifié
        if ($scheduledSMS->isRecurring()) {
            // Calculer la prochaine date d'exécution pour les SMS récurrents
            $nextRunAt = $scheduledSMS->calculateNextRunDate();
            if ($nextRunAt) {
                $this->scheduledSMSRepository->updateAfterExecution($scheduledSMS->getId(), $nextRunAt);
            } else {
                // Si aucune date suivante n'est calculée, marquer comme terminé
                $this->scheduledSMSRepository->updateStatus($scheduledSMS->getId(), 'completed');
            }
        } else {
            // Pour les SMS non récurrents, marquer comme envoyé
            $this->scheduledSMSRepository->updateAfterExecution($scheduledSMS->getId());
        }

        // Envoyer une notification en temps réel si disponible
        $this->sendNotification($scheduledSMS, $status, $successfulSends, $failedSends, $totalRecipients);

        return [
            'status' => $status,
            'total_recipients' => $totalRecipients,
            'successful_sends' => $successfulSends,
            'failed_sends' => $failedSends,
            'errors' => $errors
        ];
    }

    /**
     * Détermine le statut global de l'exécution
     * 
     * @param int $successfulSends Nombre d'envois réussis
     * @param int $failedSends Nombre d'envois échoués
     * @param int $totalRecipients Nombre total de destinataires
     * @return string Statut ('success', 'partial_success' ou 'failed')
     */
    private function determineStatus(int $successfulSends, int $failedSends, int $totalRecipients): string
    {
        if ($successfulSends === $totalRecipients) {
            return 'success';
        } elseif ($successfulSends > 0) {
            return 'partial_success';
        } else {
            return 'failed';
        }
    }

    /**
     * Récupère le numéro de téléphone du destinataire selon le type de destinataire
     * 
     * @param mixed $recipient Destinataire
     * @param string $recipientType Type de destinataire ('numbers', 'contacts', 'groups')
     * @return string Numéro de téléphone
     * @throws Exception Si le format du destinataire est invalide
     */
    private function getRecipientNumber($recipient, string $recipientType): string
    {
        switch ($recipientType) {
            case 'numbers':
                // Le destinataire est directement un numéro de téléphone
                if (is_string($recipient)) {
                    return $recipient;
                }
                break;
            case 'contacts':
                // Le destinataire est un contact (objet ou ID)
                if (is_array($recipient) && isset($recipient['phone'])) {
                    return $recipient['phone'];
                } elseif (is_object($recipient) && isset($recipient->phone)) {
                    return $recipient->phone;
                }
                break;
            case 'groups':
                // Non géré pour l'instant, nécessiterait de récupérer les contacts du groupe
                throw new Exception('Group recipients not supported yet');
        }

        throw new Exception(sprintf('Invalid recipient format for type %s: %s', $recipientType, json_encode($recipient)));
    }

    /**
     * Envoie une notification en temps réel
     * 
     * @param ScheduledSMS $scheduledSMS SMS planifié
     * @param string $status Statut de l'exécution
     * @param int $successfulSends Nombre d'envois réussis
     * @param int $failedSends Nombre d'envois échoués
     * @param int $totalRecipients Nombre total de destinataires
     */
    private function sendNotification(
        ScheduledSMS $scheduledSMS,
        string $status,
        int $successfulSends,
        int $failedSends,
        int $totalRecipients
    ): void {
        if (!$this->realtimeNotificationService) {
            return;
        }

        $title = sprintf('Scheduled SMS "%s" executed', $scheduledSMS->getName());
        $message = sprintf(
            'Status: %s, Successful: %d/%d, Failed: %d/%d',
            $status,
            $successfulSends,
            $totalRecipients,
            $failedSends,
            $totalRecipients
        );

        try {
            $this->realtimeNotificationService->sendToUser(
                $scheduledSMS->getUserId(),
                'info',
                $message,
                [
                    'title' => $title,
                    'scheduled_sms_id' => $scheduledSMS->getId(),
                    'status' => $status,
                    'type' => 'scheduled_sms_execution'
                ]
            );
        } catch (Exception $e) {
            $this->log('error', sprintf(
                'Error sending notification for scheduled SMS #%d: %s',
                $scheduledSMS->getId(),
                $e->getMessage()
            ));
        }
    }

    /**
     * Journalise un message
     * 
     * @param string $level Niveau de log
     * @param string $message Message à journaliser
     */
    private function log(string $level, string $message): void
    {
        if ($this->logger) {
            $this->logger->log($level, $message);
        }
    }

    /**
     * Journalise une erreur
     * 
     * @param Exception $exception Exception
     * @param string $context Contexte de l'erreur
     */
    private function logError(Exception $exception, string $context): void
    {
        if ($this->errorLoggerService) {
            $this->errorLoggerService->logError(
                $context,
                $exception,
                'error',
                ['service' => 'ScheduledSMSExecutionService']
            );
        }
    }
}
