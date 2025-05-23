<?php

// Test direct du client WhatsApp API sans injection de dépendances

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

// Paramètres du test
$templateName = 'greeting';          // Nom du template
$language = 'fr';                    // Code de langue
$recipient = '+2250777104936';       // Numéro du destinataire
$params = ['Claude'];                // Paramètres du template

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
            'headers' => [
                'Authorization' => 'Bearer ' . $config['access_token'],
                'Content-Type' => 'application/json',
            ]
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
        
        $response = $this->httpClient->post($endpoint, [
            'json' => $payload,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config['access_token'],
                'Content-Type' => 'application/json',
            ]
        ]);
        
        return json_decode($response->getBody()->getContents(), true);
    }
}

// Test principal
try {
    log_message("Début du test direct de l'API WhatsApp");
    
    // Créer le client simple
    $client = new SimpleWhatsAppClient($config);
    log_message("Client WhatsApp initialisé avec les paramètres suivants:");
    log_message("- Base URL: " . $config['base_url']);
    log_message("- API Version: " . $config['api_version']);
    log_message("- Phone Number ID: " . $config['phone_number_id']);
    
    // Normaliser le numéro de téléphone
    $normalizedRecipient = preg_replace('/[^0-9]/', '', $recipient);
    if (strpos($normalizedRecipient, '225') !== 0) {
        $normalizedRecipient = '225' . $normalizedRecipient;
    }
    
    // Préparer les paramètres pour le corps du message
    $bodyParameters = [];
    foreach ($params as $param) {
        $bodyParameters[] = [
            'type' => 'text',
            'text' => $param
        ];
    }
    
    // Créer le payload
    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $normalizedRecipient,
        'type' => 'template',
        'template' => [
            'name' => $templateName,
            'language' => [
                'code' => $language
            ],
            'components' => [
                [
                    'type' => 'body',
                    'parameters' => $bodyParameters
                ]
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
        log_message("Message envoyé avec succès! ID: " . $messageId);
        log_message("Réponse complète: " . json_encode($result, JSON_PRETTY_PRINT));
    } else {
        log_message("Envoi effectué mais format de réponse inattendu", true);
        log_message("Réponse reçue: " . json_encode($result, JSON_PRETTY_PRINT));
    }
    
    log_message("Test terminé avec succès!");
    
} catch (\Exception $e) {
    log_message("Erreur lors du test: " . $e->getMessage(), true);
    log_message("Trace: " . $e->getTraceAsString(), true);
}