<?php
/**
 * Test direct de la fonctionnalité de templates WhatsApp
 * 
 * Ce script teste directement les classes impliquées sans passer par GraphQL
 */

// Autoload et bootstrap
require_once __DIR__ . '/../vendor/autoload.php';
$container = require_once __DIR__ . '/../bootstrap.php';

// Récupérer les services
$templateService = $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface::class);
$logger = $container->get(\Psr\Log\LoggerInterface::class);

// 1. Test direct du service de templates
echo "=== TEST DU SERVICE DE TEMPLATES ===\n";
try {
    $templates = $templateService->getApprovedTemplates();
    echo "Résultat du service: " . (is_array($templates) ? "ARRAY" : gettype($templates)) . "\n";
    
    if (is_array($templates)) {
        echo "Nombre de templates: " . count($templates) . "\n";
        if (count($templates) > 0) {
            echo "Premier template: " . json_encode($templates[0], JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "Aucun template (tableau vide)\n";
        }
    } elseif ($templates === null) {
        echo "PROBLÈME: Le service a retourné NULL\n";
    } else {
        echo "Type inattendu: " . gettype($templates) . "\n";
    }
} catch (\Throwable $e) {
    echo "ERREUR lors de l'appel du service: " . $e->getMessage() . "\n";
    echo "Class: " . get_class($e) . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n";

// 2. Test du contrôleur d'urgence
echo "=== TEST DU CONTRÔLEUR D'URGENCE ===\n";
try {
    // Essayer de récupérer notre contrôleur d'urgence
    if ($container->has('App\\GraphQL\\Controllers\\WhatsApp\\WhatsAppEmergencyController')) {
        $emergencyController = $container->get('App\\GraphQL\\Controllers\\WhatsApp\\WhatsAppEmergencyController');
        echo "Contrôleur d'urgence trouvé dans le conteneur\n";
        
        try {
            $user = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class)->findOneByUsername('admin');
            if (!$user) {
                echo "Aucun utilisateur 'admin' trouvé. Test avec un utilisateur fictif.\n";
                // Créer un User fictif si nécessaire
                $user = new \App\Entities\User();
                $reflection = new ReflectionClass($user);
                $idProperty = $reflection->getProperty('id');
                $idProperty->setAccessible(true);
                $idProperty->setValue($user, 1);
            }
            
            $templates = $emergencyController->fetchApprovedWhatsAppTemplates(null, $user);
            echo "Résultat du contrôleur d'urgence: " . (is_array($templates) ? "ARRAY" : gettype($templates)) . "\n";
            echo "Nombre de templates: " . count($templates) . "\n";
        } catch (\Throwable $e) {
            echo "ERREUR lors de l'appel du contrôleur d'urgence: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Contrôleur d'urgence NON TROUVÉ dans le conteneur\n";
        
        // Créer et tester manuellement
        echo "Création manuelle du contrôleur...\n";
        $emergencyController = new \App\GraphQL\Controllers\WhatsApp\WhatsAppEmergencyController($logger);
        
        $user = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class)->findOneByUsername('admin');
        if (!$user) {
            echo "Aucun utilisateur 'admin' trouvé. Test avec un utilisateur fictif.\n";
            // Créer un User fictif si nécessaire
            $user = new \App\Entities\User();
            $reflection = new ReflectionClass($user);
            $idProperty = $reflection->getProperty('id');
            $idProperty->setAccessible(true);
            $idProperty->setValue($user, 1);
        }
        
        try {
            $templates = $emergencyController->fetchApprovedWhatsAppTemplates(null, $user);
            echo "Résultat du contrôleur d'urgence (création manuelle): " . (is_array($templates) ? "ARRAY" : gettype($templates)) . "\n";
            echo "Nombre de templates: " . count($templates) . "\n";
        } catch (\Throwable $e) {
            echo "ERREUR lors de l'appel du contrôleur d'urgence (création manuelle): " . $e->getMessage() . "\n";
            echo "Class: " . get_class($e) . "\n";
            echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        }
    }
} catch (\Throwable $e) {
    echo "ERREUR lors de la récupération du contrôleur: " . $e->getMessage() . "\n";
}

// 3. Test de WhatsAppTemplateSafeType
echo "\n=== TEST DE WhatsAppTemplateSafeType ===\n";
try {
    // Créer un template avec des données nulles ou incomplètes
    $template = new \App\GraphQL\Types\WhatsApp\WhatsAppTemplateSafeType(null);
    echo "Création réussie avec null\n";
    echo "ID: " . $template->getId() . "\n";
    echo "Name: " . $template->getName() . "\n";
    
    // Créer un template avec des données minimales
    $template2 = new \App\GraphQL\Types\WhatsApp\WhatsAppTemplateSafeType([
        'id' => 'test123',
        'name' => 'Test Template'
    ]);
    echo "Création réussie avec données minimales\n";
    echo "ID: " . $template2->getId() . "\n";
    echo "Name: " . $template2->getName() . "\n";
} catch (\Throwable $e) {
    echo "ERREUR lors du test de WhatsAppTemplateSafeType: " . $e->getMessage() . "\n";
}

echo "\nTests terminés.\n";