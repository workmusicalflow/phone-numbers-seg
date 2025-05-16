<?php

require_once __DIR__ . '/../src/bootstrap-doctrine.php';

use Doctrine\ORM\Tools\SchemaTool;

echo "=== Mise à jour du schéma Doctrine pour WhatsApp ===\n\n";

try {
    $schemaTool = new SchemaTool($entityManager);
    
    // Obtenir toutes les métadonnées d'entités
    $allMetadata = $entityManager->getMetadataFactory()->getAllMetadata();
    
    // Filtrer uniquement les entités WhatsApp
    $whatsappMetadata = array_filter($allMetadata, function($metadata) {
        return strpos($metadata->getName(), 'WhatsApp') !== false;
    });
    
    if (empty($whatsappMetadata)) {
        echo "Aucune entité WhatsApp trouvée.\n";
        exit(1);
    }
    
    echo "Entités WhatsApp trouvées:\n";
    foreach ($whatsappMetadata as $metadata) {
        echo "- " . $metadata->getName() . "\n";
    }
    
    // Générer le SQL DDL
    echo "\n=== SQL à exécuter ===\n";
    $sql = $schemaTool->getUpdateSchemaSql($whatsappMetadata, false);
    
    foreach ($sql as $query) {
        echo $query . ";\n";
    }
    
    if (empty($sql)) {
        echo "Le schéma est déjà à jour.\n";
        exit(0);
    }
    
    // Demander confirmation
    echo "\nVoulez-vous exécuter ces requêtes? (y/n) ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    
    if (trim($line) === 'y') {
        echo "\nExécution des requêtes...\n";
        $schemaTool->updateSchema($whatsappMetadata, false);
        echo "✓ Schéma mis à jour avec succès.\n";
    } else {
        echo "Mise à jour annulée.\n";
    }
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}