<?php

namespace App\Services\Interfaces;

/**
 * Interface pour le service d'envoi de SMS
 */
interface SMSSenderServiceInterface
{
    /**
     * Envoie un SMS à un numéro de téléphone
     *
     * @param string $phoneNumber Numéro de téléphone du destinataire
     * @param string $message Message à envoyer
     * @param string|null $senderName Nom d'expéditeur à utiliser
     * @param int|null $userId ID de l'utilisateur qui envoie le SMS
     * @return array Résultat de l'envoi
     */
    public function sendSMS(string $phoneNumber, string $message, ?string $senderName = null, ?int $userId = null): array;

    /**
     * Envoie un SMS à plusieurs numéros de téléphone
     *
     * @param array $phoneNumbers Numéros de téléphone des destinataires
     * @param string $message Message à envoyer
     * @param string|null $senderName Nom d'expéditeur à utiliser
     * @param int|null $userId ID de l'utilisateur qui envoie le SMS
     * @return array Résultat de l'envoi
     */
    public function sendBulkSMS(array $phoneNumbers, string $message, ?string $senderName = null, ?int $userId = null): array;

    /**
     * Envoie un SMS à tous les numéros de téléphone d'un segment
     *
     * @param int $segmentId ID du segment
     * @param string $message Message à envoyer
     * @param string|null $senderName Nom d'expéditeur à utiliser
     * @param int|null $userId ID de l'utilisateur qui envoie le SMS
     * @return array Résultat de l'envoi
     */
    public function sendSMSToSegment(int $segmentId, string $message, ?string $senderName = null, ?int $userId = null): array;
}
