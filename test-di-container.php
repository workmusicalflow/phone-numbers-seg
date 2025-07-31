<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

echo "🧪 Test du conteneur DI...\n\n";

try {
    echo "1️⃣ Chargement du conteneur DI...\n";
    
    // Charger le DIContainer utilisé par GraphQL
    $container = new \App\GraphQL\DIContainer();
    
    echo "✅ Conteneur DI chargé\n\n";
    
    echo "2️⃣ Test de résolution du WhatsAppServiceInterface...\n";
    
    try {
        $whatsappService = $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface::class);
        echo "✅ WhatsAppServiceInterface résolu avec succès\n";
        echo "Type: " . get_class($whatsappService) . "\n\n";
        
        echo "3️⃣ Test de résolution des autres services WhatsApp...\n";
        
        // Tester WhatsAppServiceEnhanced directement
        try {
            $enhancedService = $container->get(\App\Services\WhatsApp\WhatsAppServiceEnhanced::class);
            echo "✅ WhatsAppServiceEnhanced résolu avec succès\n";
        } catch (\Throwable $e) {
            echo "❌ Erreur WhatsAppServiceEnhanced: " . $e->getMessage() . "\n";
        }
        
        // Tester les autres services
        try {
            $apiClient = $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface::class);
            echo "✅ WhatsAppApiClientInterface résolu avec succès\n";
        } catch (\Throwable $e) {
            echo "❌ Erreur WhatsAppApiClientInterface: " . $e->getMessage() . "\n";
        }
        
    } catch (\Throwable $e) {
        echo "❌ Erreur lors de la résolution du service:\n";
        echo "Type: " . get_class($e) . "\n";
        echo "Message: " . $e->getMessage() . "\n";
        echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
        
        // Vérifier les causes imbriquées
        $previous = $e->getPrevious();
        if ($previous) {
            echo "\nCause racine:\n";
            echo "Type: " . get_class($previous) . "\n";
            echo "Message: " . $previous->getMessage() . "\n";
            echo "Fichier: " . $previous->getFile() . ":" . $previous->getLine() . "\n";
        }
    }
    
} catch (\Throwable $e) {
    echo "❌ Erreur lors du chargement du conteneur:\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
}