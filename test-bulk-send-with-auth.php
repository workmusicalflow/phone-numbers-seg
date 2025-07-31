<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

// Démarrer la session
session_start();

// Initialisation de l'application
$container = require __DIR__ . '/src/bootstrap-rest.php';

try {
    echo "=== TEST BULK SEND AVEC AUTHENTIFICATION ===\n";
    
    // 1. Se connecter en tant qu'admin
    $authService = $container->get(\App\Services\Interfaces\AuthServiceInterface::class);
    
    $loginResult = $authService->authenticate('admin', 'admin123');
    
    if ($loginResult) {
        echo "✅ Connexion réussie\n";
        
        // 2. Vérifier l'authentification
        $user = $authService->getCurrentUser();
        if ($user) {
            echo "✅ Utilisateur: " . $user->getUsername() . " (ID: " . $user->getId() . ")\n";
        }
        
        // 3. Tester le context GraphQL
        $contextFactory = $container->get(\App\GraphQL\Context\GraphQLContextFactory::class);
        $context = $contextFactory->create();
        $contextUser = $context->getCurrentUser();
        
        if ($contextUser) {
            echo "✅ Utilisateur via contexte: " . $contextUser->getUsername() . "\n";
            
            // 4. Simuler un appel à l'API bulk send
            echo "=== SIMULATION APPEL BULK SEND ===\n";
            
            // Simuler les données d'entrée comme celles du frontend
            $testData = [
                'recipients' => ['+2250123456789', '+2250987654321'],
                'templateName' => 'test_template',
                'templateLanguage' => 'fr',
                'bodyVariables' => [],
                'headerVariables' => [],
                'defaultParameters' => [],
                'recipientParameters' => [],
                'options' => [
                    'batchSize' => 10,
                    'continueOnError' => true
                ]
            ];
            
            echo "Données de test: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n";
            echo "✅ Simulation OK - l'authentification fonctionne\n";
            
        } else {
            echo "❌ Problème avec le contexte GraphQL\n";
        }
        
    } else {
        echo "❌ Échec de la connexion\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}