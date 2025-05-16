<?php

declare(strict_types=1);

namespace App\GraphQL\Resolvers\WhatsApp;

use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\GraphQL\Context\GraphQLContext;
use App\GraphQL\Types\WhatsApp\WhatsAppMessageInputType;
use App\GraphQL\Types\WhatsApp\WhatsAppTemplateSendInput;
use App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface;
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
        private WhatsAppMessageHistoryRepositoryInterface $whatsappMessageRepository
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
     * @return WhatsAppMessageHistory[]
     */
    public function getWhatsAppMessages(
        ?int $limit = 100,
        ?int $offset = 0,
        ?string $phoneNumber = null,
        ?string $status = null,
        ?string $type = null,
        ?string $direction = null,
        ?GraphQLContext $context = null
    ): array {
        $user = $context->getCurrentUser();
        if (!$user) {
            throw new \Exception("L'utilisateur doit être authentifié.");
        }
        
        $criteria = ['oracleUser' => $user];
        
        if ($phoneNumber !== null) {
            $criteria['phoneNumber'] = $phoneNumber;
        }
        
        if ($status !== null) {
            $criteria['status'] = $status;
        }
        
        if ($type !== null) {
            $criteria['type'] = $type;
        }
        
        if ($direction !== null) {
            $criteria['direction'] = $direction;
        }
        
        $messages = $this->whatsappMessageRepository->findBy(
            $criteria,
            ['createdAt' => 'DESC'],
            $limit,
            $offset
        );
        
        $totalCount = $this->whatsappMessageRepository->count($criteria);
        
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
        
        if ($status !== null) {
            $criteria['status'] = $status;
        }
        
        if ($direction !== null) {
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
}