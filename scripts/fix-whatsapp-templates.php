<?php
/**
 * Script de vérification et réparation pour le problème de templates WhatsApp
 * 
 * Ce script:
 * 1. Teste la communication avec l'API Meta WhatsApp
 * 2. Vérifie que les templates sont correctement récupérés
 * 3. Propose des corrections pour les problèmes détectés
 */

// Charger les dépendances
require_once __DIR__ . '/../src/bootstrap-doctrine.php';

// Obtenir les services nécessaires
use App\GraphQL\DIContainer;
use App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface;
use App\GraphQL\Types\WhatsApp\WhatsAppTemplateSafeType;
use Psr\Log\LoggerInterface;

// Fonction pour afficher un message
function printMessage($message, $type = 'info') {
    $colors = [
        'info' => "\033[0;36m", // Cyan
        'success' => "\033[0;32m", // Vert
        'warning' => "\033[0;33m", // Jaune
        'error' => "\033[0;31m", // Rouge
    ];
    
    $reset = "\033[0m";
    
    echo $colors[$type] . $message . $reset . PHP_EOL;
}

// Créer une instance du conteneur DI
try {
    $container = new DIContainer();
    
    // Récupérer les services
    $apiClient = $container->get(WhatsAppApiClientInterface::class);
    $templateService = $container->get(WhatsAppTemplateServiceInterface::class);
    $logger = $container->get(LoggerInterface::class);
    
    // En-tête
    printMessage("============================================", 'success');
    printMessage("OUTIL DE DIAGNOSTIC DES TEMPLATES WHATSAPP", 'success');
    printMessage("============================================", 'success');
    printMessage("");
    
    // Test 1: Vérifier la connexion à l'API
    printMessage("Test 1: Connexion à l'API WhatsApp...", 'info');
    
    try {
        // Essayer de récupérer les templates
        $templates = $apiClient->getTemplates();
        
        if (is_array($templates)) {
            printMessage("✅ Connexion à l'API réussie", 'success');
            printMessage("   " . count($templates) . " templates récupérés", 'success');
        } else {
            printMessage("⚠️ Connexion à l'API réussie mais format de retour incorrect", 'warning');
            printMessage("   Format retourné: " . gettype($templates), 'warning');
        }
    } catch (\Throwable $e) {
        printMessage("❌ Échec de connexion à l'API: " . $e->getMessage(), 'error');
    }
    
    printMessage("");
    
    // Test 2: Vérifier le service de templates
    printMessage("Test 2: Service de templates WhatsApp...", 'info');
    
    try {
        $approvedTemplates = $templateService->fetchApprovedTemplatesFromMeta();
        
        if (is_array($approvedTemplates)) {
            printMessage("✅ Service de templates fonctionnel", 'success');
            printMessage("   " . count($approvedTemplates) . " templates approuvés récupérés", 'success');
        } else {
            printMessage("⚠️ Service de templates fonctionnel mais format de retour incorrect", 'warning');
            printMessage("   Format retourné: " . gettype($approvedTemplates), 'warning');
        }
    } catch (\Throwable $e) {
        printMessage("❌ Échec du service de templates: " . $e->getMessage(), 'error');
    }
    
    printMessage("");
    
    // Test 3: Création de types WhatsAppTemplateSafeType
    printMessage("Test 3: Conversion en WhatsAppTemplateSafeType...", 'info');
    
    try {
        if (is_array($approvedTemplates) && !empty($approvedTemplates)) {
            $sampleTemplate = $approvedTemplates[0];
            $safeType = new WhatsAppTemplateSafeType($sampleTemplate);
            
            printMessage("✅ Conversion en WhatsAppTemplateSafeType réussie", 'success');
            printMessage("   Template converti: " . $safeType->getName(), 'success');
        } else {
            printMessage("⚠️ Pas de templates à convertir", 'warning');
            
            // Essayer avec un template vide
            $safeType = new WhatsAppTemplateSafeType([]);
            printMessage("✅ Conversion d'un template vide réussie", 'success');
            printMessage("   Template par défaut: " . $safeType->getName(), 'success');
        }
    } catch (\Throwable $e) {
        printMessage("❌ Échec de conversion en WhatsAppTemplateSafeType: " . $e->getMessage(), 'error');
    }
    
    printMessage("");
    
    // Test 4: Simulations avec null
    printMessage("Test 4: Simulation avec valeurs null...", 'info');
    
    try {
        // Test avec null
        $safeTypeNull = new WhatsAppTemplateSafeType(null);
        printMessage("✅ Conversion d'un template null réussie", 'success');
        printMessage("   Template par défaut: " . $safeTypeNull->getName(), 'success');
    } catch (\Throwable $e) {
        printMessage("❌ Échec de conversion d'un template null: " . $e->getMessage(), 'error');
    }
    
    printMessage("");
    
    // Test 5: Vérification des templates dans la base de données
    printMessage("Test 5: Templates dans la base de données...", 'info');
    
    try {
        $templateRepository = $container->get(\App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface::class);
        $dbTemplates = $templateRepository->findAll();
        
        printMessage("✅ " . count($dbTemplates) . " templates trouvés dans la base de données", 'success');
    } catch (\Throwable $e) {
        printMessage("❌ Erreur d'accès aux templates en base de données: " . $e->getMessage(), 'error');
    }
    
    printMessage("");
    
    // Rapport final
    printMessage("============================================", 'success');
    printMessage("RÉSUMÉ DU DIAGNOSTIC", 'success');
    printMessage("============================================", 'success');
    printMessage("");
    
    if (isset($approvedTemplates) && is_array($approvedTemplates)) {
        printMessage("✓ API WhatsApp: " . count($templates) . " templates au total", 'success');
        printMessage("✓ Templates approuvés: " . count($approvedTemplates) . " templates", 'success');
    } else {
        printMessage("✗ Problème avec l'API ou les templates", 'error');
    }
    
    if (isset($dbTemplates) && is_array($dbTemplates)) {
        printMessage("✓ Base de données: " . count($dbTemplates) . " templates", 'success');
    } else {
        printMessage("✗ Problème avec la base de données", 'error');
    }
    
    // Solutions proposées
    printMessage("");
    printMessage("SOLUTIONS RECOMMANDÉES:", 'info');
    
    if (!isset($approvedTemplates) || !is_array($approvedTemplates) || empty($approvedTemplates)) {
        printMessage("1. Vérifiez les identifiants d'API WhatsApp dans la configuration", 'info');
        printMessage("2. Assurez-vous que l'API Meta est accessible (pas de problème réseau)", 'info');
        printMessage("3. Vérifiez que le token d'accès WhatsApp n'est pas expiré", 'info');
    }
    
    printMessage("4. Utilisez le contrôleur WhatsAppTemplateController qui retourne toujours un tableau", 'info');
    printMessage("5. Vérifiez que WhatsAppTemplateSafeType est utilisé dans les requêtes GraphQL", 'info');
    
    printMessage("");
    printMessage("============================================", 'success');
    
} catch (\Throwable $e) {
    printMessage("❌ Erreur critique: " . $e->getMessage(), 'error');
    printMessage("Trace: " . $e->getTraceAsString(), 'error');
}