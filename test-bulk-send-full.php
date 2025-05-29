<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

// Démarrer la session
session_start();

// Initialisation de l'application
$container = require __DIR__ . '/src/bootstrap-rest.php';

try {
    echo "=== TEST COMPLET BULK SEND ===\n";
    
    // 1. Se connecter en tant qu'admin
    $authService = $container->get(\App\Services\Interfaces\AuthServiceInterface::class);
    $user = $authService->authenticate('admin', 'admin123');
    
    if (!$user) {
        die("❌ Échec de la connexion\n");
    }
    
    echo "✅ Connecté en tant que: " . $user->getUsername() . "\n";
    echo "Session ID: " . session_id() . "\n";
    
    // 2. Simuler l'appel à l'API bulk-send en incluant directement le code
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    // Simuler les données JSON en entrée
    $testData = [
        'recipients' => ['+2250123456789', '+2250987654321'],
        'templateName' => 'hello_world',
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
    
    // Simuler php://input
    $jsonData = json_encode($testData);
    
    echo "=== DONNÉES D'ENTRÉE ===\n";
    echo json_encode($testData, JSON_PRETTY_PRINT) . "\n";
    
    // 3. Tester directement la logique du bulk-send
    echo "=== TEST LOGIQUE BULK SEND ===\n";
    
    // Vérifier l'authentification via GraphQL Context
    $contextFactory = $container->get(\App\GraphQL\Context\GraphQLContextFactory::class);
    $context = $contextFactory->create();
    $contextUser = $context->getCurrentUser();
    
    if (!$contextUser) {
        die("❌ Utilisateur non authentifié via contexte\n");
    }
    
    echo "✅ Utilisateur authentifié via contexte: " . $contextUser->getUsername() . "\n";
    
    // Valider les données requises
    if (empty($testData['recipients']) || !is_array($testData['recipients'])) {
        die("❌ La liste des destinataires est requise\n");
    }
    
    if (empty($testData['templateName'])) {
        die("❌ Le nom du template est requis\n");
    }
    
    echo "✅ Validation des données OK\n";
    
    // Vérifier si la commande BulkSendTemplateCommand existe
    if (!class_exists(\App\Services\WhatsApp\Commands\BulkSendTemplateCommand::class)) {
        echo "⚠️  Classe BulkSendTemplateCommand non trouvée\n";
        echo "⚠️  Cette classe doit être créée pour l'envoi en masse\n";
    } else {
        echo "✅ Classe BulkSendTemplateCommand trouvée\n";
    }
    
    // Vérifier si le CommandBus existe
    if (!$container->has('whatsapp.command_bus.bulk')) {
        echo "⚠️  CommandBus 'whatsapp.command_bus.bulk' non configuré\n";
        echo "⚠️  Le CommandBus doit être configuré dans le DI container\n";
    } else {
        echo "✅ CommandBus configuré\n";
    }
    
    echo "\n=== RÉSUMÉ ===\n";
    echo "✅ Authentification: OK\n";
    echo "✅ Validation des données: OK\n";
    echo "⚠️  Implémentation complète: En cours\n";
    echo "\nLe problème principal est que l'utilisateur doit être connecté dans le frontend.\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}