<?php

// Define application root and include necessary files
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/vendor/autoload.php';

echo "=== Test du contrôleur WhatsApp après refactorisation ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Create DI container
$container = new \App\GraphQL\DIContainer();

try {
    // Get the controller from DI
    $whatsAppController = $container->get(\App\Controllers\WhatsAppController::class);
    
    // Verify that the controller is of the expected type
    echo "Vérification du type de contrôleur:\n";
    echo "- WhatsAppController: " . get_class($whatsAppController) . "\n\n";
    
    // Mock user for testing
    $user = new \App\Entities\User();
    $user->setUsername('testuser');
    $user->setPassword(password_hash('password', PASSWORD_DEFAULT));
    $user->setEmail('test@example.com');
    $user->setIsAdmin(false);
    $user->setSmsCredit(100);
    $user->setSmsLimit(1000);
    $user->generateApiKey();
    
    // Manually set ID using reflection since it's usually auto-generated
    $reflection = new ReflectionClass($user);
    $idProperty = $reflection->getProperty('id');
    $idProperty->setAccessible(true);
    $idProperty->setValue($user, 1);
    
    // Test the controller method
    echo "Test de WhatsAppController->getApprovedTemplates():\n";
    try {
        $params = [
            'force_meta' => true,
            'force_refresh' => true,
            'use_cache' => false,
            'debug' => true
        ];
        $result = $whatsAppController->getApprovedTemplates($user, $params);
        
        // Vérifier la structure de la réponse
        echo "Structure de la réponse: " . json_encode(array_keys($result)) . "\n";
        echo "Source des templates: " . $result['meta']['source'] . "\n";
        echo "Nombre de templates: " . $result['count'] . "\n";
        echo "Fallback utilisé: " . ($result['meta']['usedFallback'] ? 'Oui' : 'Non') . "\n";
        
        if ($result['status'] === 'success' && $result['count'] > 0) {
            echo "✅ SUCCÈS: " . $result['count'] . " templates récupérés via le contrôleur\n";
            echo "Premier template: " . ($result['templates'][0]['name'] ?? 'N/A') . "\n\n";
        } else {
            echo "❌ ÉCHEC: Aucun template récupéré ou statut d'erreur\n\n";
        }
    } catch (\Exception $e) {
        echo "❌ ÉCHEC: " . $e->getMessage() . "\n\n";
    }
    
    echo "Tests terminés avec succès!\n";
    
} catch (\Exception $e) {
    echo "❌ ERREUR CRITIQUE: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}