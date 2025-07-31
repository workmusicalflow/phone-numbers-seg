<?php

/**
 * Script de test pour vérifier la communication avec l'API WhatsApp
 * 
 * Ce script teste directement les fonctionnalités de l'API WhatsApp sans passer par l'interface web
 */

// Charger l'autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Configurer le chemin du fichier de log
$logFile = __DIR__ . '/../logs/whatsapp-api-test.log';

// Fonction de journalisation
function log_message($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
    echo $message . PHP_EOL;
}

// Nettoyer le fichier de log
file_put_contents($logFile, "--- Test de l'API WhatsApp démarré le " . date('Y-m-d H:i:s') . " ---\n");

log_message("Initialisation du test de l'API WhatsApp...");

try {
    // Créer le conteneur
    $container = new \App\GraphQL\DIContainer();
    log_message("Conteneur créé avec succès");
    
    // Récupérer le service WhatsApp et le logger
    $whatsAppService = $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface::class);
    $logger = $container->get(\Psr\Log\LoggerInterface::class);
    log_message("Services récupérés avec succès");
    
    // Récupérer un utilisateur pour le test
    $userRepository = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);
    $user = $userRepository->findOneByUsername('admin');
    
    if (!$user) {
        throw new \Exception("Utilisateur 'admin' non trouvé");
    }
    
    log_message("Utilisateur trouvé: " . $user->getUsername() . " (ID: " . $user->getId() . ")");
    
    // Test 1: Récupérer les templates approuvés
    log_message("\n--- Test 1: Récupération des templates approuvés ---");
    
    $filters = [
        'status' => 'APPROVED',
        'useCache' => true,
        'forceRefresh' => false
    ];
    
    log_message("Appel du service avec les filtres: " . json_encode($filters));
    
    try {
        $templates = $whatsAppService->getApprovedTemplates($user, $filters);
        
        log_message("Succès! " . count($templates) . " templates récupérés");
        log_message("Premier template: " . json_encode(reset($templates)));
    } catch (\Exception $e) {
        log_message("ERREUR lors de la récupération des templates: " . $e->getMessage());
        
        // Test de fallback: Essayer de récupérer les templates directement depuis le repository
        log_message("\n--- Test de fallback: Récupération depuis le repository ---");
        try {
            $templateRepository = $container->get(\App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface::class);
            $cachedTemplates = $templateRepository->findApprovedTemplates($filters);
            log_message("Succès du fallback! " . count($cachedTemplates) . " templates récupérés du cache");
        } catch (\Exception $e2) {
            log_message("ERREUR du fallback: " . $e2->getMessage());
        }
    }
    
    // Test 2: Vérification de la configuration de l'API WhatsApp
    log_message("\n--- Test 2: Vérification de la configuration de l'API WhatsApp ---");
    
    // Récupérer le client API directement
    $apiClient = $container->get(\App\Services\WhatsApp\WhatsAppApiClient::class);
    
    // Récupérer le numéro de téléphone WhatsApp
    $phoneNumberID = $apiClient->getPhoneNumberId();
    log_message("Phone Number ID: " . $phoneNumberID);
    
    // Récupérer l'ID d'entreprise
    $businessAccountID = $apiClient->getBusinessAccountId();
    log_message("Business Account ID: " . $businessAccountID);
    
    log_message("\nTest terminé.");
    
} catch (\Exception $e) {
    log_message("ERREUR FATALE: " . $e->getMessage());
    log_message("Trace: " . $e->getTraceAsString());
}