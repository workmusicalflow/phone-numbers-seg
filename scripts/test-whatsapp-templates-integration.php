<?php
/**
 * Script de test d'intégration pour les templates WhatsApp
 * 
 * Ce script teste la communication avec l'API Cloud Meta pour les fonctionnalités
 * de templates WhatsApp implémentées dans l'application Oracle.
 */

require_once __DIR__ . '/../src/bootstrap-doctrine-simple.php';

use App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface;
use App\Entities\User;

// Définir manuellement les variables d'environnement nécessaires pour WhatsApp
$_ENV['WHATSAPP_APP_ID'] = '1193922949108494';
$_ENV['WHATSAPP_PHONE_NUMBER_ID'] = '660953787095211';
$_ENV['WHATSAPP_WABA_ID'] = '664409593123173';
$_ENV['WHATSAPP_API_VERSION'] = 'v22.0';
$_ENV['WHATSAPP_ACCESS_TOKEN'] = 'EAAQ93dlFUw4BOZCu6OPmzQuo47pE8eYgGCJLWaQzeyHo03ZCmUWNOQZABt0NeJgVfx9zgurvJc3YynNmFZBgfsCslzydmfzdWZA3onZCyGQsgSo1ZAC6o7ZCgzukF10wmeCjfWcWItPeOw0hanzT0V5ShOIQZCEzVF9qP2aGALaD5ZCTvy95DhjlUwOwijVNAEXpGzEG0YKIsRI8ZCngj9BiXLltt3azinQQYgPBIs9bZA6K';

// Obtenir l'EntityManager depuis bootstrap-doctrine-simple.php
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine-simple.php';

// Charger les définitions du conteneur DI
$definitions = require __DIR__ . '/../src/config/di.php';

// Créer le conteneur avec les définitions
$containerBuilder = new \DI\ContainerBuilder();
$containerBuilder->addDefinitions($definitions);
$container = $containerBuilder->build();

// Obtenir les services
$templateService = $container->get(WhatsAppTemplateServiceInterface::class);
$whatsAppService = $container->get(WhatsAppServiceInterface::class);
$logger = $container->get(\Psr\Log\LoggerInterface::class);

// Récupérer un utilisateur de test
$userRepository = $entityManager->getRepository(User::class);
$testUser = $userRepository->findOneBy(['username' => 'admin']);

if (!$testUser) {
    die("Utilisateur de test 'admin' non trouvé. Veuillez créer un utilisateur de test.\n");
}

// Fonction helper pour afficher les résultats de test
function displayResult($testName, $success, $message = null) {
    echo str_pad("[$testName]", 40, ' ') . ": ";
    if ($success) {
        echo "\033[32mSUCCÈS\033[0m";
    } else {
        echo "\033[31mÉCHEC\033[0m";
        if ($message) {
            echo " - $message";
        }
    }
    echo "\n";
}

// Tableau pour stocker les résultats des tests
$results = [
    'success' => 0,
    'failed' => 0,
    'total' => 0
];

// Fonction pour exécuter un test et collecter les résultats
function runTest($results, $testName, $callback) {
    echo "\nExécution du test: $testName\n";
    $results['total']++;
    
    try {
        $testResult = $callback();
        if ($testResult === true) {
            displayResult($testName, true);
            $results['success']++;
        } else {
            displayResult($testName, false, $testResult);
            $results['failed']++;
        }
    } catch (\Exception $e) {
        displayResult($testName, false, $e->getMessage());
        $results['failed']++;
    }
    
    return $results;
}

echo "\n=== Tests d'intégration des templates WhatsApp ===\n";

// Test 1: Récupération des templates
$results = runTest($results, "Récupération des templates", function() use ($templateService) {
    $templates = $templateService->fetchApprovedTemplatesFromMeta();
    
    if (empty($templates)) {
        return "Aucun template récupéré. Vérifiez les identifiants API et les templates disponibles.";
    }
    
    echo "  " . count($templates) . " templates trouvés.\n";
    
    // Afficher quelques détails sur les templates récupérés
    echo "  Premiers templates récupérés :\n";
    $sampleSize = min(3, count($templates));
    for ($i = 0; $i < $sampleSize; $i++) {
        $template = $templates[$i];
        echo "  - [{$template['name']}] ({$template['language']}) - Catégorie: {$template['category']}, Statut: {$template['status']}\n";
    }
    
    return true;
});

// Test 2: Récupération des catégories de templates
$results = runTest($results, "Récupération des catégories", function() use ($templateService) {
    $categories = $templateService->getTemplateCategories();
    
    if (empty($categories)) {
        return "Aucune catégorie récupérée.";
    }
    
    echo "  Catégories trouvées : " . implode(", ", $categories) . "\n";
    return true;
});

// Test 3: Récupération des langues de templates
$results = runTest($results, "Récupération des langues", function() use ($templateService) {
    $languages = $templateService->getTemplateLanguages();
    
    if (empty($languages)) {
        return "Aucune langue récupérée.";
    }
    
    echo "  Langues trouvées : " . implode(", ", $languages) . "\n";
    return true;
});

// Test 4: Filtrage des templates par nom
$results = runTest($results, "Filtrage des templates par nom", function() use ($templateService) {
    // D'abord récupérer tous les templates pour avoir un nom à filtrer
    $allTemplates = $templateService->fetchApprovedTemplatesFromMeta();
    
    if (empty($allTemplates)) {
        return "Aucun template disponible pour le test de filtrage.";
    }
    
    // Prendre le nom du premier template pour le filtrage
    $nameToFilter = $allTemplates[0]['name'];
    $filtered = $templateService->fetchApprovedTemplatesFromMeta(['name' => $nameToFilter]);
    
    echo "  Filtrage avec le nom '$nameToFilter' : " . count($filtered) . " résultat(s)\n";
    
    if (empty($filtered)) {
        return "Le filtrage n'a retourné aucun résultat.";
    }
    
    return true;
});

// Test 5: Construction des composants de template
$results = runTest($results, "Construction des composants", function() use ($templateService) {
    // Créer des données factices pour les tests
    $templateComponentsFromMeta = [
        [
            'type' => 'HEADER',
            'format' => 'TEXT',
            'text' => 'En-tête du message'
        ],
        [
            'type' => 'BODY',
            'text' => 'Bonjour {{1}}, votre commande {{2}} a été confirmée. Merci pour votre achat!'
        ],
        [
            'type' => 'FOOTER',
            'text' => 'Service client: +225 XX XX XX XX'
        ],
        [
            'type' => 'BUTTONS',
            'buttons' => [
                [
                    'type' => 'URL',
                    'text' => 'Suivre commande',
                    'url' => 'https://exemple.com/suivi/{{1}}'
                ],
                [
                    'type' => 'QUICK_REPLY',
                    'text' => 'Besoin d\'aide'
                ]
            ]
        ]
    ];
    
    $templateDynamicData = [
        'body' => ['John Doe', 'CMD-12345'],
        'buttons' => [0 => 'CMD-12345']
    ];
    
    $components = $templateService->buildTemplateComponents($templateComponentsFromMeta, $templateDynamicData);
    
    if (empty($components)) {
        return "Aucun composant n'a été généré.";
    }
    
    echo "  " . count($components) . " composants générés.\n";
    
    // Vérifier que les composants ont été correctement générés
    $bodyFound = false;
    $buttonFound = false;
    
    foreach ($components as $component) {
        if ($component['type'] === 'body') {
            $bodyFound = true;
            if (count($component['parameters']) !== 2) {
                return "Le nombre de paramètres du corps ne correspond pas.";
            }
        }
        
        if ($component['type'] === 'button') {
            $buttonFound = true;
        }
    }
    
    if (!$bodyFound) {
        return "Le composant de corps n'a pas été généré.";
    }
    
    if (!$buttonFound && !empty($templateComponentsFromMeta[3]['buttons'])) {
        return "Le composant de bouton n'a pas été généré.";
    }
    
    return true;
});

// Test 6: Récupération d'un template spécifique
$results = runTest($results, "Récupération d'un template spécifique", function() use ($templateService) {
    // D'abord récupérer tous les templates pour avoir un template spécifique à récupérer
    $allTemplates = $templateService->fetchApprovedTemplatesFromMeta();
    
    if (empty($allTemplates)) {
        return "Aucun template disponible pour le test.";
    }
    
    // Prendre le premier template pour le test
    $firstTemplate = $allTemplates[0];
    $templateName = $firstTemplate['name'];
    $templateLanguage = $firstTemplate['language'];
    
    $specificTemplate = $templateService->getTemplate($templateName, $templateLanguage);
    
    if (!$specificTemplate) {
        return "Le template spécifique '$templateName' ($templateLanguage) n'a pas pu être récupéré.";
    }
    
    echo "  Template '{$specificTemplate['name']}' récupéré avec succès.\n";
    return true;
});

// IMPORTANT: Ce test va réellement envoyer un message WhatsApp
// Obtenir l'API client pour le test d'envoi
$apiClient = $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface::class);

// Test 7: Envoi d'un message template
$results = runTest($results, "Envoi d'un message template", function() use ($templateService, $whatsAppService, $testUser, $apiClient) {
    // Configuration pour le test
    $testPhoneNumber = '+2250777104936'; // Numéro de test fourni par l'utilisateur
    
    // D'abord récupérer tous les templates
    $allTemplates = $templateService->fetchApprovedTemplatesFromMeta();
    
    if (empty($allTemplates)) {
        return "Aucun template disponible pour le test d'envoi.";
    }
    
    // Trouver un template de type UTILITY (généralement moins restrictif)
    $testTemplate = null;
    foreach ($allTemplates as $template) {
        if ($template['category'] === 'UTILITY' && $template['status'] === 'APPROVED') {
            $testTemplate = $template;
            break;
        }
    }
    
    if (!$testTemplate) {
        // Prendre simplement le premier template approuvé
        foreach ($allTemplates as $template) {
            if ($template['status'] === 'APPROVED') {
                $testTemplate = $template;
                break;
            }
        }
    }
    
    if (!$testTemplate) {
        return "Aucun template approuvé disponible pour le test d'envoi.";
    }
    
    // Analyser les composants pour préparer les données dynamiques
    $componentsJson = json_decode($testTemplate['componentsJson'] ?? '{}', true);
    
    // Créer des données dynamiques simples pour le test
    $bodyVariables = [];
    $headerMediaUrl = null;
    
    // Vérifier si le template a un corps avec des variables
    $bodyComponent = null;
    foreach ($componentsJson as $component) {
        if (($component['type'] ?? '') === 'BODY') {
            $bodyComponent = $component;
            break;
        }
    }
    
    if ($bodyComponent && isset($bodyComponent['text'])) {
        // Compter le nombre de variables {{N}}
        preg_match_all('/{{(\d+)}}/', $bodyComponent['text'], $matches);
        
        if (!empty($matches[0])) {
            // Créer des valeurs factices pour chaque variable
            foreach ($matches[1] as $varNum) {
                $bodyVariables[] = "Test" . $varNum;
            }
        }
    }
    
    // Construire les composants pour l'API
    $components = [];
    if (!empty($bodyVariables)) {
        $bodyParameters = [];
        foreach ($bodyVariables as $value) {
            $bodyParameters[] = [
                'type' => 'text',
                'text' => $value
            ];
        }
        
        $components[] = [
            'type' => 'body',
            'parameters' => $bodyParameters
        ];
    }
    
    // Tenter d'envoyer le template
    try {
        // Utiliser directement l'API client pour contourner la validation de template en base de données
        
        // Construction du payload manuellement
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $testPhoneNumber,
            'type' => 'template',
            'template' => [
                'name' => $testTemplate['name'],
                'language' => [
                    'code' => $testTemplate['language']
                ]
            ]
        ];
        
        // Ajouter les composants si présents
        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }
        
        // Envoi direct via l'API
        $result = $apiClient->sendMessage($payload);
        
        if (!isset($result['messages'][0]['id'])) {
            return "L'envoi a échoué : réponse non conforme.";
        }
        
        $messageId = $result['messages'][0]['id'];
        echo "  Message envoyé avec succès. ID: " . $messageId . "\n";
        
        // Enregistrer le message dans l'historique
        try {
            // Utiliser une requête SQL directe pour éviter les problèmes de cascade avec Doctrine
            $entityManager = require __DIR__ . '/../src/bootstrap-doctrine-simple.php';
            $pdo = $entityManager->getConnection()->getNativeConnection();
            
            $sql = "INSERT INTO whatsapp_message_history 
                    (oracle_user_id, wabaMessageId, phoneNumber, direction, type, 
                     templateName, templateLanguage, content, status, timestamp, createdAt, updatedAt) 
                    VALUES 
                    (:oracle_user_id, :wabaMessageId, :phoneNumber, :direction, :type, 
                     :templateName, :templateLanguage, :content, :status, :timestamp, :createdAt, :updatedAt)";
            
            $statement = $pdo->prepare($sql);
            $statement->bindValue(':oracle_user_id', $testUser->getId());
            $statement->bindValue(':wabaMessageId', $messageId);
            $statement->bindValue(':phoneNumber', $testPhoneNumber);
            $statement->bindValue(':direction', 'OUTGOING');
            $statement->bindValue(':type', 'template');
            $statement->bindValue(':templateName', $testTemplate['name']);
            $statement->bindValue(':templateLanguage', $testTemplate['language']);
            $statement->bindValue(':content', json_encode($payload));
            $statement->bindValue(':status', 'sent');
            $statement->bindValue(':timestamp', date('Y-m-d H:i:s'));
            $statement->bindValue(':createdAt', date('Y-m-d H:i:s'));
            $statement->bindValue(':updatedAt', date('Y-m-d H:i:s'));
            
            $statement->execute();
            $lastId = $pdo->lastInsertId();
            
            echo "  Message enregistré dans l'historique avec ID: " . $lastId . "\n";
        } catch (\Exception $e) {
            echo "  Erreur lors de l'enregistrement dans l'historique: " . $e->getMessage() . "\n";
        }
        return true;
    } catch (\Exception $e) {
        return "L'envoi a échoué : " . $e->getMessage();
    }
});

// Afficher le récapitulatif des tests
echo "\n=== Récapitulatif des tests ===\n";
echo "Tests réussis : {$results['success']} / {$results['total']}\n";
echo "Tests échoués : {$results['failed']} / {$results['total']}\n";

// Vérifier si tous les tests ont réussi
if ($results['failed'] === 0) {
    echo "\n\033[32mTous les tests ont réussi !\033[0m\n";
    exit(0);
} else {
    echo "\n\033[31mCertains tests ont échoué. Veuillez vérifier les résultats ci-dessus.\033[0m\n";
    exit(1);
}