<?php

namespace App\Services\Interfaces;

/**
 * Interface pour le service de notification en temps réel
 */
interface RealtimeNotificationServiceInterface
{
    /**
     * Envoie une notification en temps réel à un utilisateur spécifique
     *
     * @param int $userId ID de l'utilisateur destinataire
     * @param string $type Type de notification (info, success, warning, error)
     * @param string $message Message de la notification
     * @param array $data Données supplémentaires pour la notification
     * @return bool True si l'envoi a réussi, false sinon
     */
    public function sendToUser(int $userId, string $type, string $message, array $data = []): bool;

    /**
     * Envoie une notification en temps réel à tous les administrateurs
     *
     * @param string $type Type de notification (info, success, warning, error)
     * @param string $message Message de la notification
     * @param array $data Données supplémentaires pour la notification
     * @return bool True si l'envoi a réussi, false sinon
     */
    public function sendToAdmins(string $type, string $message, array $data = []): bool;

    /**
     * Envoie une notification en temps réel à tous les utilisateurs
     *
     * @param string $type Type de notification (info, success, warning, error)
     * @param string $message Message de la notification
     * @param array $data Données supplémentaires pour la notification
     * @return bool True si l'envoi a réussi, false sinon
     */
    public function broadcast(string $type, string $message, array $data = []): bool;

    /**
     * Envoie une notification en temps réel à un groupe d'utilisateurs
     *
     * @param array $userIds Liste des IDs des utilisateurs destinataires
     * @param string $type Type de notification (info, success, warning, error)
     * @param string $message Message de la notification
     * @param array $data Données supplémentaires pour la notification
     * @return bool True si l'envoi a réussi, false sinon
     */
    public function sendToGroup(array $userIds, string $type, string $message, array $data = []): bool;

    /**
     * Enregistre un événement administratif qui déclenchera des notifications
     * 
     * @param string $eventType Type d'événement (sender_name_approval, order_completion, etc.)
     * @param array $eventData Données de l'événement
     * @return bool True si l'enregistrement a réussi, false sinon
     */
    public function logAdminEvent(string $eventType, array $eventData): bool;
}
