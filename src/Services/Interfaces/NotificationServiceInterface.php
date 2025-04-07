<?php

namespace App\Services\Interfaces;

/**
 * Interface pour le service de notification
 */
interface NotificationServiceInterface
{
    /**
     * Envoie une notification par SMS
     *
     * @param string $phoneNumber Numéro de téléphone du destinataire
     * @param string $message Message à envoyer
     * @return bool True si l'envoi a réussi, false sinon
     */
    public function sendSMSNotification(string $phoneNumber, string $message): bool;

    /**
     * Envoie une notification par email
     *
     * @param string $email Adresse email du destinataire
     * @param string $subject Sujet de l'email
     * @param string $message Message à envoyer
     * @param array $data Données supplémentaires pour le template
     * @return bool True si l'envoi a réussi, false sinon
     */
    public function sendEmailNotification(string $email, string $subject, string $message, array $data = []): bool;

    /**
     * Envoie une notification par SMS et par email
     *
     * @param string $phoneNumber Numéro de téléphone du destinataire
     * @param string $email Adresse email du destinataire
     * @param string $subject Sujet de l'email
     * @param string $message Message à envoyer
     * @param array $data Données supplémentaires pour le template
     * @return array Tableau associatif avec les résultats des envois ['sms' => bool, 'email' => bool]
     */
    public function sendMultiChannelNotification(
        string $phoneNumber,
        string $email,
        string $subject,
        string $message,
        array $data = []
    ): array;
}
