<?php
/**
 * Script simplifié pour tester l'envoi de messages texte WhatsApp
 * (dans la fenêtre de 24 heures)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\User;
use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\Services\WhatsApp\WhatsAppApiClient;
use App\Services\PhoneNumberNormalizerService;
use App\Repositories\Doctrine\WhatsApp\WhatsAppMessageHistoryRepository;
use Psr\Log\NullLogger;
use GuzzleHttp\Client;

// Configuration
$phoneNumber = '+2250777104936'; // Votre numéro WhatsApp
$message = "Merci pour votre message ! Ceci est une réponse automatique envoyée dans la fenêtre de 24 heures.";

echo "=== Test simplifié d'envoi de message texte WhatsApp ===\n\n";

// Charger la configuration
$config = require __DIR__ . '/../src/config/whatsapp.php';

// Vérifier la configuration
if (empty($config['phone_number_id'])) {
    echo "Erreur : WHATSAPP_PHONE_NUMBER_ID n'est pas défini dans l'environnement\n";
    echo "Configuration actuelle : " . json_encode($config, JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

// Obtenir l'EntityManager via le bootstrap
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Charger l'utilisateur admin
$userRepository = $entityManager->getRepository(User::class);
$user = $userRepository->find(1); // ID admin = 1

if (!$user) {
    echo "Erreur : utilisateur admin non trouvé\n";
    exit(1);
}

echo "Utilisateur : " . $user->getUsername() . "\n";

// Créer le service de normalisation
$normalizer = new PhoneNumberNormalizerService();

// Créer directement l'API client simplifié
$httpClient = new Client([
    'base_uri' => $config['base_url'] ?? 'https://graph.facebook.com/',
    'timeout' => 30,
    'headers' => [
        'Authorization' => 'Bearer ' . $config['access_token'],
        'Content-Type' => 'application/json'
    ]
]);

// Normaliser le numéro
$normalizedNumber = $normalizer->normalize($phoneNumber);

echo "Envoi du message à : $normalizedNumber\n";
echo "Message : $message\n\n";

// Préparer le payload
$payload = [
    'messaging_product' => 'whatsapp',
    'to' => $normalizedNumber,
    'type' => 'text',
    'text' => [
        'body' => $message
    ]
];

$endpoint = $config['api_version'] . '/' . $config['phone_number_id'] . '/messages';

try {
    // Envoyer le message
    $response = $httpClient->post($endpoint, [
        'json' => $payload
    ]);
    
    $result = json_decode($response->getBody()->getContents(), true);
    
    if (isset($result['messages'][0]['id'])) {
        echo "✓ Message envoyé avec succès !\n";
        echo "ID WhatsApp : " . $result['messages'][0]['id'] . "\n";
        
        // Sauvegarder dans l'historique
        $messageHistory = new WhatsAppMessageHistory();
        $messageHistory->setWabaMessageId($result['messages'][0]['id']);
        $messageHistory->setPhoneNumber($normalizedNumber);
        $messageHistory->setOracleUser($user);
        $messageHistory->setType('text');
        $messageHistory->setDirection('sent');
        $messageHistory->setStatus('sent');
        $messageHistory->setContent($message);
        $messageHistory->setCreatedAt(new \DateTime());
        
        $entityManager->persist($messageHistory);
        $entityManager->flush();
        
        echo "Message sauvegardé dans l'historique\n";
    } else {
        echo "Réponse inattendue : " . json_encode($result) . "\n";
    }
    
} catch (\GuzzleHttp\Exception\ClientException $e) {
    $errorBody = $e->getResponse()->getBody()->getContents();
    $errorData = json_decode($errorBody, true);
    
    echo "✗ Erreur API : " . ($errorData['error']['message'] ?? $e->getMessage()) . "\n";
    
    if (strpos($errorBody, '24-hour') !== false || strpos($errorBody, 'outside the allowed window') !== false) {
        echo "\nDétails de l'erreur :\n";
        echo "Cette erreur survient car :\n";
        echo "1. Aucune conversation n'a été initiée par l'utilisateur\n";
        echo "2. La fenêtre de 24 heures est expirée\n";
        echo "\nSolution :\n";
        echo "1. Envoyez d'abord un message depuis WhatsApp vers le numéro de votre Business\n";
        echo "2. Attendez que le webhook reçoive le message\n";
        echo "3. Relancez ce script pour répondre\n";
    }
} catch (Exception $e) {
    echo "✗ Erreur : " . $e->getMessage() . "\n";
    echo "\n--- Trace complète ---\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== Fin du test ===\n";