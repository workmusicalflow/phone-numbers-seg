<?php
/**
 * Script pour exécuter la migration de la table whatsapp_messages
 * 
 * Ce script exécute le fichier SQL create_whatsapp_messages_table.sql
 * et met à jour le schéma Doctrine pour l'entité WhatsAppMessage
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap-doctrine.php';

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

// Récupération de l'EntityManager
/** @var EntityManagerInterface $entityManager */
$entityManager = $container->get(EntityManagerInterface::class);

// Exécution du fichier SQL de migration
echo "Exécution du fichier SQL de migration pour WhatsApp Messages...\n";

$sqlFile = __DIR__ . '/../src/database/migrations/create_whatsapp_messages_table.sql';
$sqlContent = file_get_contents($sqlFile);

if ($sqlContent === false) {
    die("Erreur: Impossible de lire le fichier de migration SQL.\n");
}

// Diviser le contenu en instructions SQL distinctes
$statements = explode(';', $sqlContent);

// Exécuter chaque instruction
$connection = $entityManager->getConnection();
$succeeded = 0;
$failed = 0;

foreach ($statements as $statement) {
    $statement = trim($statement);
    if (empty($statement)) {
        continue;
    }

    try {
        $connection->executeStatement($statement);
        $succeeded++;
        echo ".";
    } catch (\Exception $e) {
        $failed++;
        echo "F";
        // Optionnel: Afficher l'erreur
        // echo "\nErreur: " . $e->getMessage() . "\n";
    }
}

echo "\n$succeeded requêtes exécutées avec succès, $failed échecs.\n";

// Mise à jour du schéma Doctrine pour l'entité WhatsAppMessage
echo "Mise à jour du schéma Doctrine pour WhatsAppMessage...\n";

try {
    $schemaTool = new SchemaTool($entityManager);
    $metadataFactory = $entityManager->getMetadataFactory();
    $metadata = $metadataFactory->getMetadataFor('App\\Entities\\WhatsApp\\WhatsAppMessage');
    
    // Uniquement mettre à jour cette entité
    $schemaTool->updateSchema([$metadata]);
    
    echo "Schéma Doctrine mis à jour avec succès.\n";
} catch (\Exception $e) {
    echo "Erreur lors de la mise à jour du schéma Doctrine: " . $e->getMessage() . "\n";
}

echo "Migration terminée.\n";