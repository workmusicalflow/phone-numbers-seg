<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Configuration simple
$config = [
    'base_url' => 'https://graph.facebook.com/',
    'api_version' => 'v18.0',
    'access_token' => 'TEST_ACCESS_TOKEN',
    'phone_number_id' => '123456789',
    'whatsapp_business_account_id' => '987654321'
];

// Logger Monolog
$logger = new Logger('whatsapp');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

try {
    echo "=== Test du client API WhatsApp ===\n\n";
    
    // Test de la création du client
    echo "1. Création du client API WhatsApp...\n";
    $client = new \App\Services\WhatsApp\WhatsAppApiClient($logger, $config);
    echo "✓ Client créé avec succès\n\n";
    
    // Test de la structure de message
    echo "2. Test de la structure d'un message texte...\n";
    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => '22507000000',
        'type' => 'text',
        'text' => [
            'body' => 'Test message'
        ]
    ];
    echo "Payload: " . json_encode($payload, JSON_PRETTY_PRINT) . "\n";
    
    echo "\n=== Tests terminés avec succès ===\n";
    
} catch (Exception $e) {
    echo "\nErreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}