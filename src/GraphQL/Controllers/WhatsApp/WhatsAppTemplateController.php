<?php

declare(strict_types=1);

namespace App\GraphQL\Controllers\WhatsApp;

use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\GraphQL\Context\GraphQLContext;
use App\GraphQL\Types\WhatsApp\SendTemplateInput;
use App\GraphQL\Types\WhatsApp\SendTemplateResult;
use App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface;
use Psr\Log\LoggerInterface;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Parameter;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Contrôleur GraphQL pour les fonctionnalités liées aux templates WhatsApp
 * 
 * Ce contrôleur implémente la mutation sendWhatsAppTemplateV2 qui permet d'envoyer 
 * des messages de templates WhatsApp. Cette implémentation utilise des classes dédiées 
 * pour les types d'entrée et de sortie, ce qui garantit une meilleure compatibilité 
 * avec GraphQL et évite les problèmes de nullabilité.
 * 
 * @see \App\GraphQL\Types\WhatsApp\SendTemplateInput
 * @see \App\GraphQL\Types\WhatsApp\SendTemplateResult
 */
#[Type]
class WhatsAppTemplateController
{
    private WhatsAppServiceInterface $whatsappService;
    private LoggerInterface $logger;

    public function __construct(
        WhatsAppServiceInterface $whatsappService,
        LoggerInterface $logger
    ) {
        $this->whatsappService = $whatsappService;
        $this->logger = $logger;
    }

    /**
     * Envoie un message template WhatsApp avec les paramètres fournis
     */
    #[Mutation]
    #[Logged]
    public function sendWhatsAppTemplateV2(
        SendTemplateInput $input,
        ?GraphQLContext $context = null
    ): SendTemplateResult {
        try {
            $user = $context?->getCurrentUser();
            if (!$user) {
                throw new \Exception("L'utilisateur doit être authentifié.");
            }
            
            $this->logger->info("Envoi de template WhatsApp V2", [
                'template' => $input->getTemplateName(),
                'recipient' => $input->getRecipientPhoneNumber(),
                'language' => $input->getTemplateLanguage()
            ]);
            
            // Récupérer les paramètres
            $bodyVariables = $input->getBodyVariables() ?? [];
            $headerMediaUrl = $input->getHeaderMediaUrl();
            
            // Envoyer le template via le service
            $messageHistory = $this->whatsappService->sendTemplateMessage(
                $user,
                $input->getRecipientPhoneNumber(),
                $input->getTemplateName(),
                $input->getTemplateLanguage(),
                $headerMediaUrl,
                $bodyVariables
            );
            
            // Construire le résultat
            $result = new SendTemplateResult(
                true,
                $messageHistory->getWabaMessageId(),
                null
            );
            
            // Log du résultat
            $this->logger->info("Template WhatsApp envoyé avec succès", [
                'messageId' => $result->getMessageId()
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Erreur envoi template WhatsApp V2", [
                'error' => $e->getMessage()
            ]);
            
            $result = new SendTemplateResult(
                false,
                null,
                $e->getMessage()
            );
            
            // Log du résultat en cas d'erreur
            $this->logger->error("Échec de l'envoi du template WhatsApp", [
                'error' => $result->getError()
            ]);
            
            return $result;
        }
    }
}