<?php

/**
 * Script pour vérifier les templates disponibles dans l'API Meta
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap-doctrine.php';

// Créer le conteneur DI
$container = new App\GraphQL\DIContainer();
$apiClient = $container->get(App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface::class);

echo "Récupération des templates depuis l'API Meta...\n";
echo "===========================================\n\n";

try {
    $templates = $apiClient->getTemplates();
    
    echo "Templates disponibles:\n";
    foreach ($templates as $index => $template) {
        echo "\n" . ($index + 1) . ". Template:\n";
        echo "   ID: " . ($template['id'] ?? 'N/A') . "\n";
        echo "   Name: " . ($template['name'] ?? 'N/A') . "\n"; 
        echo "   Language: " . ($template['language'] ?? 'N/A') . "\n";
        echo "   Status: " . ($template['status'] ?? 'N/A') . "\n";
        echo "   Category: " . ($template['category'] ?? 'N/A') . "\n";
        
        if (isset($template['components'])) {
            echo "   Components:\n";
            foreach ($template['components'] as $component) {
                echo "     - Type: " . $component['type'] . "\n";
                if ($component['type'] === 'BODY' && isset($component['text'])) {
                    echo "       Text: " . substr($component['text'], 0, 50) . "...\n";
                }
                if ($component['type'] === 'HEADER' && isset($component['format'])) {
                    echo "       Format: " . $component['format'] . "\n";
                }
            }
        }
    }
    
} catch (\Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    echo "Trace : " . $e->getTraceAsString() . "\n";
}

echo "\nTerminé.\n";