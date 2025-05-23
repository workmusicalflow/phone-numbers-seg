<?php

// Un script simple pour tester directement l'envoi de templates WhatsApp
// Sans passer par l'API REST

// Charger l'environnement
require_once __DIR__ . '/../src/bootstrap-rest.php';

use App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface;

// Configuration
$templateName = 'greeting';  // Nom du template à utiliser (ajuster si nécessaire)
$language = 'fr';            // Code de langue du template
$recipient = '+2250777104936'; // Numéro du destinataire
$params = ['Claude'];        // Paramètres du template (ajuster selon le template)

// Fonction de log
function log_message($message, $isError = false) {
    echo ($isError ? "\033[31m[ERREUR]\033[0m " : "\033[32m[INFO]\033[0m ") . $message . PHP_EOL;
}

// Test principal
try {
    log_message("Début du test d'envoi de template WhatsApp");
    
    // Récupérer les services nécessaires
    $whatsAppService = $container->get(WhatsAppServiceInterface::class);
    $templateRepo = $container->get(WhatsAppTemplateRepositoryInterface::class);
    
    log_message("Services récupérés avec succès");
    
    // Récupération du premier utilisateur disponible pour le test
    $userRepo = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);
    $user = $userRepo->findOneBy([], ['id' => 'ASC']);
    
    if (!$user) {
        log_message("Aucun utilisateur trouvé dans la base de données", true);
        exit(1);
    }
    
    log_message("Utilisateur trouvé: ID=" . $user->getId());
    
    // Vérifier si le template existe
    $template = $templateRepo->findOneBy(['name' => $templateName]);
    
    if ($template) {
        log_message("Template trouvé: " . $template->getName() . " - " . $template->getLanguage());
    } else {
        log_message("Template non trouvé. Le test utilisera un template par défaut depuis l'API Meta.");
    }
    
    // Préparer les paramètres pour le corps du message
    $bodyParams = [];
    foreach ($params as $param) {
        $bodyParams[] = [
            'type' => 'text',
            'text' => $param
        ];
    }
    
    // Créer les composants du template
    $components = [
        [
            'type' => 'body',
            'parameters' => $bodyParams
        ]
    ];
    
    // Tester l'envoi du message template avec les composants (utilise la nouvelle méthode)
    log_message("Envoi du template WhatsApp: $templateName en $language à $recipient");
    
    $result = $whatsAppService->sendTemplateMessageWithComponents(
        $user,
        $recipient,
        $templateName,
        $language,
        $components
    );
    
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