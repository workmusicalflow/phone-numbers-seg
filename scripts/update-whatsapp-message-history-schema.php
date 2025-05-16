<?php
/**
 * Script pour mettre à jour le schéma de la table whatsapp_message_history
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Obtenir l'EntityManager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

echo "=== Mise à jour du schéma WhatsApp Message History ===\n\n";

try {
    // Obtenir la connexion
    $connection = $entityManager->getConnection();
    
    // Vérifier si les colonnes existent déjà
    $schemaManager = $connection->createSchemaManager();
    $columns = $schemaManager->listTableColumns('whatsapp_message_history');
    
    $existingColumns = array_map(function($col) { return strtolower($col->getName()); }, $columns);
    
    // Ajouter la colonne metadata si elle n'existe pas
    if (!in_array('metadata', $existingColumns)) {
        echo "Ajout de la colonne 'metadata'...\n";
        $sql = "ALTER TABLE whatsapp_message_history ADD COLUMN metadata JSON DEFAULT NULL";
        $connection->executeStatement($sql);
        echo "✓ Colonne 'metadata' ajoutée\n";
    } else {
        echo "La colonne 'metadata' existe déjà\n";
    }
    
    // Ajouter la colonne errors si elle n'existe pas
    if (!in_array('errors', $existingColumns)) {
        echo "Ajout de la colonne 'errors'...\n";
        $sql = "ALTER TABLE whatsapp_message_history ADD COLUMN errors JSON DEFAULT NULL";
        $connection->executeStatement($sql);
        echo "✓ Colonne 'errors' ajoutée\n";
    } else {
        echo "La colonne 'errors' existe déjà\n";
    }
    
    echo "\n✅ Mise à jour du schéma terminée avec succès !\n";
    
} catch (\Exception $e) {
    echo "\n❌ Erreur : " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Fin du script ===\n";