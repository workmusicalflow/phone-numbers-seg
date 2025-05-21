<?php

/**
 * Script de synchronisation des templates WhatsApp
 * 
 * Ce script permet de synchroniser les templates WhatsApp entre l'API Meta Cloud
 * et la base de données locale Oracle. Il peut être exécuté manuellement ou
 * via une tâche cron programmée.
 * 
 * Options:
 * --force   Force la mise à jour des templates même s'ils existent déjà
 * --all     Synchronise avec tous les utilisateurs (pas seulement l'admin)
 * --disable Désactive les templates orphelins (qui n'existent plus dans l'API Meta)
 * --report  Génère un rapport détaillé sur l'état des templates
 * 
 * Exemple d'utilisation:
 * php sync-whatsapp-templates.php --force --disable --report
 */

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../src/bootstrap-doctrine.php';

// Analyser les arguments en ligne de commande
$options = getopt('', ['force', 'all', 'disable', 'report']);

$forceUpdate = isset($options['force']);
$allUsers = isset($options['all']);
$disableOrphaned = isset($options['disable']);
$generateReport = isset($options['report']);

// Fonction pour afficher le texte avec couleur dans la console
function colorText(string $text, string $color = 'default'): string {
    $colors = [
        'default' => "\033[0m",
        'black' => "\033[30m",
        'red' => "\033[31m",
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'magenta' => "\033[35m",
        'cyan' => "\033[36m",
        'white' => "\033[37m",
    ];

    return $colors[$color] . $text . $colors['default'];
}

// Afficher l'en-tête du script
echo colorText("===========================================\n", 'cyan');
echo colorText("    Synchronisation Templates WhatsApp    \n", 'cyan');
echo colorText("===========================================\n\n", 'cyan');

// Afficher les options sélectionnées
echo "Options activées:\n";
echo "- " . ($forceUpdate ? colorText("✓", 'green') : colorText("✗", 'red')) . " Force mise à jour\n";
echo "- " . ($allUsers ? colorText("✓", 'green') : colorText("✗", 'red')) . " Tous les utilisateurs\n";
echo "- " . ($disableOrphaned ? colorText("✓", 'green') : colorText("✗", 'red')) . " Désactiver templates orphelins\n";
echo "- " . ($generateReport ? colorText("✓", 'green') : colorText("✗", 'red')) . " Générer rapport\n\n";

// Créer le conteneur DI
$container = new App\GraphQL\DIContainer();

try {
    // Récupérer le service de synchronisation
    $syncService = $container->get(App\Services\Interfaces\WhatsApp\WhatsAppTemplateSyncServiceInterface::class);
    
    echo colorText("1. Synchronisation des templates depuis l'API Meta\n", 'blue');
    echo "-------------------------------------------------\n";
    
    // Exécuter la synchronisation
    $stats = $syncService->syncTemplates($forceUpdate);
    
    // Afficher les statistiques
    echo colorText("✓ ", 'green') . "Synchronisation terminée\n";
    echo "  - Templates traités: " . colorText((string)$stats['total'], 'yellow') . "\n";
    echo "  - Ajoutés: " . colorText((string)$stats['added'], 'green') . "\n";
    echo "  - Mis à jour: " . colorText((string)$stats['updated'], 'blue') . "\n";
    echo "  - Inchangés: " . colorText((string)$stats['unchanged'], 'cyan') . "\n";
    echo "  - Échecs: " . colorText((string)$stats['failed'], 'red') . "\n\n";
    
    echo colorText("2. Synchronisation des templates avec les utilisateurs\n", 'blue');
    echo "-------------------------------------------------\n";
    
    // Synchroniser avec les utilisateurs
    $userTemplatesCreated = $syncService->syncTemplatesWithUsers(!$allUsers);
    
    echo colorText("✓ ", 'green') . "Synchronisation avec les utilisateurs terminée\n";
    echo "  - Relations utilisateur-template créées: " . colorText((string)$userTemplatesCreated, 'green') . "\n\n";
    
    // Désactiver les templates orphelins si l'option est activée
    if ($disableOrphaned) {
        echo colorText("3. Désactivation des templates orphelins\n", 'blue');
        echo "-------------------------------------------------\n";
        
        $disabledCount = $syncService->disableOrphanedTemplates();
        
        echo colorText("✓ ", 'green') . "Désactivation terminée\n";
        echo "  - Templates désactivés: " . colorText((string)$disabledCount, 'yellow') . "\n\n";
    }
    
    // Générer et afficher le rapport si l'option est activée
    if ($generateReport) {
        echo colorText("4. Rapport sur l'état des templates\n", 'blue');
        echo "-------------------------------------------------\n";
        
        $report = $syncService->generateTemplateReport();
        
        echo "État des templates en base de données:\n";
        echo "  - Total: " . colorText((string)$report['database']['total'], 'yellow') . "\n";
        echo "  - Actifs: " . colorText((string)$report['database']['active'], 'green') . "\n";
        echo "  - Inactifs: " . colorText((string)$report['database']['inactive'], 'red') . "\n";
        
        echo "\nRépartition par statut:\n";
        foreach ($report['database']['by_status'] as $status => $count) {
            $color = match($status) {
                'APPROVED' => 'green',
                'PENDING' => 'yellow',
                'REJECTED' => 'red',
                default => 'default'
            };
            echo "  - " . colorText($status, $color) . ": " . colorText((string)$count, $color) . "\n";
        }
        
        echo "\nRépartition par catégorie:\n";
        foreach ($report['database']['by_category'] as $category => $count) {
            echo "  - " . $category . ": " . colorText((string)$count, 'cyan') . "\n";
        }
        
        echo "\nRépartition par langue:\n";
        foreach ($report['database']['by_language'] as $language => $count) {
            echo "  - " . $language . ": " . colorText((string)$count, 'cyan') . "\n";
        }
        
        echo "\nStatut de l'API Meta:\n";
        echo "  - Templates disponibles: " . colorText((string)$report['meta_api']['total'], 'yellow') . "\n";
        echo "  - Statut de synchronisation: " . colorText($report['meta_api']['sync_status'], $report['meta_api']['sync_status'] === 'OK' ? 'green' : 'red') . "\n";
        echo "  - Date du rapport: " . $report['generated_at'] . "\n\n";
    }
    
    echo colorText("Opération terminée avec succès!\n", 'green');
    
} catch (\Exception $e) {
    echo colorText("\nERREUR: " . $e->getMessage() . "\n", 'red');
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}