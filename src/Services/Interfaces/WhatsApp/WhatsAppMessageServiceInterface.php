<?php

namespace App\Services\Interfaces\WhatsApp;

use App\Entities\WhatsApp\WhatsAppMessage;

/**
 * Interface pour le service de gestion des messages WhatsApp
 */
interface WhatsAppMessageServiceInterface
{
    /**
     * Traite un message WhatsApp entrant
     *
     * @param array $message Les données du message
     * @param array $metadata Les métadonnées associées
     * @return WhatsAppMessage
     */
    public function processIncomingMessage(array $message, array $metadata): WhatsAppMessage;

    /**
     * Traite un statut de message WhatsApp
     *
     * @param array $status Les données du statut
     * @param array $metadata Les métadonnées associées
     * @return bool
     */
    public function processMessageStatus(array $status, array $metadata): bool;

    /**
     * Récupère les messages d'un expéditeur
     *
     * @param string $sender
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getMessagesBySender(string $sender, int $limit = 50, int $offset = 0): array;

    /**
     * Récupère les messages d'un destinataire
     *
     * @param string $recipient
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getMessagesByRecipient(string $recipient, int $limit = 50, int $offset = 0): array;

    /**
     * Récupère un message par son ID
     *
     * @param string $messageId
     * @return WhatsAppMessage|null
     */
    public function getMessageById(string $messageId): ?WhatsAppMessage;

    /**
     * Récupère les messages par type
     *
     * @param string $type
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getMessagesByType(string $type, int $limit = 50, int $offset = 0): array;
}