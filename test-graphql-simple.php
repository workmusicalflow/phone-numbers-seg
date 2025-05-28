<?php

declare(strict_types=1);

echo "🧪 Test GraphQL simple...\n\n";

try {
    // Test d'accès au fichier graphql.php
    echo "1️⃣ Test d'inclusion de graphql.php...\n";
    
    // Simuler l'environnement
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['CONTENT_TYPE'] = 'application/json';
    
    // Capturer la sortie
    ob_start();
    
    try {
        require_once __DIR__ . '/public/graphql.php';
        $output = ob_get_contents();
        ob_end_clean();
        
        echo "✅ graphql.php chargé sans erreur fatale\n";
        if (strlen($output) > 0) {
            echo "📤 Sortie: " . substr($output, 0, 200) . (strlen($output) > 200 ? "..." : "") . "\n";
        }
        
    } catch (\Throwable $e) {
        ob_end_clean();
        echo "❌ Erreur lors du chargement de graphql.php:\n";
        echo "Type: " . get_class($e) . "\n";
        echo "Message: " . $e->getMessage() . "\n";
        echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
        
        // Afficher la trace pour identifier le problème
        echo "\nStack trace:\n";
        echo $e->getTraceAsString() . "\n";
    }
    
} catch (\Throwable $e) {
    echo "❌ Erreur globale: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
}