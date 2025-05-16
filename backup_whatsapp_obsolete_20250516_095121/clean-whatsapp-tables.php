<?php
/**
 * Script pour nettoyer les anciennes tables WhatsApp
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use Doctrine\ORM\EntityManagerInterface;

echo "=== Nettoyage des tables WhatsApp ===\n\n";

try {
    // Construction du conteneur d'injection de dépendances
    $containerBuilder = new ContainerBuilder();
    $containerBuilder->addDefinitions(__DIR__ . '/../src/config/di.php');
    $container = $containerBuilder->build();

    // Récupération de l'entity manager
    $entityManager = $container->get(EntityManagerInterface::class);
    $connection = $entityManager->getConnection();
    $schemaManager = $connection->createSchemaManager();
    
    // Lister toutes les tables
    $tables = $schemaManager->listTableNames();
    
    echo "Tables WhatsApp existantes :\n";
    $whatsappTables = [];
    foreach ($tables as $table) {
        if (strpos($table, 'whatsapp') !== false) {
            echo "- $table\n";
            $whatsappTables[] = $table;
        }
    }
    
    if (empty($whatsappTables)) {
        echo "\nAucune table WhatsApp trouvée.\n";
        exit(0);
    }
    
    echo "\nVoulez-vous supprimer ces tables ? (y/N) : ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    
    if (trim($line) === 'y' || trim($line) === 'Y') {
        foreach ($whatsappTables as $table) {
            try {
                $connection->executeStatement("DROP TABLE IF EXISTS $table");
                echo "✅ Table $table supprimée\n";
            } catch (\Exception $e) {
                echo "❌ Erreur lors de la suppression de $table : " . $e->getMessage() . "\n";
            }
        }
        echo "\n✅ Nettoyage terminé\n";
    } else {
        echo "\nOpération annulée\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}