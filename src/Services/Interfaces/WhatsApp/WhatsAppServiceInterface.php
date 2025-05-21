<?php

declare(strict_types=1);

namespace App\Services\Interfaces\WhatsApp;

use App\Entities\User;
use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\Entities\WhatsApp\WhatsAppTemplateHistory;

/**
 * Interface pour le service WhatsApp
 */
interface WhatsAppServiceInterface
{
    /**
     * Envoyer un message WhatsApp générique
     * 
     * @param User $user
     * @param string $recipient
     * @param string $type
     * @param string|null $content
     * @param string|null $mediaUrl
     * @return WhatsAppMessageHistory
     */
    public function sendMessage(
        User $user,
        string $recipient,
        string $type,
        ?string $content = null,
        ?string $mediaUrl = null
    ): WhatsAppMessageHistory;

    /**
     * Envoyer un message template WhatsApp avec API simplifiée
     * 
     * @param User $user
     * @param string $recipient
     * @param string $templateName
     * @param string $languageCode
     * @param string|null $headerImageUrl
     * @param array $bodyParams
     * @return WhatsAppMessageHistory
     */
    public function sendTemplateMessage(
        User $user,
        string $recipient,
        string $templateName,
        string $languageCode,
        ?string $headerImageUrl = null,
        array $bodyParams = []
    ): WhatsAppMessageHistory;

    /**
     * Envoyer un message texte
     * 
     * @param User $user
     * @param string $recipient
     * @param string $message
     * @param string|null $contextMessageId
     * @return array
     */
    public function sendTextMessage(
        User $user,
        string $recipient,
        string $message,
        ?string $contextMessageId = null
    ): array;

    /**
     * Envoyer un message template avec composants détaillés
     * 
     * @param User $user
     * @param string $recipient
     * @param string $templateName
     * @param string $languageCode
     * @param array $components
     * @param string|null $headerMediaId
     * @return array
     */
    public function sendTemplateMessageWithComponents(
        User $user,
        string $recipient,
        string $templateName,
        string $languageCode,
        array $components = [],
        ?string $headerMediaId = null
    ): array;

    /**
     * Envoyer un message média
     * 
     * @param User $user
     * @param string $recipient
     * @param string $type
     * @param string $mediaIdOrUrl
     * @param string|null $caption
     * @return array
     */
    public function sendMediaMessage(
        User $user,
        string $recipient,
        string $type,
        string $mediaIdOrUrl,
        ?string $caption = null
    ): array;

    /**
     * Envoyer un message interactif
     * 
     * @param User $user
     * @param string $recipient
     * @param array $interactive
     * @return array
     */
    public function sendInteractiveMessage(
        User $user,
        string $recipient,
        array $interactive
    ): array;

    /**
     * Marquer un message comme lu
     * 
     * @param User $user
     * @param string $messageId
     * @return bool
     */
    public function markAsRead(User $user, string $messageId): bool;

    /**
     * Obtenir l'historique des messages
     * 
     * @param User $user
     * @param string|null $phoneNumber
     * @param string|null $status
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getMessageHistory(
        User $user,
        ?string $phoneNumber = null,
        ?string $status = null,
        int $limit = 100,
        int $offset = 0
    ): array;

    /**
     * Traiter un message webhook entrant
     * 
     * @param array $webhookData
     * @return void
     */
    public function processWebhookMessage(array $webhookData): void;

    /**
     * Uploader un média
     * 
     * @param User $user
     * @param string $filePath
     * @param string $mimeType
     * @return string ID du média
     */
    public function uploadMedia(User $user, string $filePath, string $mimeType): string;

    /**
     * Télécharger un média
     * 
     * @param User $user
     * @param string $mediaId
     * @return array
     */
    public function downloadMedia(User $user, string $mediaId): array;

    /**
     * Obtenir l'URL d'un média
     * 
     * @param User $user
     * @param string $mediaId
     * @return string
     */
    public function getMediaUrl(User $user, string $mediaId): string;

    /**
     * Traiter un webhook entrant
     * 
     * @param array $payload
     * @return void
     */
    public function processWebhook(array $payload): void;

    /**
     * Vérifier le webhook
     * 
     * @param string $mode
     * @param string $challenge
     * @param string $verifyToken
     * @return string|null
     */
    public function verifyWebhook(string $mode, string $challenge, string $verifyToken): ?string;

    /**
     * Récupère les templates WhatsApp approuvés pour un utilisateur.
     *
     * @param User $user L'utilisateur pour lequel récupérer les templates.
     * @return array La liste des templates, potentiellement vide.
     *               Chaque template pourrait être un tableau associatif ou un objet.
     */
    public function getUserTemplates(User $user): array;
    
    /**
     * Récupère les templates WhatsApp approuvés avec des options de filtrage.
     *
     * @param User $user L'utilisateur pour lequel récupérer les templates.
     * @param array $filters Options de filtrage (name, language, category, status, useCache, forceRefresh).
     * @return array La liste des templates, potentiellement vide.
     */
    public function getApprovedTemplates(User $user, array $filters = []): array;

    /**
     * Enregistre l'utilisation d'un template dans l'historique
     * 
     * @param User $user L'utilisateur qui utilise le template
     * @param string $templateId L'ID du template
     * @param string $recipient Le numéro de téléphone du destinataire
     * @param string $templateName Le nom du template
     * @param string $language Le code de langue du template
     * @param string $category La catégorie du template
     * @param array|null $parameters Les valeurs des paramètres du corps
     * @param string|null $headerMediaType Le type de média d'en-tête (url, id)
     * @param string|null $headerMediaUrl L'URL du média d'en-tête
     * @param string|null $headerMediaId L'ID du média d'en-tête
     * @param array|null $buttonValues Les valeurs des boutons
     * @param WhatsAppMessageHistory|null $messageHistory Le message associé à cette utilisation
     * @return WhatsAppTemplateHistory L'entrée d'historique créée
     */
    public function recordTemplateUsage(
        User $user,
        string $templateId,
        string $recipient,
        string $templateName,
        string $language,
        string $category,
        ?array $parameters = null,
        ?string $headerMediaType = null,
        ?string $headerMediaUrl = null,
        ?string $headerMediaId = null,
        ?array $buttonValues = null,
        ?WhatsAppMessageHistory $messageHistory = null
    ): WhatsAppTemplateHistory;
}