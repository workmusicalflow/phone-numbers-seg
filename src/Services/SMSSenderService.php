<?php

namespace App\Services;

use App\Services\Interfaces\SMSSenderServiceInterface;
use App\Services\Interfaces\OrangeAPIClientInterface;
use App\Services\Interfaces\SubjectInterface;

/**
 * Service d'envoi de SMS utilisant l'API Orange
 * 
 * Ce service implémente le pattern Observer pour notifier les observateurs
 * lorsqu'un SMS est envoyé ou échoue.
 */
class SMSSenderService implements SMSSenderServiceInterface
{
    /**
     * @var OrangeAPIClientInterface
     */
    private $orangeAPIClient;

    /**
     * @var SubjectInterface
     */
    private $eventManager;

    /**
     * Constructeur
     * 
     * @param OrangeAPIClientInterface $orangeAPIClient
     * @param SubjectInterface $eventManager
     */
    public function __construct(
        OrangeAPIClientInterface $orangeAPIClient,
        SubjectInterface $eventManager
    ) {
        $this->orangeAPIClient = $orangeAPIClient;
        $this->eventManager = $eventManager;
    }

    /**
     * Envoie un SMS à un numéro de téléphone
     *
     * @param string $phoneNumber Numéro de téléphone du destinataire
     * @param string $message Message à envoyer
     * @param string|null $senderName Nom d'expéditeur à utiliser
     * @param int|null $userId ID de l'utilisateur qui envoie le SMS
     * @param string|null $batchId ID du lot pour les envois en masse
     * @param int|null $segmentId ID du segment associé
     * @param int|null $queueId ID de l'élément dans la file d'attente
     * @return array Résultat de l'envoi
     */
    public function sendSMS(string $phoneNumber, string $message, ?string $senderName = null, ?int $userId = null, ?string $batchId = null, ?int $segmentId = null, ?int $queueId = null): array
    {
        try {
            // Envoyer le SMS via l'API Orange
            $result = $this->orangeAPIClient->sendSMS($phoneNumber, $message, $senderName);

            // Préparer les données pour l'événement
            $eventData = [
                'phoneNumber' => $phoneNumber,
                'message' => $message,
                'senderName' => $senderName ?? 'System',
                'senderAddress' => $result['senderAddress'] ?? 'system',
                'messageId' => $result['messageId'] ?? null,
                'userId' => $userId,
                'segmentId' => $segmentId,
                'batchId' => $batchId,
                'queueId' => $queueId
            ];

            // Notifier les observateurs que le SMS a été envoyé
            $this->eventManager->notify('sms.sent', $eventData);

            return [
                'success' => true,
                'messageId' => $result['messageId'] ?? null,
                'senderAddress' => $result['senderAddress'] ?? 'system'
            ];
        } catch (\Exception $e) {
            // Préparer les données pour l'événement d'erreur
            $eventData = [
                'phoneNumber' => $phoneNumber,
                'message' => $message,
                'senderName' => $senderName ?? 'System',
                'error' => $e->getMessage(),
                'userId' => $userId,
                'segmentId' => $segmentId,
                'batchId' => $batchId,
                'queueId' => $queueId
            ];

            // Notifier les observateurs que l'envoi du SMS a échoué
            $this->eventManager->notify('sms.failed', $eventData);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Envoie des SMS en masse
     * 
     * @param array $recipients Liste des destinataires (numéros de téléphone)
     * @param string $message Message à envoyer
     * @param string|null $senderName Nom de l'expéditeur
     * @param int|null $userId ID de l'utilisateur qui envoie le SMS
     * @param string|null $batchId ID du lot pour les envois en masse
     * @param int|null $segmentId ID du segment associé
     * @return array Résultats de l'envoi
     */
    public function sendBulkSMS(
        array $recipients,
        string $message,
        ?string $senderName = null,
        ?int $userId = null,
        ?string $batchId = null,
        ?int $segmentId = null
    ): array {
        $results = [
            'success' => [],
            'failed' => [],
            'total' => count($recipients),
            'successful' => 0,
            'failed_count' => 0
        ];

        foreach ($recipients as $phoneNumber) {
            $result = $this->sendSMS($phoneNumber, $message, $senderName, $userId, $batchId, $segmentId);

            if ($result['success']) {
                $results['success'][] = [
                    'phoneNumber' => $phoneNumber,
                    'messageId' => $result['messageId'] ?? null
                ];
                $results['successful']++;
            } else {
                $results['failed'][] = [
                    'phoneNumber' => $phoneNumber,
                    'error' => $result['error'] ?? 'Failed to send SMS'
                ];
                $results['failed_count']++;
            }
        }

        return $results;
    }
    
    /**
     * Envoie un SMS à tous les numéros de téléphone d'un segment
     *
     * @param int $segmentId ID du segment
     * @param string $message Message à envoyer
     * @param string|null $senderName Nom d'expéditeur à utiliser
     * @param int|null $userId ID de l'utilisateur qui envoie le SMS
     * @return array Résultat de l'envoi
     */
    public function sendSMSToSegment(int $segmentId, string $message, ?string $senderName = null, ?int $userId = null): array
    {
        // Cette méthode n'est pas directement utilisée car SMSQueueService.enqueueSegment() est préférée pour les segments
        // Mais nous l'implémentons pour respecter l'interface
        
        return [
            'success' => false,
            'error' => 'Not directly supported - use SMSQueueService.enqueueSegment() instead'
        ];
    }
}