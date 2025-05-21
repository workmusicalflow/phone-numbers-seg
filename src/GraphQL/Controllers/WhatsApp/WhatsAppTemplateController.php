<?php

declare(strict_types=1);

namespace App\GraphQL\Controllers\WhatsApp;

use App\Entities\User;
use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\GraphQL\Context\GraphQLContext;
use App\GraphQL\Types\WhatsApp\SendTemplateInput;
use App\GraphQL\Types\WhatsApp\SendTemplateResult;
use App\GraphQL\Types\WhatsApp\TemplateFilterInput;
use App\GraphQL\Types\WhatsApp\WhatsAppTemplateSafeType;
use App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface;
use Psr\Log\LoggerInterface;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Parameter;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Contrôleur GraphQL pour les fonctionnalités liées aux templates WhatsApp
 * 
 * Ce contrôleur implémente :
 * - La mutation sendWhatsAppTemplateV2 qui permet d'envoyer des messages de templates WhatsApp
 * - La query fetchApprovedWhatsAppTemplates pour récupérer la liste des templates approuvés
 */
#[Type]
class WhatsAppTemplateController
{
    private WhatsAppServiceInterface $whatsappService;
    private WhatsAppTemplateServiceInterface $templateService;
    private LoggerInterface $logger;

    public function __construct(
        WhatsAppServiceInterface $whatsappService,
        WhatsAppTemplateServiceInterface $templateService,
        LoggerInterface $logger
    ) {
        $this->whatsappService = $whatsappService;
        $this->templateService = $templateService;
        $this->logger = $logger;
    }

    /**
     * Récupère les templates WhatsApp approuvés
     * 
     * Cette méthode permet de récupérer les templates approuvés directement depuis l'API Meta
     * avec une gestion robuste des erreurs. Elle retourne toujours un tableau (potentiellement vide)
     * en cas d'erreur pour éviter les problèmes de nullabilité avec GraphQL.
     * 
     * @param TemplateFilterInput|null $filter Filtres optionnels (nom, langue, catégorie)
     * @param User|null $user Utilisateur authentifié (injecté automatiquement)
     * @return array Liste des templates WhatsApp approuvés
     */
    #[Query(name: "fetchApprovedWhatsAppTemplates")]
    #[Logged]
    public function fetchApprovedWhatsAppTemplates(
        ?TemplateFilterInput $filter = null,
        #[InjectUser] ?User $user = null
    ): array {
        // Journal de démarrage de la fonction
        $this->logger->info("[fetchApprovedWhatsAppTemplates] Début exécution requête", [
            'controller' => 'WhatsAppTemplateController',
            'time' => date('Y-m-d H:i:s'),
        ]);
        
        // Vérification de l'utilisateur
        if (!$user) {
            $this->logger->warning("[fetchApprovedWhatsAppTemplates] Tentative d'accès sans authentification");
            throw new GraphQLException("Authentification requise", 401);
        }
        
        // Log de l'utilisateur identifié
        $this->logger->info("[fetchApprovedWhatsAppTemplates] Utilisateur authentifié", [
            'user_id' => $user->getId(),
        ]);

        try {
            $this->logger->info("Récupération des templates WhatsApp approuvés", [
                'user_id' => $user->getId(),
                'filter' => $filter ? [
                    'name' => $filter->name ?? null,
                    'language' => $filter->language ?? null,
                    'category' => $filter->category ?? null
                ] : null
            ]);

            // Préparer les filtres pour le service
            $filterArray = [];
            if ($filter) {
                if ($filter->name !== null) {
                    $filterArray['name'] = $filter->name;
                }
                if ($filter->language !== null) {
                    $filterArray['language'] = $filter->language;
                }
                if ($filter->category !== null) {
                    $filterArray['category'] = $filter->category;
                }
            }

            // ATTENTION: S'assurer que nous avons toujours un array, même vide
            try {
                // Appeler le service pour récupérer les templates
                $templates = $this->templateService->fetchApprovedTemplatesFromMeta($filterArray);
                
                // Validation et sécurité
                if (!is_array($templates)) {
                    $this->logger->warning('Le service a retourné un type non-array pour fetchApprovedTemplatesFromMeta', [
                        'type' => gettype($templates),
                        'value' => $templates
                    ]);
                    $templates = [];
                }
            } catch (\Throwable $serviceException) {
                $this->logger->error('Exception dans le service template', [
                    'error' => $serviceException->getMessage(),
                    'trace' => $serviceException->getTraceAsString()
                ]);
                // En cas d'erreur dans le service, utiliser un tableau vide
                $templates = [];
            }
            
            // Convertir les templates en objets WhatsAppTemplateSafeType
            $templateTypes = [];
            if (!empty($templates)) {
                foreach ($templates as $template) {
                    try {
                        $templateTypes[] = new WhatsAppTemplateSafeType($template);
                    } catch (\Throwable $typeException) {
                        $this->logger->error('Erreur lors de la création du type template', [
                            'template' => $template,
                            'error' => $typeException->getMessage()
                        ]);
                        // Continuer avec le prochain template en cas d'erreur
                        continue;
                    }
                }
            }
            
            $this->logger->info("Templates WhatsApp récupérés avec succès", [
                'count' => count($templateTypes)
            ]);
            
            // Garantir que nous retournons TOUJOURS un tableau (même vide)
            // pour respecter la non-nullabilité du schéma GraphQL
            return empty($templateTypes) ? [] : $templateTypes;
        } catch (\Exception $e) {
            $this->logger->error('[fetchApprovedWhatsAppTemplates] Erreur Exception standard', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'class' => get_class($e),
                'user_id' => $user->getId()
            ]);
            
            // En cas d'erreur, retourner un tableau vide plutôt que de lancer une exception
            return [];
        } catch (\Error $e) {
            // Capture des erreurs PHP (comme TypeError, ParseError, etc.)
            $this->logger->error('[fetchApprovedWhatsAppTemplates] Erreur PHP critique', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'class' => get_class($e),
                'user_id' => $user->getId()
            ]);
            
            return [];
        } catch (\Throwable $e) {
            // Capture de toute autre erreur
            $this->logger->error('[fetchApprovedWhatsAppTemplates] Erreur Throwable non gérée', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'class' => get_class($e),
                'user_id' => $user->getId()
            ]);
            
            return [];
        }
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
            $headerMediaId = $input->getHeaderMediaId();
            $components = [];
            
            // Préparer les composants du template si un JSON est fourni
            if ($input->getTemplateComponentsJsonString()) {
                $components = json_decode($input->getTemplateComponentsJsonString(), true) ?: [];
            }

            // Si nous avons un headerMediaId, utiliser la méthode avec composants
            if ($headerMediaId) {
                $this->logger->info("Envoi de template WhatsApp avec Media ID", [
                    'mediaId' => $headerMediaId
                ]);
                
                // Envoyer via le service avec composants et Media ID
                $response = $this->whatsappService->sendTemplateMessageWithComponents(
                    $user,
                    $input->getRecipientPhoneNumber(),
                    $input->getTemplateName(),
                    $input->getTemplateLanguage(),
                    $components,
                    $headerMediaId
                );
                
                // Récupérer l'ID du message WhatsApp
                $wabaMessageId = $response['messages'][0]['id'] ?? null;
                
                // Créer un objet message d'historique
                $messageHistory = new \App\Entities\WhatsApp\WhatsAppMessageHistory();
                $messageHistory->setOracleUser($user);
                $messageHistory->setWabaMessageId($wabaMessageId);
                $messageHistory->setPhoneNumber($input->getRecipientPhoneNumber());
                $messageHistory->setDirection('OUTGOING');
                $messageHistory->setType('template');
                $messageHistory->setStatus('sent');
                $messageHistory->setTemplateName($input->getTemplateName());
                $messageHistory->setTemplateLanguage($input->getTemplateLanguage());
                $messageHistory->setMediaId($headerMediaId);
                $messageHistory->setTimestamp(new \DateTime());
            } else {
                // Utiliser la méthode standard avec URL
                $messageHistory = $this->whatsappService->sendTemplateMessage(
                    $user,
                    $input->getRecipientPhoneNumber(),
                    $input->getTemplateName(),
                    $input->getTemplateLanguage(),
                    $headerMediaUrl,
                    $bodyVariables
                );
            }
            
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