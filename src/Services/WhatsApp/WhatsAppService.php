<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface;
use App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface;
use App\Entities\User;
use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\Entities\WhatsApp\WhatsAppTemplate;
use Psr\Log\LoggerInterface;
use DateTime;

/**
 * Service principal pour gérer les opérations WhatsApp
 */
class WhatsAppService implements WhatsAppServiceInterface
{
    private WhatsAppApiClientInterface $apiClient;
    private WhatsAppMessageHistoryRepositoryInterface $messageRepository;
    private WhatsAppTemplateRepositoryInterface $templateRepository;
    private LoggerInterface $logger;
    private array $config;

    public function __construct(
        WhatsAppApiClientInterface $apiClient,
        WhatsAppMessageHistoryRepositoryInterface $messageRepository,
        WhatsAppTemplateRepositoryInterface $templateRepository,
        LoggerInterface $logger,
        array $config
    ) {
        $this->apiClient = $apiClient;
        $this->messageRepository = $messageRepository;
        $this->templateRepository = $templateRepository;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Envoie un message WhatsApp générique
     */
    public function sendMessage(
        User $user,
        string $recipient,
        string $type,
        ?string $content = null,
        ?string $mediaUrl = null
    ): WhatsAppMessageHistory {
        try {
            $normalizedRecipient = $this->normalizePhoneNumber($recipient);
            $result = null;

            switch ($type) {
                case 'text':
                    $payload = [
                        'messaging_product' => 'whatsapp',
                        'to' => $normalizedRecipient,
                        'type' => 'text',
                        'text' => [
                            'body' => $content ?? ''
                        ]
                    ];
                    $result = $this->apiClient->sendMessage($payload);
                    break;

                case 'image':
                case 'video':
                case 'audio':
                case 'document':
                    $payload = [
                        'messaging_product' => 'whatsapp',
                        'to' => $normalizedRecipient,
                        'type' => $type,
                        $type => [
                            'link' => $mediaUrl ?? ''
                        ]
                    ];
                    if ($content) {
                        $payload[$type]['caption'] = $content;
                    }
                    $result = $this->apiClient->sendMessage($payload);
                    break;

                default:
                    throw new \InvalidArgumentException("Type de message non supporté: $type");
            }

            // Créer l'historique
            $messageHistory = new WhatsAppMessageHistory();
            $messageHistory->setOracleUser($user);
            $wabaMessageId = $result['messages'][0]['id'] ?? null;
            if ($wabaMessageId) {
                $messageHistory->setWabaMessageId($wabaMessageId);
            }
            $messageHistory->setPhoneNumber($recipient);
            $messageHistory->setDirection('OUTGOING');
            $messageHistory->setType($type);
            $messageHistory->setContent($content);
            $messageHistory->setStatus('sent');
            $messageHistory->setTimestamp(new DateTime());
            if ($mediaUrl) {
                $messageHistory->setMediaId($mediaUrl);
            }

            // Debug log
            error_log("WhatsApp Service - Before save - phoneNumber: " . $messageHistory->getPhoneNumber());

            // Sauvegarder
            $this->messageRepository->save($messageHistory);

            // Debug log après save
            error_log("WhatsApp Service - After save - phoneNumber: " . $messageHistory->getPhoneNumber());
            error_log("WhatsApp Service - After save - id: " . $messageHistory->getId());

            return $messageHistory;
        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi message WhatsApp', [
                'user_id' => $user->getId(),
                'recipient' => $recipient,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Envoie un message template WhatsApp
     */
    public function sendTemplateMessage(
        User $user,
        string $recipient,
        string $templateName,
        string $languageCode,
        ?string $headerImageUrl = null,
        array $bodyParams = []
    ): WhatsAppMessageHistory {
        try {
            $components = [];

            // Ajout de l'image d'en-tête si fournie
            if ($headerImageUrl) {
                $components[] = [
                    'type' => 'header',
                    'parameters' => [
                        [
                            'type' => 'image',
                            'image' => [
                                'link' => $headerImageUrl
                            ]
                        ]
                    ]
                ];
            }

            // Ajout des paramètres du corps si fournis
            if (!empty($bodyParams)) {
                $bodyParameters = [];
                foreach ($bodyParams as $param) {
                    $bodyParameters[] = [
                        'type' => 'text',
                        'text' => $param
                    ];
                }

                $components[] = [
                    'type' => 'body',
                    'parameters' => $bodyParameters
                ];
            }

            // Construire le payload pour le template
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $this->normalizePhoneNumber($recipient),
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => $languageCode
                    ]
                ]
            ];

            // Ajouter les composants si présents
            if (!empty($components)) {
                $payload['template']['components'] = $components;
            }

            // Envoyer le message via l'API client
            $result = $this->apiClient->sendMessage($payload);

            // Créer l'historique du message
            $messageHistory = new WhatsAppMessageHistory();
            $messageHistory->setOracleUser($user);
            $messageHistory->setWabaMessageId($result['messages'][0]['id'] ?? '');
            $messageHistory->setPhoneNumber($recipient);
            $messageHistory->setDirection('OUTGOING');
            $messageHistory->setType('template');
            $messageHistory->setStatus('sent');
            $messageHistory->setTemplateName($templateName);
            $messageHistory->setTemplateLanguage($languageCode);
            $messageHistory->setContent(json_encode([
                'template' => $templateName,
                'language' => $languageCode,
                'components' => $components
            ]));
            $messageHistory->setTimestamp(new DateTime());

            // Sauvegarder l'historique
            $this->messageRepository->save($messageHistory);

            return $messageHistory;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi du template WhatsApp', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId(),
                'recipient' => $recipient,
                'template' => $templateName
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sendTextMessage(
        User $user,
        string $recipient,
        string $message,
        ?string $contextMessageId = null
    ): array {
        try {
            // Construire le payload
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $this->normalizePhoneNumber($recipient),
                'type' => 'text',
                'text' => [
                    'body' => $message
                ]
            ];

            // Ajouter le contexte si fourni
            if ($contextMessageId !== null) {
                $payload['context'] = [
                    'message_id' => $contextMessageId
                ];
            }

            // Envoyer via l'API
            $response = $this->apiClient->sendMessage($payload);

            // Sauvegarder dans l'historique
            $this->saveMessage(
                $user,
                $recipient,
                WhatsAppMessageHistory::DIRECTION_OUTBOUND,
                WhatsAppMessageHistory::TYPE_TEXT,
                $message,
                $response['messages'][0]['id'] ?? null
            );

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi message texte WhatsApp', [
                'user_id' => $user->getId(),
                'recipient' => $recipient,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sendTemplateMessageWithComponents(
        User $user,
        string $recipient,
        string $templateName,
        string $languageCode,
        array $components = []
    ): array {
        try {
            // Utilisation de l'approche API directe - pas de vérification en base de données locale
            // Construire le payload
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $this->normalizePhoneNumber($recipient),
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => $languageCode
                    ]
                ]
            ];

            // Ajouter les composants si fournis
            if (!empty($components)) {
                $payload['template']['components'] = $components;
            }

            // Envoyer via l'API
            $response = $this->apiClient->sendMessage($payload);

            // Sauvegarder dans l'historique
            $this->saveMessage(
                $user,
                $recipient,
                WhatsAppMessageHistory::DIRECTION_OUTBOUND,
                WhatsAppMessageHistory::TYPE_TEMPLATE,
                [
                    'template_name' => $templateName,
                    'language' => $languageCode,
                    'components' => $components
                ],
                $response['messages'][0]['id'] ?? null
            );

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi template WhatsApp', [
                'user_id' => $user->getId(),
                'recipient' => $recipient,
                'template' => $templateName,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sendMediaMessage(
        User $user,
        string $recipient,
        string $type,
        string $mediaIdOrUrl,
        ?string $caption = null
    ): array {
        try {
            // Valider le type de média
            $validTypes = ['image', 'video', 'audio', 'document'];
            if (!in_array($type, $validTypes)) {
                throw new \InvalidArgumentException("Type de média invalide : $type");
            }

            // Construire le payload
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $this->normalizePhoneNumber($recipient),
                'type' => $type,
                $type => []
            ];

            // Ajouter l'ID ou l'URL du média
            if (strpos($mediaIdOrUrl, 'http') === 0) {
                $payload[$type]['link'] = $mediaIdOrUrl;
            } else {
                $payload[$type]['id'] = $mediaIdOrUrl;
            }

            // Ajouter la légende si applicable
            if ($caption !== null && in_array($type, ['image', 'video', 'document'])) {
                $payload[$type]['caption'] = $caption;
            }

            // Envoyer via l'API
            $response = $this->apiClient->sendMessage($payload);

            // Sauvegarder dans l'historique
            $this->saveMessage(
                $user,
                $recipient,
                WhatsAppMessageHistory::DIRECTION_OUTBOUND,
                $this->mapMediaTypeToHistoryType($type),
                [
                    'media_id' => str_contains($mediaIdOrUrl, 'http') ? null : $mediaIdOrUrl,
                    'media_url' => str_contains($mediaIdOrUrl, 'http') ? $mediaIdOrUrl : null,
                    'caption' => $caption
                ],
                $response['messages'][0]['id'] ?? null
            );

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi média WhatsApp', [
                'user_id' => $user->getId(),
                'recipient' => $recipient,
                'type' => $type,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sendInteractiveMessage(
        User $user,
        string $recipient,
        array $interactive
    ): array {
        try {
            // Construire le payload
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $this->normalizePhoneNumber($recipient),
                'type' => 'interactive',
                'interactive' => $interactive
            ];

            // Envoyer via l'API
            $response = $this->apiClient->sendMessage($payload);

            // Sauvegarder dans l'historique
            $this->saveMessage(
                $user,
                $recipient,
                WhatsAppMessageHistory::DIRECTION_OUTBOUND,
                WhatsAppMessageHistory::TYPE_INTERACTIVE,
                $interactive,
                $response['messages'][0]['id'] ?? null
            );

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi message interactif WhatsApp', [
                'user_id' => $user->getId(),
                'recipient' => $recipient,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function markAsRead(User $user, string $messageId): bool
    {
        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'status' => 'read',
                'message_id' => $messageId
            ];

            $this->apiClient->sendMessage($payload);

            // Mettre à jour le statut dans l'historique si le message existe
            $message = $this->messageRepository->findOneBy(['wabaMessageId' => $messageId]);
            if ($message) {
                $message->setStatus(WhatsAppMessageHistory::STATUS_READ);
                $this->messageRepository->save($message);
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur marquage message comme lu', [
                'user_id' => $user->getId(),
                'message_id' => $messageId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageHistory(
        User $user,
        ?string $phoneNumber = null,
        ?string $status = null,
        int $limit = 100,
        int $offset = 0
    ): array {
        $criteria = ['oracle_user_id' => $user->getId()];

        if ($phoneNumber !== null) {
            $criteria['phoneNumber'] = $phoneNumber;
        }

        if ($status !== null) {
            $criteria['status'] = $status;
        }

        return $this->messageRepository->findBy(
            $criteria,
            ['timestamp' => 'DESC'],
            $limit,
            $offset
        );
    }

    /**
     * {@inheritdoc}
     */
    public function processWebhookMessage(array $webhookData): void
    {
        try {
            $entry = $webhookData['entry'][0] ?? null;
            if ($entry === null) {
                return;
            }

            $changes = $entry['changes'][0] ?? null;
            if ($changes === null || $changes['field'] !== 'messages') {
                return;
            }

            $value = $changes['value'];
            $messages = $value['messages'] ?? [];
            $statuses = $value['statuses'] ?? [];

            // Traiter les messages entrants
            foreach ($messages as $message) {
                $this->processIncomingMessage($message, $value['metadata']['phone_number_id']);
            }

            // Traiter les mises à jour de statut
            foreach ($statuses as $status) {
                $this->processStatusUpdate($status);
            }
        } catch (\Exception $e) {
            $this->logger->error('Erreur traitement webhook WhatsApp', [
                'error' => $e->getMessage(),
                'data' => $webhookData
            ]);
        }
    }

    /**
     * Normalise un numéro de téléphone pour l'API WhatsApp
     */
    private function normalizePhoneNumber(string $phoneNumber): string
    {
        // Supprimer tous les caractères non numériques
        $normalized = preg_replace('/[^0-9]/', '', $phoneNumber);

        // S'assurer que le numéro commence par un code pays
        if (strpos($normalized, '225') !== 0) {
            $normalized = '225' . $normalized;
        }

        return $normalized;
    }

    /**
     * Sauvegarde un message dans l'historique
     */
    private function saveMessage(
        User $user,
        string $phoneNumber,
        string $direction,
        string $type,
        $content,
        ?string $wabaMessageId = null
    ): void {
        $message = new WhatsAppMessageHistory();
        $message->setOracleUser($user);
        $message->setPhoneNumber($phoneNumber);
        $message->setDirection($direction);
        $message->setType($type);

        if (is_array($content)) {
            $message->setContent(json_encode($content));
        } else {
            $message->setContent((string)$content);
        }

        if ($wabaMessageId !== null) {
            $message->setWabaMessageId($wabaMessageId);
        }

        $message->setStatus(WhatsAppMessageHistory::STATUS_SENT);
        $message->setTimestamp(new \DateTime());

        $this->messageRepository->save($message);
    }

    /**
     * Mappe le type de média vers le type d'historique
     */
    private function mapMediaTypeToHistoryType(string $mediaType): string
    {
        return match ($mediaType) {
            'image' => WhatsAppMessageHistory::TYPE_IMAGE,
            'video' => WhatsAppMessageHistory::TYPE_VIDEO,
            'audio' => WhatsAppMessageHistory::TYPE_AUDIO,
            'document' => WhatsAppMessageHistory::TYPE_DOCUMENT,
            default => throw new \InvalidArgumentException("Type de média non supporté : $mediaType")
        };
    }

    /**
     * Traite un message entrant du webhook
     */
    private function processIncomingMessage(array $message, string $phoneNumberId): void
    {
        $messageHistory = new WhatsAppMessageHistory();
        $messageHistory->setWabaMessageId($message['id']);
        $messageHistory->setPhoneNumber($message['from']);
        $messageHistory->setDirection(WhatsAppMessageHistory::DIRECTION_INBOUND);
        $messageHistory->setType($message['type']);
        $messageHistory->setTimestamp(\DateTime::createFromFormat('U', $message['timestamp']));
        $messageHistory->setStatus(WhatsAppMessageHistory::STATUS_RECEIVED);

        // Contenu selon le type
        switch ($message['type']) {
            case 'text':
                $messageHistory->setContent($message['text']['body']);
                break;

            case 'image':
            case 'video':
            case 'audio':
            case 'document':
                $mediaData = $message[$message['type']];
                $messageHistory->setMediaId($mediaData['id']);
                if (isset($mediaData['caption'])) {
                    $messageHistory->setContent($mediaData['caption']);
                }
                break;

            case 'interactive':
                $messageHistory->setContent(json_encode($message['interactive']));
                break;

            default:
                $messageHistory->setContent(json_encode($message));
        }

        // Déterminer l'utilisateur Oracle associé
        // Pour l'instant, on utilise le phone_number_id du webhook
        // Dans une vraie implémentation, il faudrait mapper ceci à un utilisateur spécifique
        $user = $this->findUserByPhoneNumberId($phoneNumberId);
        if ($user !== null) {
            $messageHistory->setOracleUser($user);
            $this->messageRepository->save($messageHistory);
        }
    }

    /**
     * Traite une mise à jour de statut du webhook
     */
    private function processStatusUpdate(array $status): void
    {
        $wabaMessageId = $status['id'];
        $newStatus = $status['status'];

        // Mapper le statut WhatsApp vers notre statut interne
        $internalStatus = match ($newStatus) {
            'sent' => WhatsAppMessageHistory::STATUS_SENT,
            'delivered' => WhatsAppMessageHistory::STATUS_DELIVERED,
            'read' => WhatsAppMessageHistory::STATUS_READ,
            'failed' => WhatsAppMessageHistory::STATUS_FAILED,
            default => WhatsAppMessageHistory::STATUS_SENT
        };

        // Mettre à jour le message dans l'historique
        $message = $this->messageRepository->findOneBy(['wabaMessageId' => $wabaMessageId]);
        if ($message !== null) {
            $message->setStatus($internalStatus);

            // Ajouter les informations d'erreur si le message a échoué
            if ($newStatus === 'failed' && isset($status['errors'])) {
                $error = $status['errors'][0] ?? [];
                $message->setErrorCode($error['code'] ?? null);
                $message->setErrorMessage($error['message'] ?? null);
            }

            $this->messageRepository->save($message);
        }
    }

    /**
     * Trouve un utilisateur Oracle basé sur l'ID du numéro de téléphone WhatsApp
     * 
     * Pour l'instant, cette méthode retourne null car la logique de mapping
     * doit être implémentée selon les besoins spécifiques du projet
     */
    private function findUserByPhoneNumberId(string $phoneNumberId): ?User
    {
        // TODO: Implémenter la logique pour trouver l'utilisateur
        // basé sur le phone_number_id de WhatsApp
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function uploadMedia(User $user, string $filePath, string $mimeType): string
    {
        try {
            return $this->apiClient->uploadMedia($filePath, $mimeType);
        } catch (\Exception $e) {
            $this->logger->error('Erreur upload média WhatsApp', [
                'user_id' => $user->getId(),
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function downloadMedia(User $user, string $mediaId): array
    {
        try {
            return $this->apiClient->downloadMedia($mediaId);
        } catch (\Exception $e) {
            $this->logger->error('Erreur téléchargement média WhatsApp', [
                'user_id' => $user->getId(),
                'media_id' => $mediaId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaUrl(User $user, string $mediaId): string
    {
        try {
            return $this->apiClient->getMediaUrl($mediaId);
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération URL média WhatsApp', [
                'user_id' => $user->getId(),
                'media_id' => $mediaId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processWebhook(array $payload): void
    {
        $this->processWebhookMessage($payload);
    }

    /**
     * {@inheritdoc}
     */
    public function verifyWebhook(string $mode, string $challenge, string $verifyToken): ?string
    {
        if ($mode === 'subscribe' && $verifyToken === $this->config['webhook_verify_token']) {
            return $challenge;
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserTemplates(User $user): array
    {
        // Approche API directe : récupérer les templates directement depuis l'API Meta
        try {
            $templateService = new WhatsAppTemplateService($this->apiClient, $this->templateRepository, $this->logger);
            return $templateService->fetchApprovedTemplatesFromMeta();
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération templates WhatsApp pour utilisateur', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
