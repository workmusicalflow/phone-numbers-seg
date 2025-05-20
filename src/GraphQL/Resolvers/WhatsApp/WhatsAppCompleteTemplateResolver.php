<?php

declare(strict_types=1);

namespace App\GraphQL\Resolvers\WhatsApp;

use App\GraphQL\Context\GraphQLContext;
use App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface;
use App\Exceptions\ForbiddenException;
use Psr\Log\LoggerInterface;

/**
 * Resolver GraphQL pour les templates WhatsApp
 * 
 * Implémentation basée sur l'approche de "chargement direct" qui récupère
 * les templates directement depuis l'API Meta sans stockage en base de données.
 */
class WhatsAppCompleteTemplateResolver
{
    private WhatsAppTemplateServiceInterface $templateService;
    private LoggerInterface $logger;

    public function __construct(
        WhatsAppTemplateServiceInterface $templateService,
        LoggerInterface $logger
    ) {
        $this->templateService = $templateService;
        $this->logger = $logger;
    }

    /**
     * Récupère tous les templates WhatsApp disponibles via l'API Meta directement
     * 
     * @Query(name="getWhatsAppUserTemplates")
     * @return array
     */
    public function getWhatsAppUserTemplates(
        ?string $name = null,
        ?string $language = null,
        ?string $category = null,
        ?GraphQLContext $context = null
    ): array {
        $user = $context?->getCurrentUser();
        if ($user === null) {
            throw new ForbiddenException('Utilisateur non authentifié');
        }

        $this->logger->info('Récupération des templates depuis l\'API Meta', [
            'user_id' => $user->getId(),
            'name' => $name,
            'language' => $language,
            'category' => $category
        ]);

        // Définir les filtres basés sur les paramètres
        $filters = [];
        if ($name !== null) {
            $filters['name'] = $name;
        }
        if ($language !== null) {
            $filters['language'] = $language;
        }
        if ($category !== null) {
            $filters['category'] = $category;
        }

        // Récupérer les templates via l'approche de chargement direct
        $templates = $this->templateService->fetchApprovedTemplatesFromMeta($filters);

        // Logger les templates pour le débogage
        $this->logger->debug('Templates récupérés depuis l\'API Meta', [
            'templates_count' => count($templates),
            'templates_sample' => array_slice($templates, 0, 2)
        ]);

        // Transformer les résultats pour correspondre à la structure attendue par GraphQL
        return array_map(function($template) {
            // Générer un ID unique pour le template si aucun n'est fourni
            $templateId = isset($template['id']) && !empty($template['id']) 
                ? $template['id'] 
                : 'meta_template_' . md5($template['name'] . $template['language']);
            
            return [
                'id' => md5($template['name'] . $template['language']), // Générer un ID unique
                'template_id' => $templateId, // Assurez-vous que ce n'est jamais null ou vide
                'name' => $template['name'] ?? 'Unnamed Template',
                'language' => $template['language'] ?? 'unknown',
                'status' => $template['status'] ?? 'UNKNOWN'
            ];
        }, $templates);
    }
}