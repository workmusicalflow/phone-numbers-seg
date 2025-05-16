<?php
/**
 * Script d'urgence pour restaurer toutes les tables de l'application
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

echo "=== RESTAURATION D'URGENCE DES TABLES ===\n\n";

try {
    // Construction du conteneur d'injection de dépendances
    $containerBuilder = new ContainerBuilder();
    $containerBuilder->addDefinitions(__DIR__ . '/../src/config/di.php');
    $container = $containerBuilder->build();

    // Récupération de l'entity manager
    $entityManager = $container->get(EntityManagerInterface::class);
    
    // Création de l'outil de schéma
    $schemaTool = new SchemaTool($entityManager);
    
    // Obtenir toutes les métadonnées des entités
    $allMetadata = $entityManager->getMetadataFactory()->getAllMetadata();
    
    echo "Entités trouvées : " . count($allMetadata) . "\n\n";
    
    foreach ($allMetadata as $metadata) {
        echo "- " . $metadata->getName() . "\n";
    }
    
    echo "\nCréation du schéma complet...\n";
    
    // Créer le schéma pour toutes les entités
    $schemaTool->updateSchema($allMetadata, true);
    
    echo "\n✅ Tables restaurées avec succès !\n\n";
    
    // Vérifier les tables créées
    $connection = $entityManager->getConnection();
    $schemaManager = $connection->createSchemaManager();
    $tables = $schemaManager->listTableNames();
    
    echo "Tables dans la base de données :\n";
    foreach ($tables as $table) {
        echo "✅ $table\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    echo "Trace : " . $e->getTraceAsString() . "\n";
    exit(1);
}