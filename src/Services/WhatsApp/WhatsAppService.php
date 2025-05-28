<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface;
use App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateHistoryRepositoryInterface;
use App\Entities\User;
use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\Entities\WhatsApp\WhatsAppTemplate;
use App\Entities\WhatsApp\WhatsAppTemplateHistory;
use Psr\Log\LoggerInterface;
use DateTime;

/**
 * Service principal pour gérer les opérations WhatsApp
 * 
 * Ce service implémente les opérations WhatsApp comme l'envoi de messages,
 * la gestion de templates, le traitement des webhooks, etc.
 * 
 * Il utilise d'autres services spécialisés (comme WhatsAppTemplateService)
 * pour des fonctionnalités spécifiques, suivant le principe de séparation des responsabilités.
 */
class WhatsAppService implements WhatsAppServiceInterface
{
    private WhatsAppApiClientInterface $apiClient;
    private WhatsAppMessageHistoryRepositoryInterface $messageRepository;
    private WhatsAppTemplateRepositoryInterface $templateRepository;
    private LoggerInterface $logger;
    private array $config;
    private ?WhatsAppTemplateHistoryRepositoryInterface $templateHistoryRepository;
    private \App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface $templateService;

    public function __construct(
        WhatsAppApiClientInterface $apiClient,
        WhatsAppMessageHistoryRepositoryInterface $messageRepository,
        WhatsAppTemplateRepositoryInterface $templateRepository,
        LoggerInterface $logger,
        array $config,
        \App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface $templateService,
        ?WhatsAppTemplateHistoryRepositoryInterface $templateHistoryRepository = null
    ) {
        $this->apiClient = $apiClient;
        $this->messageRepository = $messageRepository;
        $this->templateRepository = $templateRepository;
        $this->logger = $logger;
        $this->config = $config;
        $this->templateService = $templateService;
        $this->templateHistoryRepository = $templateHistoryRepository;
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

            // Récupérer le template complet pour avoir sa catégorie
            // Rechercher par nom au lieu de template_id (qui n'existe pas dans l'entité)
            $templates = $this->templateRepository->findByAdvancedCriteria(['name' => $templateName], [], 1);
            $template = !empty($templates) ? $templates[0] : null;
            $category = $template ? $template->getCategory() : 'UTILITY';

            // Enregistrer l'utilisation du template dans l'historique dédié
            if ($this->templateHistoryRepository !== null) {
                $this->recordTemplateUsage(
                    $user,
                    $templateName,
                    $recipient,
                    $templateName,
                    $languageCode,
                    $category,
                    $bodyParams,
                    $headerImageUrl ? 'url' : null,
                    $headerImageUrl,
                    null,
                    null,
                    $messageHistory
                );
            }

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
        array $components = [],
        ?string $headerMediaId = null
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

            // Vérifier si nous avons un Media ID à utiliser plutôt qu'une URL dans les composants
            if ($headerMediaId !== null) {
                // Chercher le composant header dans les composants existants
                $headerFound = false;
                foreach ($components as &$component) {
                    if ($component['type'] === 'header' && !empty($component['parameters'])) {
                        foreach ($component['parameters'] as &$param) {
                            if (in_array($param['type'], ['image', 'video', 'document'])) {
                                // Remplacer le lien par l'ID
                                if (isset($param[$param['type']]['link'])) {
                                    unset($param[$param['type']]['link']);
                                }
                                $param[$param['type']]['id'] = $headerMediaId;
                                $headerFound = true;
                                break;
                            }
                        }
                    }
                    if ($headerFound) break;
                }
            }

            // Ajouter les composants si fournis
            if (!empty($components)) {
                $payload['template']['components'] = $components;
            }

            // Envoyer via l'API
            $response = $this->apiClient->sendMessage($payload);

            // Sauvegarder dans l'historique
            $messageHistory = $this->saveMessage(
                $user,
                $recipient,
                WhatsAppMessageHistory::DIRECTION_OUTBOUND,
                WhatsAppMessageHistory::TYPE_TEMPLATE,
                [
                    'template_name' => $templateName,
                    'language' => $languageCode,
                    'components' => $components,
                    'header_media_id' => $headerMediaId
                ],
                $response['messages'][0]['id'] ?? null
            );

            // Extraire les paramètres du corps et les valeurs des boutons
            $bodyParams = [];
            $buttonValues = [];

            foreach ($components as $component) {
                if ($component['type'] === 'body' && isset($component['parameters'])) {
                    foreach ($component['parameters'] as $param) {
                        if ($param['type'] === 'text') {
                            $bodyParams[] = $param['text'];
                        }
                    }
                } else if ($component['type'] === 'buttons' && isset($component['parameters'])) {
                    foreach ($component['parameters'] as $index => $param) {
                        $buttonValues[$index] = $param['text'] ?? '';
                    }
                }
            }

            // Déterminer le type de média d'en-tête
            $headerMediaType = null;
            $headerMediaUrl = null;
            if ($headerMediaId !== null) {
                $headerMediaType = 'id';
            } else {
                // Chercher une URL de média dans les composants
                foreach ($components as $component) {
                    if ($component['type'] === 'header' && isset($component['parameters'])) {
                        foreach ($component['parameters'] as $param) {
                            foreach (['image', 'video', 'document'] as $mediaType) {
                                if ($param['type'] === $mediaType && isset($param[$mediaType]['link'])) {
                                    $headerMediaType = 'url';
                                    $headerMediaUrl = $param[$mediaType]['link'];
                                    break 3;
                                }
                            }
                        }
                    }
                }
            }

            // Récupérer le template complet pour avoir sa catégorie
            // Rechercher par nom au lieu de template_id (qui n'existe pas dans l'entité)
            $templates = $this->templateRepository->findByAdvancedCriteria(['name' => $templateName], [], 1);
            $template = !empty($templates) ? $templates[0] : null;
            $category = $template ? $template->getCategory() : 'UTILITY';

            // Enregistrer l'utilisation du template dans l'historique dédié
            if ($this->templateHistoryRepository !== null) {
                $this->recordTemplateUsage(
                    $user,
                    $templateName,
                    $recipient,
                    $templateName,
                    $languageCode,
                    $category,
                    $bodyParams,
                    $headerMediaType,
                    $headerMediaUrl,
                    $headerMediaId,
                    $buttonValues,
                    $messageHistory
                );
            }

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
     * 
     * @return WhatsAppMessageHistory|null
     */
    private function saveMessage(
        User $user,
        string $phoneNumber,
        string $direction,
        string $type,
        $content,
        ?string $wabaMessageId = null
    ): ?WhatsAppMessageHistory {
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
        
        return $message;
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
    private function findUserByPhoneNumberId(string $_phoneNumberId): ?User
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
    
    /**
     * Récupère les templates WhatsApp approuvés avec une stratégie multi-niveaux
     * 
     * Cette méthode utilise une approche à plusieurs niveaux pour récupérer les templates:
     * 1. D'abord, essaie de récupérer les templates depuis l'API Meta via le WhatsAppTemplateService
     * 2. Si cela échoue ou si useCache=true, essaie de récupérer depuis le cache de la base de données
     * 3. En dernier recours, retourne des templates par défaut
     * 
     * @param User $user L'utilisateur pour lequel récupérer les templates
     * @param array $filters Options de filtrage (name, language, category, status, useCache, forceRefresh)
     * @return array La liste des templates formatés
     */
    public function getApprovedTemplates(User $user, array $filters = []): array
    {
        try {
            $this->logger->info('Récupération des templates WhatsApp approuvés', [
                'user_id' => $user->getId(),
                'filters' => $filters
            ]);
            
            // Paramètres par défaut
            $name = $filters['name'] ?? null;
            $language = $filters['language'] ?? null;
            $category = $filters['category'] ?? null;
            $status = $filters['status'] ?? 'APPROVED';
            $useCache = isset($filters['useCache']) ? (bool)$filters['useCache'] : true;
            $forceRefresh = isset($filters['forceRefresh']) ? (bool)$filters['forceRefresh'] : false;
            
            // Stratégie à plusieurs niveaux avec fallback
            $templates = [];
            
            if ($forceRefresh || !$useCache) {
                // 1. Essayer directement via l'API Meta en utilisant le service injecté
                try {
                    // Utiliser le service injecté plutôt que d'en créer un nouveau
                    $templates = $this->templateService->fetchApprovedTemplatesFromMeta($filters);
                    
                    // Si on a récupéré les templates de l'API, les mettre en cache
                    if (!empty($templates)) {
                        $this->cacheTemplates($templates);
                    }
                    
                    return $this->filterTemplates($templates, $name, $language, $category, $status);
                } catch (\Exception $e) {
                    $this->logger->warning('Erreur récupération templates depuis API Meta, utilisation cache', [
                        'error' => $e->getMessage()
                    ]);
                    
                    // En cas d'erreur, continuer avec le cache
                    if (!$useCache) {
                        throw $e; // Si l'utilisateur ne voulait pas de cache, propager l'erreur
                    }
                }
            }
            
            // 2. Récupérer depuis le cache (base de données)
            if ($useCache) {
                try {
                    // Utiliser la méthode spécifique pour les templates approuvés qui retourne déjà un tableau formaté
                    $templates = $this->templateRepository->findApprovedTemplates($filters);
                    
                    if (!empty($templates)) {
                        return $templates;
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Erreur récupération templates depuis cache', [
                        'error' => $e->getMessage()
                    ]);
                    // Continuer vers le fallback
                }
            }
            
            // 3. Templates de secours (hardcodés)
            $this->logger->warning('Utilisation des templates de secours');
            return $this->getDefaultTemplates($name, $language, $category, $status);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur critique lors de la récupération des templates approuvés', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Même en cas d'erreur critique, retourner une liste vide mais valide
            return [];
        }
    }

    /**
     * Stocke les templates dans le cache (base de données)
     * 
     * @param array $templates Liste des templates à mettre en cache
     */
    private function cacheTemplates(array $templates): void
    {
        foreach ($templates as $templateData) {
            try {
                // Vérifier si le template existe déjà
                $existingTemplate = $this->templateRepository->findByMetaNameAndLanguage(
                    $templateData['name'] ?? '', 
                    $templateData['language'] ?? ''
                );
                
                if ($existingTemplate) {
                    // Mettre à jour le template existant
                    $existingTemplate->setStatus($templateData['status'] ?? 'APPROVED');
                    $existingTemplate->setCategory($templateData['category'] ?? 'UTILITY');
                    
                    if (isset($templateData['components'])) {
                        $existingTemplate->setComponents($templateData['components']);
                        $existingTemplate->setComponentsJson(json_encode($templateData['components']));
                    }
                    
                    $this->templateRepository->save($existingTemplate);
                } else {
                    // Créer un nouveau template
                    $template = new WhatsAppTemplate();
                    $template->setTemplateId($templateData['name'] ?? '');
                    $template->setName($templateData['name'] ?? '');
                    $template->setMetaTemplateName($templateData['name'] ?? '');
                    $template->setLanguageCode($templateData['language'] ?? '');
                    $template->setStatus($templateData['status'] ?? 'APPROVED');
                    $template->setCategory($templateData['category'] ?? 'UTILITY');
                    
                    if (isset($templateData['components'])) {
                        $template->setComponents($templateData['components']);
                        $template->setComponentsJson(json_encode($templateData['components']));
                        
                        // Analyser les composants pour mettre à jour les propriétés dérivées
                        $this->parseComponentsForTemplate($template, $templateData['components']);
                    }
                    
                    $this->templateRepository->save($template);
                }
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la mise en cache du template', [
                    'error' => $e->getMessage(),
                    'template' => $templateData['name'] ?? 'Unknown'
                ]);
                // Continuer avec le template suivant
                continue;
            }
        }
    }
    
    /**
     * Analyse les composants d'un template pour mise à jour des propriétés dérivées
     * 
     * @param WhatsAppTemplate $template Le template à mettre à jour
     * @param array $components Les composants du template
     */
    private function parseComponentsForTemplate(WhatsAppTemplate $template, array $components): void
    {
        $hasMediaHeader = false;
        $hasButtons = false;
        $buttonsCount = 0;
        $bodyVariablesCount = 0;
        $bodyText = '';
        
        foreach ($components as $component) {
            $type = $component['type'] ?? '';
            
            if ($type === 'HEADER' || $type === 'header') {
                $format = $component['format'] ?? '';
                if (in_array($format, ['IMAGE', 'VIDEO', 'DOCUMENT', 'image', 'video', 'document'])) {
                    $hasMediaHeader = true;
                    $template->setHeaderFormat(strtoupper($format));
                } else {
                    $template->setHeaderFormat('TEXT');
                }
            } elseif ($type === 'BODY' || $type === 'body') {
                $text = $component['text'] ?? '';
                if ($text) {
                    $bodyText = $text;
                    // Compter les variables {{1}}, {{2}}, etc.
                    $bodyVariablesCount = preg_match_all('/{{[0-9]+}}/', $text);
                }
            } elseif ($type === 'BUTTONS' || $type === 'buttons') {
                $hasButtons = true;
                $buttonParams = $component['buttons'] ?? [];
                $buttonsCount = count($buttonParams);
            }
        }
        
        $template->setBodyText($bodyText);
        $template->setBodyVariablesCount($bodyVariablesCount);
        $template->setHasMediaHeader($hasMediaHeader);
        $template->setHasButtons($hasButtons);
        $template->setButtonsCount($buttonsCount);
    }
    
    
    /**
     * Filtre les templates selon les critères donnés
     * 
     * @param array $templates Liste des templates à filtrer
     * @param string|null $name Filtre par nom (recherche partielle)
     * @param string|null $language Filtre par langue
     * @param string|null $category Filtre par catégorie
     * @param string|null $status Filtre par statut
     * @return array Liste des templates filtrés
     */
    private function filterTemplates(
        array $templates, 
        ?string $name = null, 
        ?string $language = null, 
        ?string $category = null, 
        ?string $status = null
    ): array {
        // Filtrage si des critères sont spécifiés
        if ($name || $language || $category || $status) {
            $filteredTemplates = [];
            
            foreach ($templates as $template) {
                $include = true;
                
                // Filtre par nom (recherche partielle)
                if ($name && (!isset($template['name']) || stripos($template['name'], $name) === false)) {
                    $include = false;
                }
                
                // Filtre par langue
                if ($language && (!isset($template['language']) || $template['language'] !== $language)) {
                    $include = false;
                }
                
                // Filtre par catégorie
                if ($category && (!isset($template['category']) || $template['category'] !== $category)) {
                    $include = false;
                }
                
                // Filtre par statut
                if ($status && (!isset($template['status']) || $template['status'] !== $status)) {
                    $include = false;
                }
                
                if ($include) {
                    $filteredTemplates[] = $template;
                }
            }
            
            return $filteredTemplates;
        }
        
        return $templates;
    }
    
    /**
     * Fournit des templates par défaut en cas d'absence de templates API/cache
     * 
     * @param string|null $name Filtre par nom (recherche partielle)
     * @param string|null $language Filtre par langue
     * @param string|null $category Filtre par catégorie
     * @param string|null $status Filtre par statut
     * @return array Liste des templates par défaut filtrés
     */
    private function getDefaultTemplates(
        ?string $name = null, 
        ?string $language = null, 
        ?string $category = null, 
        ?string $status = null
    ): array {
        // Templates de secours basiques
        $templates = [
            [
                'id' => 'default_greeting',
                'name' => 'greeting',
                'category' => 'UTILITY',
                'language' => 'fr',
                'status' => 'APPROVED',
                'components' => [
                    [
                        'type' => 'BODY',
                        'text' => 'Bonjour {{1}}! Bienvenue chez nous.'
                    ]
                ],
                'description' => 'Template de salutation pour les nouveaux clients',
                'componentsJson' => json_encode([
                    [
                        'type' => 'BODY',
                        'text' => 'Bonjour {{1}}! Bienvenue chez nous.'
                    ]
                ]),
                'bodyVariablesCount' => 1,
                'hasMediaHeader' => false,
                'hasButtons' => false,
                'buttonsCount' => 0,
                'hasFooter' => false
            ],
            [
                'id' => 'default_support',
                'name' => 'support',
                'category' => 'CUSTOMER_SERVICE',
                'language' => 'fr',
                'status' => 'APPROVED',
                'components' => [
                    [
                        'type' => 'BODY',
                        'text' => 'Bonjour! Notre équipe de support est disponible pour vous aider avec {{1}}. N\'hésitez pas à nous contacter.'
                    ]
                ],
                'description' => 'Template pour le support client',
                'componentsJson' => json_encode([
                    [
                        'type' => 'BODY',
                        'text' => 'Bonjour! Notre équipe de support est disponible pour vous aider avec {{1}}. N\'hésitez pas à nous contacter.'
                    ]
                ]),
                'bodyVariablesCount' => 1,
                'hasMediaHeader' => false,
                'hasButtons' => false,
                'buttonsCount' => 0,
                'hasFooter' => false
            ],
            [
                'id' => 'default_confirmation',
                'name' => 'confirmation',
                'category' => 'UTILITY',
                'language' => 'fr',
                'status' => 'APPROVED',
                'components' => [
                    [
                        'type' => 'HEADER',
                        'format' => 'TEXT',
                        'text' => 'Confirmation'
                    ],
                    [
                        'type' => 'BODY',
                        'text' => 'Votre commande {{1}} a été confirmée. Merci de votre confiance!'
                    ]
                ],
                'description' => 'Template de confirmation de commande',
                'componentsJson' => json_encode([
                    [
                        'type' => 'HEADER',
                        'format' => 'TEXT',
                        'text' => 'Confirmation'
                    ],
                    [
                        'type' => 'BODY',
                        'text' => 'Votre commande {{1}} a été confirmée. Merci de votre confiance!'
                    ]
                ]),
                'bodyVariablesCount' => 1,
                'hasMediaHeader' => false,
                'hasButtons' => false,
                'buttonsCount' => 0,
                'hasFooter' => false
            ],
            [
                'id' => 'default_information',
                'name' => 'information',
                'category' => 'UTILITY',
                'language' => 'fr',
                'status' => 'APPROVED',
                'components' => [
                    [
                        'type' => 'BODY',
                        'text' => 'Information importante : {{1}}'
                    ]
                ],
                'description' => 'Template pour les informations importantes',
                'componentsJson' => json_encode([
                    [
                        'type' => 'BODY',
                        'text' => 'Information importante : {{1}}'
                    ]
                ]),
                'bodyVariablesCount' => 1,
                'hasMediaHeader' => false,
                'hasButtons' => false,
                'buttonsCount' => 0,
                'hasFooter' => false
            ],
            [
                'id' => 'default_promotion',
                'name' => 'promotion',
                'category' => 'MARKETING',
                'language' => 'fr',
                'status' => 'APPROVED',
                'components' => [
                    [
                        'type' => 'HEADER',
                        'format' => 'TEXT',
                        'text' => 'Offre spéciale'
                    ],
                    [
                        'type' => 'BODY',
                        'text' => 'Découvrez notre offre spéciale sur {{1}}. Profitez de {{2}}% de réduction jusqu\'au {{3}}!'
                    ]
                ],
                'description' => 'Template pour les promotions marketing',
                'componentsJson' => json_encode([
                    [
                        'type' => 'HEADER',
                        'format' => 'TEXT',
                        'text' => 'Offre spéciale'
                    ],
                    [
                        'type' => 'BODY',
                        'text' => 'Découvrez notre offre spéciale sur {{1}}. Profitez de {{2}}% de réduction jusqu\'au {{3}}!'
                    ]
                ]),
                'bodyVariablesCount' => 3,
                'hasMediaHeader' => false,
                'hasButtons' => false,
                'buttonsCount' => 0,
                'hasFooter' => false
            ]
        ];
        
        // Ajouter quelques templates en anglais
        $templatesEn = [
            [
                'id' => 'default_greeting_en',
                'name' => 'greeting',
                'category' => 'UTILITY',
                'language' => 'en',
                'status' => 'APPROVED',
                'components' => [
                    [
                        'type' => 'BODY',
                        'text' => 'Hello {{1}}! Welcome to our service.'
                    ]
                ],
                'description' => 'Greeting template for new customers',
                'componentsJson' => json_encode([
                    [
                        'type' => 'BODY',
                        'text' => 'Hello {{1}}! Welcome to our service.'
                    ]
                ]),
                'bodyVariablesCount' => 1,
                'hasMediaHeader' => false,
                'hasButtons' => false,
                'buttonsCount' => 0,
                'hasFooter' => false
            ],
            [
                'id' => 'default_support_en',
                'name' => 'support',
                'category' => 'CUSTOMER_SERVICE',
                'language' => 'en',
                'status' => 'APPROVED',
                'components' => [
                    [
                        'type' => 'BODY',
                        'text' => 'Hello! Our support team is available to help you with {{1}}. Feel free to contact us.'
                    ]
                ],
                'description' => 'Customer support template',
                'componentsJson' => json_encode([
                    [
                        'type' => 'BODY',
                        'text' => 'Hello! Our support team is available to help you with {{1}}. Feel free to contact us.'
                    ]
                ]),
                'bodyVariablesCount' => 1,
                'hasMediaHeader' => false,
                'hasButtons' => false,
                'buttonsCount' => 0,
                'hasFooter' => false
            ]
        ];
        
        // Combiner les templates français et anglais
        $templates = array_merge($templates, $templatesEn);
        
        // Appliquer les filtres
        return $this->filterTemplates($templates, $name, $language, $category, $status);
    }
    
    /**
     * {@inheritdoc}
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
    ): WhatsAppTemplateHistory {
        try {
            if ($this->templateHistoryRepository === null) {
                throw new \RuntimeException("Repository d'historique des templates non configuré");
            }

            // Créer l'entrée d'historique
            $templateHistory = new WhatsAppTemplateHistory();
            $templateHistory->setOracleUser($user);
            $templateHistory->setTemplateId($templateId);
            $templateHistory->setTemplateName($templateName);
            $templateHistory->setLanguage($language);
            $templateHistory->setCategory($category);
            $templateHistory->setPhoneNumber($recipient);
            $templateHistory->setParameters($parameters);
            $templateHistory->setHeaderMediaType($headerMediaType);
            $templateHistory->setHeaderMediaUrl($headerMediaUrl);
            $templateHistory->setHeaderMediaId($headerMediaId);
            $templateHistory->setButtonValues($buttonValues);
            $templateHistory->setStatus('sent');
            $templateHistory->setUsedAt(new \DateTime());

            // Lier au message d'historique si fourni
            if ($messageHistory !== null) {
                $templateHistory->setMessageHistory($messageHistory);
                $templateHistory->setWabaMessageId($messageHistory->getWabaMessageId());
            }

            // Lier au template si on peut le trouver
            // Rechercher par nom au lieu de template_id (qui n'existe pas dans l'entité)
            $templates = $this->templateRepository->findByAdvancedCriteria(['name' => $templateId], [], 1);
            $template = !empty($templates) ? $templates[0] : null;
            if ($template !== null) {
                $templateHistory->setTemplate($template);
            }

            // Sauvegarder l'historique
            $this->templateHistoryRepository->save($templateHistory);

            return $templateHistory;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'enregistrement de l\'utilisation du template', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId(),
                'template_id' => $templateId
            ]);

            // Créer un objet même en cas d'erreur pour que l'application puisse continuer
            $fallbackHistory = new WhatsAppTemplateHistory();
            $fallbackHistory->setOracleUser($user);
            $fallbackHistory->setTemplateId($templateId);
            $fallbackHistory->setTemplateName($templateName);
            $fallbackHistory->setLanguage($language);
            $fallbackHistory->setCategory($category);
            $fallbackHistory->setPhoneNumber($recipient);
            $fallbackHistory->setStatus('error');
            $fallbackHistory->setUsedAt(new \DateTime());
            
            return $fallbackHistory;
        }
    }
}