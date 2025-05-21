<?php
/**
 * Script de correctif d'urgence pour le problème de templates WhatsApp
 */

// Emplacement du contrôleur
$controllerPath = __DIR__ . '/../src/GraphQL/Controllers/WhatsApp/WhatsAppEmergencyController.php';

// Créer le contrôleur d'urgence avec templates statiques
$controllerContent = <<<'EOT'
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
EOT;

// Enregistrement du contrôleur
$diRegistrationPath = __DIR__ . '/../src/config/di/emergency.php';
$diRegistrationContent = <<<'EOT'
<?php
/**
 * Configuration de dépendances d'urgence
 */

use Psr\Log\LoggerInterface;

return [
    // Contrôleur d'urgence pour les templates WhatsApp
    'App\\GraphQL\\Controllers\\WhatsApp\\WhatsAppEmergencyController' => \DI\create('App\\GraphQL\\Controllers\\WhatsApp\\WhatsAppEmergencyController')
        ->constructor(
            \DI\get(LoggerInterface::class)
        )
];
EOT;

// Modification du fichier de configuration principal (di.php)
$mainConfigPath = __DIR__ . '/../src/config/di.php';
$mainConfigContent = file_get_contents($mainConfigPath);
$mainConfigUpdated = str_replace(
    '$configFiles = [',
    '$configFiles = [
    $configDir . \'emergency.php\',',
    $mainConfigContent
);

// Écrire les fichiers
file_put_contents($controllerPath, $controllerContent);
file_put_contents($diRegistrationPath, $diRegistrationContent);
file_put_contents($mainConfigPath, $mainConfigUpdated);

echo "✅ Correctif d'urgence appliqué avec succès.\n";
echo "- Contrôleur d'urgence créé: " . $controllerPath . "\n";
echo "- Configuration DI créée: " . $diRegistrationPath . "\n";
echo "- Configuration principale mise à jour\n";
echo "\nVous devez maintenant redémarrer votre serveur PHP pour que les changements prennent effet.\n";
echo "Ensuite, testez la page /frontend/test-templates.html\n";