<?php

/**
 * Script de synchronisation des templates WhatsApp pour tâche cron
 * 
 * Ce script est conçu pour être exécuté régulièrement via cron
 * Il effectue une synchronisation silencieuse (sans sortie console) des templates
 * Exemple de configuration cron pour exécution toutes les 6 heures:
 * 
 * 0 */6 * * * php /path/to/sync-whatsapp-templates-cron.php >> /var/log/whatsapp-sync.log 2>&1
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/bootstrap-doctrine.php';

// Fonction d'écriture dans le journal avec horodatage
function logMessage(string $message): void {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $message\n";
}

logMessage("Début synchronisation templates WhatsApp");

try {
    // Créer le conteneur DI
    $container = new App\GraphQL\DIContainer();
    
    // Récupérer le service de synchronisation
    $syncService = $container->get(App\Services\Interfaces\WhatsApp\WhatsAppTemplateSyncServiceInterface::class);
    
    // Exécuter la synchronisation complète avec désactivation des templates orphelins
    $stats = $syncService->syncTemplates(false);
    logMessage("Templates synchronisés - Total: {$stats['total']}, Ajoutés: {$stats['added']}, " . 
               "Mis à jour: {$stats['updated']}, Inchangés: {$stats['unchanged']}, Échecs: {$stats['failed']}");
    
    // Synchroniser avec l'administrateur seulement
    $userTemplatesCreated = $syncService->syncTemplatesWithUsers(true);
    logMessage("Relations utilisateur-template créées: $userTemplatesCreated");
    
    // Désactiver les templates orphelins
    $disabledCount = $syncService->disableOrphanedTemplates();
    logMessage("Templates orphelins désactivés: $disabledCount");
    
    logMessage("Synchronisation terminée avec succès");
    
} catch (\Exception $e) {
    logMessage("ERREUR: " . $e->getMessage());
    logMessage("Trace: " . $e->getTraceAsString());
    exit(1);
}