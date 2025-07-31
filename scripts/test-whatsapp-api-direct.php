<?php
/**
 * Test direct de l'API WhatsApp
 */

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;

// Charger les variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Configuration
$config = require __DIR__ . '/../src/config/whatsapp.php';

echo "=== Test direct de l'API WhatsApp ===\n\n";

// Vérifier la configuration
echo "Configuration:\n";
echo "Phone Number ID: " . ($config['phone_number_id'] ?? 'Non défini') . "\n";
echo "Access Token: " . (isset($config['access_token']) ? 'Défini (' . strlen($config['access_token']) . ' caractères)' : 'Non défini') . "\n";
echo "API Version: " . ($config['api_version'] ?? 'Non défini') . "\n\n";

if (!isset($config['access_token']) || !isset($config['phone_number_id'])) {
    echo "Erreur: Configuration incomplète\n";
    exit(1);
}

// Créer le client HTTP
$client = new Client([
    'base_uri' => $config['base_url'] ?? 'https://graph.facebook.com/',
    'timeout' => 30,
    'headers' => [
        'Authorization' => 'Bearer ' . $config['access_token'],
        'Content-Type' => 'application/json'
    ]
]);

// Payload pour envoyer un message
$payload = [
    'messaging_product' => 'whatsapp',
    'to' => '+2250777104936',
    'type' => 'text',
    'text' => [
        'body' => 'Test direct API WhatsApp - ' . date('H:i:s')
    ]
];

echo "Payload:\n" . json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

// Endpoint
$endpoint = $config['api_version'] . '/' . $config['phone_number_id'] . '/messages';
echo "Endpoint: " . $endpoint . "\n\n";

try {
    // Envoyer la requête
    echo "Envoi de la requête...\n";
    $response = $client->post($endpoint, [
        'json' => $payload
    ]);
    
    // Récupérer la réponse
    $statusCode = $response->getStatusCode();
    $body = $response->getBody()->getContents();
    $result = json_decode($body, true);
    
    echo "Status HTTP: " . $statusCode . "\n";
    echo "Réponse:\n" . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    if (isset($result['messages'][0]['id'])) {
        echo "✅ Message envoyé avec succès!\n";
        echo "Message ID: " . $result['messages'][0]['id'] . "\n";
    } else {
        echo "⚠️  Réponse inattendue de l'API\n";
    }
    
} catch (\GuzzleHttp\Exception\ClientException $e) {
    // Erreur 4xx
    echo "❌ Erreur client (4xx): " . $e->getMessage() . "\n";
    echo "Réponse: " . $e->getResponse()->getBody()->getContents() . "\n";
} catch (\GuzzleHttp\Exception\ServerException $e) {
    // Erreur 5xx
    echo "❌ Erreur serveur (5xx): " . $e->getMessage() . "\n";
    echo "Réponse: " . $e->getResponse()->getBody()->getContents() . "\n";
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}