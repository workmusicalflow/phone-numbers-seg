<?php
/**
 * Script pour vérifier si un numéro de téléphone est enregistré sur WhatsApp
 * via l'API WhatsApp Business
 * 
 * Note: Cette vérification est indirecte. WhatsApp ne fournit pas d'API spécifique pour 
 * vérifier si un numéro est enregistré, mais nous pouvons le déduire à partir des réponses d'erreur
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\PhoneNumberNormalizerService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

// Récupérer le numéro à vérifier
$phoneNumber = $argv[1] ?? null;

if (!$phoneNumber) {
    echo "Entrez le numéro de téléphone à vérifier : ";
    $phoneNumber = trim(fgets(STDIN));
}

// Charger la configuration
$config = require __DIR__ . '/../src/config/whatsapp.php';

// Vérifier la configuration
if (empty($config['phone_number_id']) || empty($config['access_token'])) {
    echo "Erreur : Configuration WhatsApp incomplète\n";
    exit(1);
}

// Créer le service de normalisation
$normalizer = new PhoneNumberNormalizerService();

// Normaliser le numéro
$normalizedNumber = $normalizer->normalize($phoneNumber);

echo "=== Vérification de l'enregistrement WhatsApp ===\n";
echo "Numéro testé : $phoneNumber\n";
echo "Numéro normalisé : $normalizedNumber\n\n";

// Créer le client HTTP
$httpClient = new Client([
    'base_uri' => $config['base_url'] ?? 'https://graph.facebook.com/',
    'timeout' => 10,
    'headers' => [
        'Authorization' => 'Bearer ' . $config['access_token'],
        'Content-Type' => 'application/json'
    ]
]);

// Endpoint pour vérifier le numéro (nous utilisons l'envoi de message comme méthode de vérification)
$endpoint = $config['api_version'] . '/' . $config['phone_number_id'] . '/messages';

// Préparer le payload avec un test de template minimal
// C'est la méthode la plus efficace car elle ne nécessite pas de fenêtre de 24h
$payload = [
    'messaging_product' => 'whatsapp',
    'recipient_type' => 'individual',
    'to' => $normalizedNumber,
    'type' => 'template',
    'template' => [
        'name' => 'hello_world',  // Utiliser un template approuvé simple
        'language' => [
            'code' => 'fr'
        ]
    ]
];

try {
    // Envoyer la requête
    $response = $httpClient->post($endpoint, [
        'json' => $payload
    ]);
    
    $result = json_decode($response->getBody()->getContents(), true);
    
    if (isset($result['messages'][0]['id'])) {
        echo "✓ Le numéro $normalizedNumber est enregistré sur WhatsApp!\n";
        echo "Message envoyé avec ID: " . $result['messages'][0]['id'] . "\n";
        echo "Ce numéro a reçu un message template 'hello_world'.\n";
    } else {
        echo "? Réponse inattendue de l'API. Le statut n'a pas pu être déterminé.\n";
        echo "Détails: " . json_encode($result) . "\n";
    }
    
} catch (ClientException $e) {
    $errorBody = $e->getResponse()->getBody()->getContents();
    $errorData = json_decode($errorBody, true);
    
    $errorMessage = $errorData['error']['message'] ?? '';
    $errorCode = $errorData['error']['code'] ?? '';
    
    // Analyser le message d'erreur pour déterminer le statut
    if (strpos($errorMessage, 'not a valid WhatsApp Business Account') !== false) {
        echo "✗ Le numéro $normalizedNumber n'est PAS enregistré sur WhatsApp.\n";
        echo "Erreur: $errorMessage\n";
    } else if (strpos($errorMessage, 'failed to accept the message') !== false) {
        echo "✗ Le numéro $normalizedNumber n'a pas accepté les messages WhatsApp (restrictions de confidentialité).\n";
        echo "Erreur: $errorMessage\n";
    } else if (strpos($errorMessage, 'rate limit') !== false) {
        echo "! Limite de taux dépassée. Réessayez plus tard.\n";
        echo "Erreur: $errorMessage\n";
    } else {
        echo "? Impossible de déterminer le statut du numéro.\n";
        echo "Code d'erreur: $errorCode\n";
        echo "Message d'erreur: $errorMessage\n";
        echo "Réponse complète: $errorBody\n";
    }
} catch (\Exception $e) {
    echo "! Erreur lors de la vérification: " . $e->getMessage() . "\n";
}

echo "\nNote: Cette méthode de vérification est indirecte. WhatsApp ne fournit pas d'API officielle pour vérifier si un numéro est enregistré.\n";
echo "Les résultats sont basés sur l'interprétation des codes d'erreur et peuvent ne pas être 100% fiables.\n";