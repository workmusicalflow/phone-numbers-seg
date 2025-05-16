<?php

require_once __DIR__ . '/../src/bootstrap-doctrine.php';

use Doctrine\ORM\Tools\SchemaTool;

try {
    echo "=== Création des tables WhatsApp supplémentaires ===\n";
    
    // Create schema tool
    $schemaTool = new SchemaTool($entityManager);
    
    // Get all entity metadata
    $metadatas = $entityManager->getMetadataFactory()->getAllMetadata();
    
    // Filter to only WhatsApp entities that need tables
    $whatsappMetadatas = array_filter($metadatas, function($metadata) {
        $className = $metadata->getName();
        return str_contains($className, 'WhatsApp');
    });
    
    foreach ($whatsappMetadatas as $metadata) {
        echo "Entité WhatsApp trouvée: " . $metadata->getName() . "\n";
    }
    
    // Create tables
    $schemaTool->updateSchema($whatsappMetadatas, false);
    
    echo "\nTables WhatsApp créées avec succès.\n";
    
    // Check specific tables
    echo "\n=== Vérification des tables ===\n";
    
    $connection = $entityManager->getConnection();
    
    // Get list of all tables
    $schemaManager = $connection->getSchemaManager();
    $tables = $schemaManager->listTableNames();
    
    $whatsappTables = array_filter($tables, function($table) {
        return str_contains($table, 'whatsapp');
    });
    
    echo "Tables WhatsApp disponibles:\n";
    foreach ($whatsappTables as $table) {
        echo "- $table\n";
    }
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}