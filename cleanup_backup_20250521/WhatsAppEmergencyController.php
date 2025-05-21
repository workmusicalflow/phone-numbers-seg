<?php

declare(strict_types=1);

namespace App\GraphQL\Controllers\WhatsApp;

use App\Entities\User;
use App\GraphQL\Types\WhatsApp\TemplateFilterInput;
use App\GraphQL\Types\WhatsApp\WhatsAppTemplateSafeType;
use Psr\Log\LoggerInterface;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

/**
 * Contrôleur GraphQL d'urgence pour les templates WhatsApp
 * 
 * Fournit des templates prédéfinis pour le fonctionnement quand l'API est indisponible
 */
#[Type]
class WhatsAppEmergencyController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Récupère des templates WhatsApp prédéfinis (solution d'urgence)
     */
    #[Query(name: "fetchApprovedWhatsAppTemplates")]
    #[Logged]
    public function fetchApprovedWhatsAppTemplates(
        ?TemplateFilterInput $filter = null,
        #[InjectUser] ?User $user = null
    ): array {
        if (!$user) {
            throw new GraphQLException("Authentification requise", 401);
        }
        
        $this->logger->info("[URGENCE] Utilisation des templates prédéfinis", [
            'user' => $user->getId()
        ]);
        
        // Templates prédéfinis en dur
        $templates = [
            [
                'id' => 'emergency_1',
                'name' => 'template_accueil',
                'category' => 'UTILITY',
                'language' => 'fr',
                'status' => 'APPROVED',
                'components' => [
                    [
                        'type' => 'BODY',
                        'text' => 'Bonjour {{1}}! Bienvenue chez nous.'
                    ]
                ]
            ],
            [
                'id' => 'emergency_2',
                'name' => 'template_confirmation',
                'category' => 'UTILITY',
                'language' => 'fr',
                'status' => 'APPROVED',
                'components' => [
                    [
                        'type' => 'BODY',
                        'text' => 'Votre demande a été confirmée. Référence: {{1}}'
                    ]
                ]
            ],
            [
                'id' => 'emergency_3',
                'name' => 'template_information',
                'category' => 'MARKETING',
                'language' => 'fr',
                'status' => 'APPROVED',
                'components' => [
                    [
                        'type' => 'HEADER',
                        'format' => 'TEXT',
                        'text' => 'Information'
                    ],
                    [
                        'type' => 'BODY',
                        'text' => 'Voici les informations sur {{1}}'
                    ]
                ]
            ],
        ];
        
        // Appliquer les filtres
        if ($filter) {
            if ($filter->name) {
                $templates = array_filter($templates, function($t) use ($filter) {
                    return stripos($t['name'], $filter->name) !== false;
                });
            }
            
            if ($filter->category) {
                $templates = array_filter($templates, function($t) use ($filter) {
                    return $t['category'] === $filter->category;
                });
            }
            
            if ($filter->language) {
                $templates = array_filter($templates, function($t) use ($filter) {
                    return $t['language'] === $filter->language;
                });
            }
        }
        
        // Convertir en WhatsAppTemplateSafeType
        $result = [];
        foreach ($templates as $template) {
            $result[] = new WhatsAppTemplateSafeType($template);
        }
        
        return $result;
    }
}