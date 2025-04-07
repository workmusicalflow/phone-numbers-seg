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
     * @return bool Succès de l'envoi
     */
    public function sendSMS(string $phoneNumber, string $message, ?string $senderName = null): bool
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
                'messageId' => $result['messageId'] ?? null
            ];

            // Notifier les observateurs que le SMS a été envoyé
            $this->eventManager->notify('sms.sent', $eventData);

            return true;
        } catch (\Exception $e) {
            // Préparer les données pour l'événement d'erreur
            $eventData = [
                'phoneNumber' => $phoneNumber,
                'message' => $message,
                'senderName' => $senderName ?? 'System',
                'error' => $e->getMessage()
            ];

            // Notifier les observateurs que l'envoi du SMS a échoué
            $this->eventManager->notify('sms.failed', $eventData);

            return false;
        }
    }

    /**
     * Envoie des SMS en masse
     * 
     * @param array $recipients Liste des destinataires (numéros de téléphone)
     * @param string $message Message à envoyer
     * @param string|null $senderName Nom de l'expéditeur
     * @return array Résultats de l'envoi
     */
    public function sendBulk(
        array $recipients,
        string $message,
        ?string $senderName = null
    ): array {
        $results = [
            'success' => [],
            'failed' => [],
            'total' => count($recipients),
            'successful' => 0,
            'failed_count' => 0
        ];

        foreach ($recipients as $phoneNumber) {
            $success = $this->sendSMS($phoneNumber, $message, $senderName);

            if ($success) {
                $results['success'][] = [
                    'phoneNumber' => $phoneNumber
                ];
                $results['successful']++;
            } else {
                $results['failed'][] = [
                    'phoneNumber' => $phoneNumber,
                    'error' => 'Failed to send SMS'
                ];
                $results['failed_count']++;
            }
        }

        return $results;
    }
}
