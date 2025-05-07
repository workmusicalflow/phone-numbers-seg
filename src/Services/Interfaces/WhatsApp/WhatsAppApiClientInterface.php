<?php

namespace App\Services\Interfaces\WhatsApp;

/**
 * Interface pour le client API WhatsApp
 */
interface WhatsAppApiClientInterface
{
    /**
     * Envoie un message texte WhatsApp
     *
     * @param string $to Numéro de téléphone du destinataire
     * @param string $message Contenu du message
     * @return array Réponse de l'API
     */
    public function sendTextMessage(string $to, string $message): array;

    /**
     * Envoie un message template WhatsApp
     *
     * @param string $to Numéro de téléphone du destinataire
     * @param string $templateName Nom du template
     * @param string $languageCode Code de langue pour le template
     * @param array $parameters Paramètres du template
     * @return array Réponse de l'API
     */
    public function sendTemplateMessage(string $to, string $templateName, string $languageCode, array $parameters = []): array;

    /**
     * Envoie un message image WhatsApp
     *
     * @param string $to Numéro de téléphone du destinataire
     * @param string $imageUrl URL de l'image
     * @param string|null $caption Légende optionnelle
     * @return array Réponse de l'API
     */
    public function sendImageMessage(string $to, string $imageUrl, ?string $caption = null): array;

    /**
     * Télécharge un média depuis l'API WhatsApp
     *
     * @param string $mediaId ID du média à télécharger
     * @return string|null Contenu du média ou null en cas d'échec
     */
    public function downloadMedia(string $mediaId): ?string;

    /**
     * Marque un message comme lu
     *
     * @param string $messageId ID du message à marquer comme lu
     * @return bool Succès de l'opération
     */
    public function markMessageAsRead(string $messageId): bool;
}