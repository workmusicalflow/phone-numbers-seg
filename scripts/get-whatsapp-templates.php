<?php

// Script pour récupérer la liste des templates WhatsApp disponibles

// Charger l'autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Configuration de base pour le client WhatsApp
$config = [
    'base_url' => 'https://graph.facebook.com',
    'api_version' => 'v22.0',
    'waba_id' => '664409593123173',
    'access_token' => 'EAAQ93dlFUw4BOZCu6OPmzQuo47pE8eYgGCJLWaQzeyHo03ZCmUWNOQZABt0NeJgVfx9zgurvJc3YynNmFZBgfsCslzydmfzdWZA3onZCyGQsgSo1ZAC6o7ZCgzukF10wmeCjfWcWItPeOw0hanzT0V5ShOIQZCEzVF9qP2aGALaD5ZCTvy95DhjlUwOwijVNAEXpGzEG0YKIsRI8ZCngj9BiXLltt3azinQQYgPBIs9bZA6K'
];

// Fonction de log
function log_message($message, $isError = false) {
    echo ($isError ? "\033[31m[ERREUR]\033[0m " : "\033[32m[INFO]\033[0m ") . $message . PHP_EOL;
}

try {
    log_message("Récupération des templates WhatsApp disponibles");
    
    // Créer un client HTTP
    $httpClient = new \GuzzleHttp\Client([
        'timeout' => 30,
        'connect_timeout' => 30,
    ]);
    
    // URL pour récupérer les templates
    $templatesEndpoint = sprintf(
        '%s/%s/%s/message_templates',
        rtrim($config['base_url'], '/'),
        $config['api_version'],
        $config['waba_id']
    );
    
    log_message("URL de l'API: " . $templatesEndpoint);
    
    // Faire la requête
    $response = $httpClient->get($templatesEndpoint, [
        'headers' => [
            'Authorization' => 'Bearer ' . $config['access_token'],
            'Content-Type' => 'application/json',
        ]
    ]);
    
    $result = json_decode($response->getBody()->getContents(), true);
    
    if (isset($result['data']) && is_array($result['data'])) {
        log_message("Nombre de templates trouvés: " . count($result['data']));
        
        foreach ($result['data'] as $template) {
            $name = $template['name'] ?? 'N/A';
            $status = $template['status'] ?? 'N/A';
            $language = $template['language'] ?? 'N/A';
            $category = $template['category'] ?? 'N/A';
            
            log_message("Template: $name | Statut: $status | Langue: $language | Catégorie: $category");
            
            // Afficher les composants si disponibles
            if (isset($template['components']) && is_array($template['components'])) {
                foreach ($template['components'] as $component) {
                    $type = $component['type'] ?? 'N/A';
                    if ($type === 'BODY' && isset($component['text'])) {
                        $text = substr($component['text'], 0, 50) . (strlen($component['text']) > 50 ? '...' : '');
                        log_message("  - Corps: $text");
                    }
                }
            }
            
            log_message("---");
        }
    } else {
        log_message("Aucun template trouvé ou format de réponse inattendu", true);
        log_message("Réponse complète: " . json_encode($result, JSON_PRETTY_PRINT));
    }
    
} catch (\Exception $e) {
    log_message("Erreur lors de la récupération des templates: " . $e->getMessage(), true);
    
    // Si c'est une erreur HTTP, essayons d'afficher plus de détails
    if ($e instanceof \GuzzleHttp\Exception\ClientException) {
        $response = $e->getResponse();
        if ($response) {
            $body = $response->getBody()->getContents();
            log_message("Détails de l'erreur API: " . $body, true);
        }
    }
}