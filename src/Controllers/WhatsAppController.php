<?php

namespace App\Controllers;

use App\Entities\User;
use App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * WhatsAppController
 * 
 * Contrôleur REST pour les opérations liées à WhatsApp
 */
class WhatsAppController
{
    /**
     * @var WhatsAppServiceInterface
     */
    private WhatsAppServiceInterface $whatsAppService;

    /**
     * @var WhatsAppTemplateRepositoryInterface
     */
    private WhatsAppTemplateRepositoryInterface $templateRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    
    /**
     * @var \App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface
     */
    private \App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface $templateService;

    /**
     * Constructor
     * 
     * @param WhatsAppServiceInterface $whatsAppService
     * @param WhatsAppTemplateRepositoryInterface $templateRepository
     * @param LoggerInterface $logger
     * @param \App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface $templateService
     */
    public function __construct(
        WhatsAppServiceInterface $whatsAppService,
        WhatsAppTemplateRepositoryInterface $templateRepository,
        LoggerInterface $logger,
        \App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface $templateService
    ) {
        $this->whatsAppService = $whatsAppService;
        $this->templateRepository = $templateRepository;
        $this->logger = $logger;
        $this->templateService = $templateService;
    }

    /**
     * Envoyer un message texte
     * 
     * @param User $user
     * @param array $data
     * @return array
     */
    public function sendTextMessage(User $user, array $data): array
    {
        try {
            if (!isset($data['recipient'])) {
                throw new \InvalidArgumentException('Le numéro du destinataire est requis');
            }
            if (!isset($data['message'])) {
                throw new \InvalidArgumentException('Le message est requis');
            }

            $recipient = $data['recipient'];
            $message = $data['message'];
            $contextMessageId = $data['context_message_id'] ?? null;

            $result = $this->whatsAppService->sendTextMessage($user, $recipient, $message, $contextMessageId);

            return [
                'status' => 'success',
                'message' => 'Message envoyé avec succès',
                'result' => $result
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi du message texte', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Envoyer un message média
     * 
     * @param User $user
     * @param array $data
     * @return array
     */
    public function sendMediaMessage(User $user, array $data): array
    {
        try {
            if (!isset($data['recipient'])) {
                throw new \InvalidArgumentException('Le numéro du destinataire est requis');
            }
            if (!isset($data['type'])) {
                throw new \InvalidArgumentException('Le type de média est requis (image, video, audio, document)');
            }
            if (!isset($data['media_url']) && !isset($data['media_id'])) {
                throw new \InvalidArgumentException('L\'URL ou l\'ID du média est requis');
            }

            $recipient = $data['recipient'];
            $type = $data['type'];
            $mediaIdOrUrl = $data['media_id'] ?? $data['media_url'];
            $caption = $data['caption'] ?? null;

            $result = $this->whatsAppService->sendMediaMessage($user, $recipient, $type, $mediaIdOrUrl, $caption);

            return [
                'status' => 'success',
                'message' => 'Message média envoyé avec succès',
                'result' => $result
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi du message média', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Envoyer un message template simple
     * 
     * @param User $user
     * @param array $data
     * @return array
     */
    public function sendTemplateMessage(User $user, array $data): array
    {
        try {
            if (!isset($data['recipient'])) {
                throw new \InvalidArgumentException('Le numéro du destinataire est requis');
            }
            if (!isset($data['template_name'])) {
                throw new \InvalidArgumentException('Le nom du template est requis');
            }
            if (!isset($data['language_code'])) {
                throw new \InvalidArgumentException('Le code de langue est requis');
            }

            $recipient = $data['recipient'];
            $templateName = $data['template_name'];
            $languageCode = $data['language_code'];
            $headerImageUrl = $data['header_image_url'] ?? null;
            $bodyParams = $data['body_params'] ?? [];

            $result = $this->whatsAppService->sendTemplateMessage(
                $user,
                $recipient,
                $templateName,
                $languageCode,
                $headerImageUrl,
                $bodyParams
            );

            return [
                'status' => 'success',
                'message' => 'Message template envoyé avec succès',
                'template' => $templateName,
                'message_id' => $result->getWabaMessageId()
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi du message template', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Envoyer un message template avec composants détaillés
     * 
     * @param User $user
     * @param array $data
     * @return array
     */
    public function sendTemplateMessageWithComponents(User $user, array $data): array
    {
        try {
            if (!isset($data['recipient'])) {
                throw new \InvalidArgumentException('Le numéro du destinataire est requis');
            }
            if (!isset($data['template_name'])) {
                throw new \InvalidArgumentException('Le nom du template est requis');
            }
            if (!isset($data['language_code'])) {
                throw new \InvalidArgumentException('Le code de langue est requis');
            }

            $recipient = $data['recipient'];
            $templateName = $data['template_name'];
            $languageCode = $data['language_code'];
            $components = $data['components'] ?? [];
            $headerMediaId = $data['header_media_id'] ?? null;

            $result = $this->whatsAppService->sendTemplateMessageWithComponents(
                $user,
                $recipient,
                $templateName,
                $languageCode,
                $components,
                $headerMediaId
            );

            return [
                'status' => 'success',
                'message' => 'Message template avancé envoyé avec succès',
                'template' => $templateName,
                'result' => $result
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi du message template avancé', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Envoyer un message interactif
     * 
     * @param User $user
     * @param array $data
     * @return array
     */
    public function sendInteractiveMessage(User $user, array $data): array
    {
        try {
            if (!isset($data['recipient'])) {
                throw new \InvalidArgumentException('Le numéro du destinataire est requis');
            }
            if (!isset($data['interactive']) || !is_array($data['interactive'])) {
                throw new \InvalidArgumentException('Les données interactives sont requises');
            }

            $recipient = $data['recipient'];
            $interactive = $data['interactive'];

            $result = $this->whatsAppService->sendInteractiveMessage($user, $recipient, $interactive);

            return [
                'status' => 'success',
                'message' => 'Message interactif envoyé avec succès',
                'result' => $result
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi du message interactif', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Marquer un message comme lu
     * 
     * @param User $user
     * @param array $data
     * @return array
     */
    public function markMessageAsRead(User $user, array $data): array
    {
        try {
            if (!isset($data['message_id'])) {
                throw new \InvalidArgumentException('L\'ID du message est requis');
            }

            $messageId = $data['message_id'];
            $result = $this->whatsAppService->markAsRead($user, $messageId);

            return [
                'status' => 'success',
                'message' => 'Message marqué comme lu',
                'result' => $result
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du marquage du message comme lu', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir l'historique des messages
     * 
     * @param User $user
     * @param array $params
     * @return array
     */
    public function getMessageHistory(User $user, array $params): array
    {
        try {
            $phoneNumber = $params['phone_number'] ?? null;
            $status = $params['status'] ?? null;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
            $offset = isset($params['offset']) ? (int)$params['offset'] : 0;

            $messages = $this->whatsAppService->getMessageHistory(
                $user,
                $phoneNumber,
                $status,
                $limit,
                $offset
            );

            $formattedMessages = [];
            foreach ($messages as $message) {
                $formattedMessages[] = [
                    'id' => $message->getId(),
                    'phone_number' => $message->getPhoneNumber(),
                    'type' => $message->getType(),
                    'direction' => $message->getDirection(),
                    'status' => $message->getStatus(),
                    'waba_message_id' => $message->getWabaMessageId(),
                    'template_name' => $message->getTemplateName(),
                    'template_language' => $message->getTemplateLanguage(),
                    'media_id' => $message->getMediaId(),
                    'timestamp' => $message->getTimestamp()->format('Y-m-d H:i:s'),
                    'content' => $message->getContent()
                ];
            }

            return [
                'status' => 'success',
                'messages' => $formattedMessages,
                'total' => count($messages),
                'limit' => $limit,
                'offset' => $offset
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération de l\'historique des messages', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Uploader un média
     * 
     * @param User $user
     * @param array $data
     * @return array
     */
    public function uploadMedia(User $user, array $data): array
    {
        try {
            if (!isset($data['file_path'])) {
                throw new \InvalidArgumentException('Le chemin du fichier est requis');
            }
            if (!isset($data['mime_type'])) {
                throw new \InvalidArgumentException('Le type MIME est requis');
            }

            $filePath = $data['file_path'];
            $mimeType = $data['mime_type'];

            $mediaId = $this->whatsAppService->uploadMedia($user, $filePath, $mimeType);

            return [
                'status' => 'success',
                'message' => 'Média uploadé avec succès',
                'media_id' => $mediaId
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'upload du média', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Télécharger un média
     * 
     * @param User $user
     * @param array $params
     * @return array
     */
    public function downloadMedia(User $user, array $params): array
    {
        try {
            if (!isset($params['media_id'])) {
                throw new \InvalidArgumentException('L\'ID du média est requis');
            }

            $mediaId = $params['media_id'];
            $mediaData = $this->whatsAppService->downloadMedia($user, $mediaId);

            return [
                'status' => 'success',
                'media_data' => $mediaData
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du téléchargement du média', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir l'URL d'un média
     * 
     * @param User $user
     * @param array $params
     * @return array
     */
    public function getMediaUrl(User $user, array $params): array
    {
        try {
            if (!isset($params['media_id'])) {
                throw new \InvalidArgumentException('L\'ID du média est requis');
            }

            $mediaId = $params['media_id'];
            $mediaUrl = $this->whatsAppService->getMediaUrl($user, $mediaId);

            return [
                'status' => 'success',
                'media_url' => $mediaUrl
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération de l\'URL du média', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Vérifier le webhook
     * 
     * @param array $params
     * @return string|null
     */
    public function verifyWebhook(array $params): ?string
    {
        try {
            if (!isset($params['hub_mode']) || !isset($params['hub_challenge']) || !isset($params['hub_verify_token'])) {
                $this->logger->error('Paramètres de vérification de webhook incomplets');
                return null;
            }

            $mode = $params['hub_mode'];
            $challenge = $params['hub_challenge'];
            $verifyToken = $params['hub_verify_token'];

            return $this->whatsAppService->verifyWebhook($mode, $challenge, $verifyToken);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la vérification du webhook', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Traiter le webhook
     * 
     * @param array $payload
     * @return array
     */
    public function processWebhook(array $payload): array
    {
        try {
            $this->whatsAppService->processWebhook($payload);
            return [
                'status' => 'success',
                'message' => 'Webhook traité avec succès'
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du traitement du webhook', [
                'error' => $e->getMessage()
            ]);
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir les templates de l'utilisateur
     * 
     * @param User $user
     * @return array
     */
    public function getUserTemplates(User $user): array
    {
        try {
            $templates = $this->whatsAppService->getUserTemplates($user);
            return [
                'status' => 'success',
                'templates' => $templates
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des templates', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtenir les templates approuvés avec une gestion robuste des erreurs
     * 
     * Cette méthode est conçue pour être robuste face aux erreurs de l'API Meta.
     * Elle priorise l'utilisation de l'API Meta et implémente des fallbacks:
     * 1. Toujours essayer d'abord l'API Meta directement
     * 2. Si erreur, utilisation des templates en cache
     * 3. Si aucun cache, utilisation des templates par défaut
     * 
     * @param User $user
     * @param array $params Paramètres de filtrage optionnels
     * @return array
     */
    public function getApprovedTemplates(User $user, array $params = []): array
    {
        try {
            $this->logger->info('Récupération des templates approuvés', [
                'user_id' => $user->getId(),
                'params' => $params
            ]);
            
            // Extraction des paramètres de filtrage
            $filter = [
                'name' => $params['name'] ?? null,
                'language' => $params['language'] ?? null,
                'category' => $params['category'] ?? null,
                'status' => $params['status'] ?? 'APPROVED',
                'useCache' => isset($params['use_cache']) ? (bool)$params['use_cache'] : false, // Par défaut, pas de cache
                'forceRefresh' => isset($params['force_refresh']) ? (bool)$params['force_refresh'] : true  // Par défaut, forcer le rafraîchissement
            ];
            
            // Paramètres supplémentaires
            $forceMeta = isset($params['force_meta']) ? (bool)$params['force_meta'] : true; // Par défaut, forcer l'utilisation de l'API Meta
            $noFallback = isset($params['no_fallback']) ? (bool)$params['no_fallback'] : false;
            $debugMode = isset($params['debug']) ? (bool)$params['debug'] : false;
            
            // Log des paramètres d'API en mode debug
            if ($debugMode) {
                $this->logger->debug('Appel API WhatsApp avec paramètres', [
                    'filter' => $filter,
                    'forceMeta' => $forceMeta,
                    'noFallback' => $noFallback
                ]);
            }
            
            // NIVEAU 1: Toujours essayer l'API Meta directement en premier
            $templates = [];
            $usedFallback = false;
            $source = 'meta_api';
            $errorMessages = [];
            
            try {
                // Tentative directe avec l'API Meta
                $metaApiFilter = $filter;
                $metaApiFilter['useCache'] = false;
                $metaApiFilter['forceRefresh'] = true;
                
                $templates = $this->templateService->fetchApprovedTemplatesFromMeta($metaApiFilter);
                
                if (!empty($templates)) {
                    if ($debugMode) {
                        $this->logger->debug('Templates récupérés depuis l\'API Meta avec succès', [
                            'count' => count($templates)
                        ]);
                    }
                    
                    return [
                        'status' => 'success',
                        'templates' => $templates,
                        'count' => count($templates),
                        'meta' => [
                            'source' => 'meta_api_direct',
                            'usedFallback' => false,
                            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
                        ]
                    ];
                } else if ($debugMode) {
                    $this->logger->debug('L\'API Meta a retourné une liste vide de templates');
                }
            } catch (\Exception $metaApiError) {
                $errorMessage = $metaApiError->getMessage();
                $errorMessages[] = $errorMessage;
                
                $this->logger->warning('Erreur lors de la récupération directe des templates depuis l\'API Meta', [
                    'error' => $errorMessage,
                    'user_id' => $user->getId(),
                    'trace' => $debugMode ? $metaApiError->getTraceAsString() : null
                ]);
                
                // Si on force l'utilisation exclusive de l'API Meta et qu'on ne veut pas de fallback, retourner l'erreur
                if ($forceMeta && $noFallback) {
                    return [
                        'status' => 'error',
                        'templates' => [],
                        'count' => 0,
                        'error_code' => 'META_API_ERROR',
                        'message' => 'Impossible de récupérer les templates depuis l\'API Meta: ' . $errorMessage,
                        'meta' => [
                            'source' => 'error',
                            'usedFallback' => false,
                            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
                            'debug' => $debugMode ? [
                                'error_full' => $errorMessage,
                                'trace' => $metaApiError->getTraceAsString()
                            ] : null
                        ]
                    ];
                }
            }
            
            // Si on force l'utilisation exclusive de l'API Meta mais qu'on accepte les fallbacks, essayer l'API via le service
            if ($forceMeta && !$noFallback && empty($templates)) {
                try {
                    // Tentative via le service qui a sa propre logique de gestion d'erreurs
                    $templates = $this->whatsAppService->getApprovedTemplates($user, $filter);
                    
                    if (!empty($templates)) {
                        if ($debugMode) {
                            $this->logger->debug('Templates récupérés via le service WhatsApp avec succès', [
                                'count' => count($templates)
                            ]);
                        }
                        
                        return [
                            'status' => 'success',
                            'templates' => $templates,
                            'count' => count($templates),
                            'meta' => [
                                'source' => 'whatsapp_service',
                                'usedFallback' => false,
                                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
                            ]
                        ];
                    }
                } catch (\Exception $serviceError) {
                    $errorMessage = $serviceError->getMessage();
                    $errorMessages[] = $errorMessage;
                    
                    $this->logger->warning('Erreur lors de la récupération des templates via le service', [
                        'error' => $errorMessage,
                        'user_id' => $user->getId()
                    ]);
                }
            }
            
            // NIVEAU 2: Cache de templates en base de données si on accepte les fallbacks
            if (!$noFallback && empty($templates)) {
                $usedFallback = true;
                
                try {
                    if ($debugMode) {
                        $this->logger->debug('Tentative de récupération des templates depuis le cache');
                    }
                    
                    $templates = $this->templateRepository->findApprovedTemplates($filter);
                    $source = 'cache';
                    
                    if (!empty($templates)) {
                        if ($debugMode) {
                            $this->logger->debug('Templates récupérés depuis le cache avec succès', [
                                'count' => count($templates)
                            ]);
                        }
                    } else if ($debugMode) {
                        $this->logger->debug('Le cache a retourné une liste vide de templates');
                    }
                } catch (\Exception $cacheError) {
                    $errorMessages[] = $cacheError->getMessage();
                    
                    $this->logger->error('Erreur lors de la récupération des templates en cache', [
                        'error' => $cacheError->getMessage(),
                        'user_id' => $user->getId()
                    ]);
                    
                    // NIVEAU 3: Templates par défaut (uniquement si on accepte les fallbacks)
                    if ($debugMode) {
                        $this->logger->debug('Utilisation des templates par défaut en dernier recours');
                    }
                    
                    $templates = $this->getFallbackTemplates($filter);
                    $source = 'fallback';
                }
            }
            
            // Vérifier si on a récupéré des templates
            if (empty($templates) && $noFallback) {
                return [
                    'status' => 'error',
                    'templates' => [],
                    'count' => 0,
                    'error_code' => 'NO_TEMPLATES_AVAILABLE',
                    'message' => 'Impossible de récupérer les templates: ' . implode('; ', $errorMessages),
                    'meta' => [
                        'source' => 'error',
                        'usedFallback' => false,
                        'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
                        'errors' => $errorMessages
                    ]
                ];
            }
            
            // Garantir que $templates est toujours un tableau
            if ($templates === null) {
                $templates = [];
            }
            
            // Filtrer les résultats si nécessaire
            if (isset($filter['name']) && $filter['name']) {
                $templates = array_filter($templates, function($template) use ($filter) {
                    return stripos($template['name'], $filter['name']) !== false;
                });
            }
            
            if (isset($filter['language']) && $filter['language']) {
                $templates = array_filter($templates, function($template) use ($filter) {
                    return $template['language'] === $filter['language'];
                });
            }
            
            if (isset($filter['category']) && $filter['category']) {
                $templates = array_filter($templates, function($template) use ($filter) {
                    return $template['category'] === $filter['category'];
                });
            }
            
            // Retourner les résultats avec métadonnées
            $response = [
                'status' => 'success',
                'templates' => array_values($templates), // Réindexer le tableau
                'count' => count($templates),
                'meta' => [
                    'source' => $source,
                    'usedFallback' => $usedFallback,
                    'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
                ]
            ];
            
            // Ajouter un avertissement si on a utilisé des templates de secours
            if ($source === 'fallback') {
                $response['warning'] = 'Templates par défaut utilisés. Connexion à l\'API Meta échouée.';
                $response['meta']['errorMessages'] = $errorMessages;
            } else if ($source === 'cache') {
                $response['notice'] = 'Templates récupérés depuis le cache. API Meta non disponible.';
                $response['meta']['errorMessages'] = $errorMessages;
            }
            
            // Ajouter des informations de debug si demandé
            if ($debugMode) {
                $response['meta']['debug'] = [
                    'filter' => $filter,
                    'forceMeta' => $forceMeta,
                    'noFallback' => $noFallback,
                    'errorMessages' => $errorMessages
                ];
            }
            
            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Erreur critique lors de la récupération des templates', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Même en cas d'erreur critique, on retourne un tableau vide mais valide
            return [
                'status' => 'error',
                'templates' => [],
                'count' => 0,
                'error_code' => 'CRITICAL_ERROR',
                'message' => 'Erreur critique lors de la récupération des templates: ' . $e->getMessage(),
                'meta' => [
                    'source' => 'error',
                    'usedFallback' => true,
                    'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
                ]
            ];
        }
    }
    
    /**
     * Fournit des templates de secours prédéfinis en cas d'indisponibilité
     * 
     * @param array $filter Filtres à appliquer
     * @return array Liste de templates de secours
     */
    private function getFallbackTemplates(array $filter = []): array
    {
        // Templates de secours basiques
        $templates = [
            [
                'id' => 'fallback_greeting',
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
                'id' => 'fallback_support',
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
                'id' => 'fallback_confirmation',
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
                'id' => 'fallback_information',
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
                'id' => 'fallback_promotion',
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
        
        // Filtrer si nécessaire
        if (isset($filter['name']) && $filter['name']) {
            $templates = array_filter($templates, function($template) use ($filter) {
                return stripos($template['name'], $filter['name']) !== false;
            });
        }
        
        if (isset($filter['language']) && $filter['language']) {
            $templates = array_filter($templates, function($template) use ($filter) {
                return $template['language'] === $filter['language'];
            });
        }
        
        if (isset($filter['category']) && $filter['category']) {
            $templates = array_filter($templates, function($template) use ($filter) {
                return $template['category'] === $filter['category'];
            });
        }
        
        return array_values($templates);
    }

    /**
     * Obtenir un template spécifique par son ID
     * 
     * @param User $user
     * @param array $params
     * @return array
     */
    public function getTemplateById(User $user, array $params): array
    {
        try {
            if (!isset($params['template_id'])) {
                throw new \InvalidArgumentException('L\'ID du template est requis');
            }

            $templateId = $params['template_id'];
            $template = $this->templateRepository->findOneBy(['name' => $templateId]);

            if (!$template) {
                return [
                    'status' => 'error',
                    'message' => 'Template non trouvé'
                ];
            }

            return [
                'status' => 'success',
                'template' => [
                    'id' => $template->getId(),
                    'template_id' => $template->getTemplateId(),
                    'name' => $template->getName(),
                    'category' => $template->getCategory(),
                    'language' => $template->getLanguage(),
                    'components' => $template->getComponents(),
                    'status' => $template->getStatus()
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération du template', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}