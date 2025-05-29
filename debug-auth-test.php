<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

// Démarrer la session
session_start();

// Initialisation de l'application
$container = require __DIR__ . '/src/bootstrap-rest.php';

try {
    echo "=== DEBUG AUTHENTIFICATION ===\n";
    echo "Session ID: " . session_id() . "\n";
    echo "Session data: " . print_r($_SESSION, true) . "\n";
    
    // Test du service d'authentification
    $authService = $container->get(\App\Services\Interfaces\AuthServiceInterface::class);
    
    echo "Authentifié: " . ($authService->isAuthenticated() ? 'OUI' : 'NON') . "\n";
    
    $user = $authService->getCurrentUser();
    if ($user) {
        echo "Utilisateur: " . $user->getUsername() . " (ID: " . $user->getId() . ")\n";
    } else {
        echo "Aucun utilisateur connecté\n";
    }
    
    // Test du contexte GraphQL
    $contextFactory = $container->get(\App\GraphQL\Context\GraphQLContextFactory::class);
    $context = $contextFactory->create();
    $contextUser = $context->getCurrentUser();
    
    if ($contextUser) {
        echo "Utilisateur via contexte: " . $contextUser->getUsername() . " (ID: " . $contextUser->getId() . ")\n";
    } else {
        echo "Aucun utilisateur via contexte\n";
    }
    
} catch (\Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}