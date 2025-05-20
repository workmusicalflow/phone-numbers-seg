<?php

declare(strict_types=1);

namespace App\GraphQL\Resolvers\WhatsApp;

use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\GraphQL\Context\GraphQLContext;
use App\GraphQL\Types\WhatsApp\WhatsAppMessageInputType;
use App\GraphQL\Types\WhatsApp\WhatsAppTemplateSendInput;
use App\GraphQL\Types\WhatsApp\SendTemplateResult;
use App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface;
use Psr\Log\LoggerInterface;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;

/**
 * Resolver GraphQL pour les opérations WhatsApp
 */
class WhatsAppResolver
{
    public function __construct(
        private WhatsAppServiceInterface $whatsappService,
        private WhatsAppMessageHistoryRepositoryInterface $whatsappMessageRepository,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    /**
     * Envoie un message WhatsApp
     * 
     * @Mutation
     * @Logged
     */
    public function sendWhatsAppMessage(
        array $message,
        ?GraphQLContext $context = null
    ): WhatsAppMessageHistory {
        try {
            error_log("WhatsApp sendMessage - Input: " . json_encode($message));

            $user = $context->getCurrentUser();
            if (!$user) {
                throw new \Exception("L'utilisateur doit être authentifié.");
            }

            error_log("WhatsApp sendMessage - User: " . $user->getId());

            // Créer l'objet message à partir du tableau
            $messageInput = new WhatsAppMessageInputType();
            $messageInput->recipient = $message['recipient'];
            $messageInput->type = $message['type'];
            $messageInput->content = $message['content'] ?? null;
            $messageInput->mediaUrl = $message['mediaUrl'] ?? null;
            $messageInput->templateName = $message['templateName'] ?? null;
            $messageInput->languageCode = $message['languageCode'] ?? null;

            error_log("WhatsApp sendMessage - Calling service");

            $result = $this->whatsappService->sendMessage(
                $user,
                $messageInput->recipient,
                $messageInput->type,
                $messageInput->content,
                $messageInput->mediaUrl
            );

            error_log("WhatsApp sendMessage - Result: " . json_encode([
                'id' => $result->getId(),
                'wabaMessageId' => $result->getWabaMessageId(),
                'status' => $result->getStatus()
            ]));

            return $result;
        } catch (\Exception $e) {
            error_log("WhatsApp sendMessage - Error: " . $e->getMessage());
            error_log("WhatsApp sendMessage - Trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Envoie un message template WhatsApp
     * 
     * @Mutation
     * @Logged
     */
    public function sendWhatsAppTemplate(
        array $template,
        ?GraphQLContext $context = null
    ): WhatsAppMessageHistory {
        $user = $context->getCurrentUser();
        if (!$user) {
            throw new \Exception("L'utilisateur doit être authentifié.");
        }

        // Créer l'objet template à partir du tableau
        $templateInput = new WhatsAppTemplateSendInput();
        $templateInput->recipient = $template['recipient'];
        $templateInput->templateName = $template['templateName'];
        $templateInput->languageCode = $template['languageCode'];

        // Convertir en objet message pour l'envoi
        $messageInput = new WhatsAppMessageInputType();
        $messageInput->recipient = $templateInput->recipient;
        $messageInput->type = 'template';
        $messageInput->templateName = $templateInput->templateName;
        $messageInput->languageCode = $templateInput->languageCode;

        // Gérer les paramètres si présents
        $params = [];
        if (isset($template['body1Param'])) $params[] = $template['body1Param'];
        if (isset($template['body2Param'])) $params[] = $template['body2Param'];
        if (isset($template['body3Param'])) $params[] = $template['body3Param'];

        return $this->whatsappService->sendTemplateMessage(
            $user,
            $messageInput->recipient,
            $templateInput->templateName,
            $templateInput->languageCode,
            $template['headerImageUrl'] ?? null,
            $params
        );
    }

    /**
     * Récupère l'historique des messages WhatsApp
     * 
     * @Query
     * @Logged
     * @return array{messages: WhatsAppMessageHistory[], totalCount: int, hasMore: bool}
     */
    public function getWhatsAppMessages(
        ?int $limit = 100,
        ?int $offset = 0,
        ?string $phoneNumber = null,
        ?string $status = null,
        ?string $type = null,
        ?string $direction = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?GraphQLContext $context = null
    ): array {
        $user = $context->getCurrentUser();
        if (!$user) {
            throw new \Exception("L'utilisateur doit être authentifié.");
        }

        $criteria = ['oracleUser' => $user];
        
        // Filtrage spécial pour le numéro de téléphone (recherche partielle)
        $phoneFilter = null;
        if ($phoneNumber !== null && $phoneNumber !== '') {
            // On ne l'ajoute pas aux criteria standard, on le traite séparément
            $phoneFilter = $phoneNumber;
            error_log('[WhatsAppResolver] Phone number filter: ' . $phoneFilter);
        }

        if ($status !== null && $status !== '') {
            $criteria['status'] = $status;
        }

        if ($type !== null && $type !== '') {
            $criteria['type'] = $type;
        }

        if ($direction !== null && $direction !== '') {
            $criteria['direction'] = $direction;
        }

        // Gérer les filtres de date
        $dateFilters = [];
        if ($startDate !== null && $startDate !== '') {
            error_log('[WhatsAppResolver] Start date filter received: ' . $startDate);
            $dateFilters['startDate'] = new \DateTime($startDate . ' 00:00:00');
            error_log('[WhatsAppResolver] Start date with time: ' . $dateFilters['startDate']->format('Y-m-d H:i:s T'));
        }
        if ($endDate !== null && $endDate !== '') {
            error_log('[WhatsAppResolver] End date filter received: ' . $endDate);
            $dateFilters['endDate'] = new \DateTime($endDate . ' 23:59:59');
            error_log('[WhatsAppResolver] End date with time: ' . $dateFilters['endDate']->format('Y-m-d H:i:s T'));
        }
        
        // Log le nombre de filtres de date
        error_log('[WhatsAppResolver] Date filters count: ' . count($dateFilters));

        // Si nous avons des filtres (date ou téléphone), utiliser une méthode dédiée
        if (!empty($dateFilters) || $phoneFilter !== null) {
            error_log('[WhatsAppResolver] Using advanced filtering');
            $messages = $this->whatsappMessageRepository->findByWithFilters(
                $criteria,
                $dateFilters,
                $phoneFilter,
                ['createdAt' => 'DESC'],
                $limit,
                $offset
            );
            $totalCount = $this->whatsappMessageRepository->countWithFilters($criteria, $dateFilters, $phoneFilter);
            error_log('[WhatsAppResolver] Found ' . count($messages) . ' messages with advanced filters');
        } else {
            error_log('[WhatsAppResolver] Using standard filtering');
            $messages = $this->whatsappMessageRepository->findBy(
                $criteria,
                ['createdAt' => 'DESC'],
                $limit,
                $offset
            );
            $totalCount = $this->whatsappMessageRepository->count($criteria);
            error_log('[WhatsAppResolver] Found ' . count($messages) . ' messages without filters');
        }

        return [
            'messages' => $messages,
            'totalCount' => $totalCount,
            'hasMore' => ($offset + $limit) < $totalCount
        ];
    }

    /**
     * Récupère un message WhatsApp par son ID
     * 
     * @Query
     * @Logged
     */
    public function whatsAppMessage(
        int $id,
        ?GraphQLContext $context = null
    ): ?WhatsAppMessageHistory {
        $user = $context->getCurrentUser();
        if (!$user) {
            throw new \Exception("L'utilisateur doit être authentifié.");
        }

        $message = $this->whatsappMessageRepository->find($id);

        // Vérifier que le message appartient à l'utilisateur
        if ($message && $message->getOracleUser()->getId() === $user->getId()) {
            return $message;
        }

        return null;
    }

    /**
     * Compte le nombre de messages WhatsApp
     * 
     * @Query
     * @Logged
     */
    public function whatsAppMessageCount(
        ?string $status = null,
        ?string $direction = null,
        ?GraphQLContext $context = null
    ): int {
        $user = $context->getCurrentUser();
        if (!$user) {
            throw new \Exception("L'utilisateur doit être authentifié.");
        }

        $criteria = ['oracleUser' => $user];

        if ($status !== null && $status !== '') {
            $criteria['status'] = $status;
        }

        if ($direction !== null && $direction !== '') {
            $criteria['direction'] = $direction;
        }

        return $this->whatsappMessageRepository->count($criteria);
    }

    /**
     * Récupère les templates WhatsApp de l'utilisateur
     * 
     * @Query
     * @Logged
     */
    public function getWhatsAppUserTemplates(
        ?GraphQLContext $context = null
    ): array {
        $user = $context->getCurrentUser();
        if (!$user) {
            throw new \Exception("L'utilisateur doit être authentifié.");
        }

        return $this->whatsappService->getUserTemplates($user);
    }

    /**
     * Obtient l'URL d'un média WhatsApp
     * 
     * @Query
     * @Logged
     */
    public function getWhatsAppMediaUrl(
        string $mediaId,
        ?GraphQLContext $context = null
    ): string {
        $user = $context->getCurrentUser();
        if (!$user) {
            throw new \Exception("L'utilisateur doit être authentifié.");
        }

        try {
            return $this->whatsappService->getMediaUrl($user, $mediaId);
        } catch (\Exception $e) {
            error_log("WhatsApp getMediaUrl - Error: " . $e->getMessage());
            throw new \Exception("Erreur lors de la récupération de l'URL du média : " . $e->getMessage());
        }
    }

    /**
     * Envoie un message média WhatsApp
     * 
     * @Mutation
     * @Logged
     */
    public function sendWhatsAppMediaMessage(
        string $recipient,
        string $type,
        string $mediaIdOrUrl,
        ?string $caption = null,
        ?GraphQLContext $context = null
    ): WhatsAppMessageHistory {
        error_log("[WhatsApp Resolver] sendWhatsAppMediaMessage called with: recipient=$recipient, type=$type, mediaIdOrUrl=$mediaIdOrUrl, caption=$caption");
        
        $user = $context->getCurrentUser();
        if (!$user) {
            throw new \Exception("L'utilisateur doit être authentifié.");
        }
        
        error_log("[WhatsApp Resolver] User authenticated: " . $user->getId());

        try {
            // Valider le type de média
            $validTypes = ['image', 'video', 'audio', 'document'];
            if (!in_array($type, $validTypes)) {
                throw new \InvalidArgumentException("Type de média invalide: $type");
            }

            // Utiliser le service WhatsApp pour envoyer le message média
            error_log("[WhatsApp Resolver] Calling whatsappService->sendMediaMessage");
            $response = $this->whatsappService->sendMediaMessage(
                $user,
                $recipient,
                $type,
                $mediaIdOrUrl,
                $caption
            );
            error_log("[WhatsApp Resolver] Service response: " . json_encode($response));

            // Récupérer l'historique du message créé
            $wabaMessageId = $response['messages'][0]['id'] ?? null;
            error_log("[WhatsApp Resolver] wabaMessageId: " . $wabaMessageId);
            
            if (!$wabaMessageId) {
                throw new \Exception("ID du message WhatsApp non retourné");
            }

            // Créer manuellement l'objet de réponse pour éviter les problèmes de timing
            $message = new WhatsAppMessageHistory();
            $message->setOracleUser($user);
            $message->setPhoneNumber($recipient);
            $message->setWabaMessageId($wabaMessageId);
            $message->setDirection(WhatsAppMessageHistory::DIRECTION_OUTBOUND);
            $message->setType($this->mapMediaTypeToHistoryType($type));
            $message->setContent(json_encode([
                'media_id' => str_contains($mediaIdOrUrl, 'http') ? null : $mediaIdOrUrl,
                'media_url' => str_contains($mediaIdOrUrl, 'http') ? $mediaIdOrUrl : null,
                'caption' => $caption
            ]));
            $message->setStatus(WhatsAppMessageHistory::STATUS_SENT);
            $message->setTimestamp(new \DateTime());
            
            // Définir le mediaId correctement
            if (!str_contains($mediaIdOrUrl, 'http')) {
                $message->setMediaId($mediaIdOrUrl);
            }
            
            error_log("[WhatsApp Resolver] Created message object with ID: " . $message->getId());
            
            // Optionnel: essayer de récupérer depuis la DB, sinon retourner l'objet créé
            $savedMessage = $this->whatsappMessageRepository->findOneBy(['wabaMessageId' => $wabaMessageId]);
            error_log("[WhatsApp Resolver] Saved message from DB: " . ($savedMessage ? $savedMessage->getId() : 'null'));
            
            $result = $savedMessage ?: $message;
            error_log("[WhatsApp Resolver] Returning message with ID: " . ($result ? $result->getId() : 'null'));
            
            return $result;
        } catch (\Exception $e) {
            error_log("WhatsApp sendMediaMessage - Error: " . $e->getMessage());
            throw new \Exception("Erreur lors de l'envoi du message média : " . $e->getMessage());
        }
    }

    /**
     * Envoie un message template WhatsApp avec le nouveau type d'entrée
     * 
     * @Mutation
     * @Logged
     */
    public function sendWhatsAppTemplateV2(
        array $input,
        ?GraphQLContext $context = null
    ): SendTemplateResult {
        try {
            $user = $context->getCurrentUser();
            if (!$user) {
                throw new \Exception("L'utilisateur doit être authentifié.");
            }
            
            $this->logger->info("Envoi de template WhatsApp V2", [
                'template' => $input['templateName'],
                'recipient' => $input['recipientPhoneNumber'],
                'language' => $input['templateLanguage']
            ]);
            
            // Récupérer les paramètres
            $bodyVariables = $input['bodyVariables'] ?? [];
            $buttonVariables = $input['buttonVariables'] ?? [];
            $headerMediaUrl = $input['headerMediaUrl'] ?? null;
            
            // Envoyer le template via le service
            $messageHistory = $this->whatsappService->sendTemplateMessage(
                $user,
                $input['recipientPhoneNumber'],
                $input['templateName'],
                $input['templateLanguage'],
                $headerMediaUrl,
                $bodyVariables
            );
            
            // Construire le résultat
            return new SendTemplateResult(
                true,
                $messageHistory->getWabaMessageId(),
                null
            );
        } catch (\Exception $e) {
            $this->logger->error("Erreur envoi template WhatsApp V2", [
                'error' => $e->getMessage()
            ]);
            
            return new SendTemplateResult(
                false,
                null,
                $e->getMessage()
            );
        }
    }
    
    /**
     * Mutation de test simple
     * 
     * @Mutation
     * @Logged
     */
    public function testWhatsAppMutation(
        ?GraphQLContext $context = null
    ): string {
        return "Test mutation works!";
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
}
