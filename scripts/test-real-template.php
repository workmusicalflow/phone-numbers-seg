<?php

// Test avec un template qui existe réellement

// Charger l'autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Configuration de base pour le client WhatsApp
$config = [
    'base_url' => 'https://graph.facebook.com',
    'api_version' => 'v22.0',
    'app_id' => '1193922949108494',
    'phone_number_id' => '660953787095211',
    'waba_id' => '664409593123173',
    'access_token' => 'EAAQ93dlFUw4BOZCu6OPmzQuo47pE8eYgGCJLWaQzeyHo03ZCmUWNOQZABt0NeJgVfx9zgurvJc3YynNmFZBgfsCslzydmfzdWZA3onZCyGQsgSo1ZAC6o7ZCgzukF10wmeCjfWcWItPeOw0hanzT0V5ShOIQZCEzVF9qP2aGALaD5ZCTvy95DhjlUwOwijVNAEXpGzEG0YKIsRI8ZCngj9BiXLltt3azinQQYgPBIs9bZA6K'
];

// Test avec un template qui existe
$templateName = 'hello_world';       // Template qui existe d'après la liste précédente
$language = 'en_US';                 // Langue du template
$recipient = '+2250777104936';       // Numéro du destinataire

// Fonction de log
function log_message($message, $isError = false) {
    echo ($isError ? "\033[31m[ERREUR]\033[0m " : "\033[32m[INFO]\033[0m ") . $message . PHP_EOL;
}

/**
 * Classe de client API WhatsApp simplifiée
 */
class SimpleWhatsAppClient {
    private array $config;
    private \GuzzleHttp\Client $httpClient;
    
    public function __construct(array $config) {
        $this->config = $config;
        $this->httpClient = new \GuzzleHttp\Client([
            'timeout' => 30,
            'connect_timeout' => 30,
        ]);
    }
    
    public function sendMessage(array $payload): array {
        $endpoint = sprintf(
            '%s/%s/%s/messages',
            rtrim($this->config['base_url'], '/'),
            $this->config['api_version'],
            $this->config['phone_number_id']
        );
        
        log_message("URL de l'API: " . $endpoint);
        
        try {
            $response = $this->httpClient->post($endpoint, [
                'json' => $payload,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config['access_token'],
                    'Content-Type' => 'application/json',
                ]
            ]);
            
            return json_decode($response->getBody()->getContents(), true);
            
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            if ($response) {
                $body = $response->getBody()->getContents();
                log_message("Erreur API détaillée: " . $body, true);
                return json_decode($body, true);
            }
            throw $e;
        }
    }
}

// Test principal
try {
    log_message("Test avec un template WhatsApp réel");
    
    // Créer le client simple
    $client = new SimpleWhatsAppClient($config);
    log_message("Client WhatsApp initialisé");
    
    // Normaliser le numéro de téléphone
    $normalizedRecipient = preg_replace('/[^0-9]/', '', $recipient);
    if (strpos($normalizedRecipient, '225') !== 0) {
        $normalizedRecipient = '225' . $normalizedRecipient;
    }
    
    // Créer le payload (template simple sans paramètres)
    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $normalizedRecipient,
        'type' => 'template',
        'template' => [
            'name' => $templateName,
            'language' => [
                'code' => $language
            ]
        ]
    ];
    
    log_message("Envoi du template WhatsApp: $templateName en $language à $recipient");
    log_message("Payload: " . json_encode($payload, JSON_PRETTY_PRINT));
    
    // Envoyer le message
    $result = $client->sendMessage($payload);
    
    // Vérifier la réponse
    if (isset($result['messages']) && !empty($result['messages'])) {
        $messageId = $result['messages'][0]['id'] ?? 'unknown';
        log_message("✅ Message envoyé avec succès! ID: " . $messageId);
        log_message("Réponse complète: " . json_encode($result, JSON_PRETTY_PRINT));
    } elseif (isset($result['error'])) {
        log_message("❌ Erreur de l'API: " . $result['error']['message'], true);
        log_message("Code d'erreur: " . $result['error']['code'], true);
    } else {
        log_message("❌ Format de réponse inattendu", true);
        log_message("Réponse reçue: " . json_encode($result, JSON_PRETTY_PRINT));
    }
    
    log_message("Test terminé");
    
} catch (\Exception $e) {
    log_message("Erreur lors du test: " . $e->getMessage(), true);
}