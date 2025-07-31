<?php

// Un script simplifié pour tester directement l'envoi de templates WhatsApp
// Sans dépendre du UserRepository

// Charger l'environnement
require_once __DIR__ . '/../src/bootstrap-rest.php';

use App\Services\WhatsApp\WhatsAppApiClient;
use App\Entities\User;

// Configuration
$templateName = 'greeting';          // Nom du template à utiliser (ajuster si nécessaire)
$language = 'fr';                    // Code de langue du template
$recipient = '+2250777104936';       // Numéro du destinataire
$params = ['Claude'];                // Paramètres du template (ajuster selon le template)

// Fonction de log
function log_message($message, $isError = false) {
    echo ($isError ? "\033[31m[ERREUR]\033[0m " : "\033[32m[INFO]\033[0m ") . $message . PHP_EOL;
}

// Test principal
try {
    log_message("Début du test d'envoi de template WhatsApp (version simplifiée)");
    
    // Récupérer le client API WhatsApp directement
    $apiClient = $container->get(WhatsAppApiClient::class);
    log_message("Client API WhatsApp récupéré avec succès");
    
    // Créer un utilisateur factice pour le test
    $mockUser = new User();
    $mockUser->setId(1);
    $mockUser->setUsername("test_user");
    $mockUser->setEmail("test@example.com");
    
    log_message("Utilisateur factice créé pour le test");
    
    // Préparer les paramètres pour le corps du message
    $bodyParameters = [];
    foreach ($params as $param) {
        $bodyParameters[] = [
            'type' => 'text',
            'text' => $param
        ];
    }
    
    // Créer les composants du template
    $components = [
        [
            'type' => 'body',
            'parameters' => $bodyParameters
        ]
    ];
    
    // Normaliser le numéro de téléphone
    $normalizedRecipient = preg_replace('/[^0-9]/', '', $recipient);
    if (strpos($normalizedRecipient, '225') !== 0) {
        $normalizedRecipient = '225' . $normalizedRecipient;
    }
    
    // Construire le payload pour l'API Meta WhatsApp
    $payload = [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => $normalizedRecipient,
        'type' => 'template',
        'template' => [
            'name' => $templateName,
            'language' => [
                'code' => $language
            ],
            'components' => $components
        ]
    ];
    
    log_message("Envoi du template WhatsApp: $templateName en $language à $recipient");
    log_message("Payload: " . json_encode($payload, JSON_PRETTY_PRINT));
    
    // Envoyer directement via l'API client
    $result = $apiClient->sendMessage($payload);
    
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