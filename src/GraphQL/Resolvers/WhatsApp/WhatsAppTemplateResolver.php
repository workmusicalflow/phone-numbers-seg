<?php

declare(strict_types=1);

namespace App\GraphQL\Resolvers\WhatsApp;

use App\Entities\User;
use App\GraphQL\Types\WhatsApp\SendTemplateInput;
use App\GraphQL\Types\WhatsApp\TemplateFilterInput;
use App\GraphQL\Types\WhatsApp\WhatsAppTemplateType;
use App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface;
use Psr\Log\LoggerInterface;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

/**
 * Resolver GraphQL pour les templates WhatsApp
 */
class WhatsAppTemplateResolver
{
    private WhatsAppTemplateServiceInterface $templateService;
    private WhatsAppServiceInterface $whatsAppService;
    private LoggerInterface $logger;

    public function __construct(
        WhatsAppTemplateServiceInterface $templateService,
        WhatsAppServiceInterface $whatsAppService,
        LoggerInterface $logger
    ) {
        $this->templateService = $templateService;
        $this->whatsAppService = $whatsAppService;
        $this->logger = $logger;
    }

    /**
     * Récupère les templates WhatsApp approuvés directement depuis l'API Meta
     *
     * @param TemplateFilterInput|null $filter Filtres optionnels
     * @return WhatsAppTemplateType[]
     */
    #[Query(name: "fetchApprovedWhatsAppTemplates")]
    #[Logged]
    public function fetchApprovedWhatsAppTemplates(?TemplateFilterInput $filter = null, #[InjectUser] ?User $user = null): array
    {
        if (!$user) {
            throw new GraphQLException("Authentification requise", 401);
        }

        try {
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

            $templates = $this->templateService->fetchApprovedTemplatesFromMeta($filterArray);
            
            $templateTypes = [];
            foreach ($templates as $template) {
                $templateTypes[] = new WhatsAppTemplateType($template);
            }
            
            return $templateTypes;
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération templates WhatsApp', [
                'error' => $e->getMessage(),
                'user' => $user->getId()
            ]);
            
            throw new GraphQLException("Erreur lors de la récupération des templates: " . $e->getMessage());
        }
    }

    /**
     * Récupère les catégories de templates disponibles
     *
     * @return string[]
     */
    #[Query(name: "whatsAppTemplateCategories")]
    #[Logged]
    public function getTemplateCategories(#[InjectUser] ?User $user = null): array
    {
        if (!$user) {
            throw new GraphQLException("Authentification requise", 401);
        }

        try {
            return $this->templateService->getTemplateCategories();
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération catégories templates WhatsApp', [
                'error' => $e->getMessage(),
                'user' => $user->getId()
            ]);
            
            throw new GraphQLException("Erreur lors de la récupération des catégories: " . $e->getMessage());
        }
    }

    /**
     * Récupère les langues disponibles pour les templates
     *
     * @return string[]
     */
    #[Query(name: "whatsAppTemplateLanguages")]
    #[Logged]
    public function getTemplateLanguages(#[InjectUser] ?User $user = null): array
    {
        if (!$user) {
            throw new GraphQLException("Authentification requise", 401);
        }

        try {
            return $this->templateService->getTemplateLanguages();
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération langues templates WhatsApp', [
                'error' => $e->getMessage(),
                'user' => $user->getId()
            ]);
            
            throw new GraphQLException("Erreur lors de la récupération des langues: " . $e->getMessage());
        }
    }

    /**
     * Envoie un message WhatsApp basé sur un template
     */
    #[Mutation(name: "sendWhatsAppTemplate")]
    #[Logged]
    public function sendWhatsAppTemplate(
        SendTemplateInput $input,
        #[InjectUser] ?User $user = null
    ): array {
        if (!$user) {
            throw new GraphQLException("Authentification requise", 401);
        }

        try {
            // Décoder les composants du template
            $templateComponents = json_decode($input->templateComponentsJsonString, true);
            if (!$templateComponents && $input->templateComponentsJsonString) {
                throw new GraphQLException("Format de composants de template invalide");
            }

            // Préparer les données dynamiques
            $templateDynamicData = [];

            // Ajouter le média d'en-tête si présent
            if ($input->headerMediaUrl) {
                $templateDynamicData['header'] = ['link' => $input->headerMediaUrl];
            }

            // Ajouter les variables du corps
            if (!empty($input->bodyVariables)) {
                $templateDynamicData['body'] = $input->bodyVariables;
            }

            // Ajouter les variables de boutons
            if (!empty($input->buttonVariables)) {
                $templateDynamicData['buttons'] = $input->buttonVariables;
            }

            // Construire les composants pour l'API
            $components = $this->templateService->buildTemplateComponents(
                $templateComponents,
                $templateDynamicData
            );

            // Envoyer le message template
            $result = $this->whatsAppService->sendTemplateMessageWithComponents(
                $user,
                $input->recipientPhoneNumber,
                $input->templateName,
                $input->templateLanguage,
                $components
            );

            return [
                'success' => true,
                'messageId' => $result['messages'][0]['id'] ?? null,
                'error' => null
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi template WhatsApp', [
                'error' => $e->getMessage(),
                'user' => $user->getId(),
                'template' => $input->templateName,
                'recipient' => $input->recipientPhoneNumber
            ]);
            
            return [
                'success' => false,
                'messageId' => null,
                'error' => $e->getMessage()
            ];
        }
    }
}