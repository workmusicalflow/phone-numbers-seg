<?php

/**
 * Script pour mettre à jour le schéma Doctrine pour les templates WhatsApp
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap-doctrine.php';

use Doctrine\ORM\Tools\SchemaTool;

// Créer le conteneur DI
$container = new App\GraphQL\DIContainer();
$entityManager = $container->get(Doctrine\ORM\EntityManagerInterface::class);

echo "Mise à jour du schéma Doctrine pour WhatsApp Templates...\n";

try {
    $schemaTool = new SchemaTool($entityManager);
    
    // Obtenir toutes les métadonnées
    $metadatas = $entityManager->getMetadataFactory()->getAllMetadata();
    
    // Filtrer pour ne garder que WhatsAppTemplate
    $templateMetadata = [];
    foreach ($metadatas as $metadata) {
        if ($metadata->getName() === 'App\\Entities\\WhatsApp\\WhatsAppTemplate') {
            $templateMetadata[] = $metadata;
            break;
        }
    }
    
    if (empty($templateMetadata)) {
        throw new \Exception("Métadonnées non trouvées pour WhatsAppTemplate");
    }
    
    // Obtenir le SQL pour mettre à jour le schéma
    $sql = $schemaTool->getUpdateSchemaSql($templateMetadata, true);
    
    if (empty($sql)) {
        echo "Le schéma est déjà à jour.\n";
    } else {
        echo "SQL à exécuter:\n";
        foreach ($sql as $query) {
            echo $query . ";\n";
        }
        
        echo "\nExécution des requêtes...\n";
        $schemaTool->updateSchema($templateMetadata, true);
        echo "Schéma mis à jour avec succès.\n";
    }
    
} catch (\Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    echo "Trace : " . $e->getTraceAsString() . "\n";
}

// Afficher la structure de la table après mise à jour
echo "\nStructure actuelle de la table whatsapp_templates:\n";
$connection = $entityManager->getConnection();
$schemaManager = $connection->createSchemaManager();

try {
    $table = $schemaManager->introspectTable('whatsapp_templates');
    foreach ($table->getColumns() as $column) {
        echo "- " . $column->getName() . " (" . $column->getType()->getName() . ")\n";
    }
} catch (\Exception $e) {
    echo "Impossible de lire la structure de la table: " . $e->getMessage() . "\n";
}