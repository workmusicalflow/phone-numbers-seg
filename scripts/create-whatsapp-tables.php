<?php
/**
 * Script pour créer les tables WhatsApp dans la base de données
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Afficher toutes les erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use DI\ContainerBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\Entities\WhatsApp\WhatsAppTemplate;
use App\Entities\WhatsApp\WhatsAppQueue;
use App\Entities\WhatsApp\WhatsAppUserTemplate;

echo "=== Création des tables WhatsApp ===\n\n";

try {
    // Construction du conteneur d'injection de dépendances
    $containerBuilder = new ContainerBuilder();
    $containerBuilder->addDefinitions(__DIR__ . '/../src/config/di.php');
    $container = $containerBuilder->build();

    // Récupération de l'entity manager
    $entityManager = $container->get(EntityManagerInterface::class);
    
    // Création de l'outil de schéma
    $schemaTool = new SchemaTool($entityManager);
    
    // Liste des entités WhatsApp
    $classes = [
        $entityManager->getClassMetadata(WhatsAppMessageHistory::class),
        $entityManager->getClassMetadata(WhatsAppTemplate::class),
        $entityManager->getClassMetadata(WhatsAppQueue::class),
        $entityManager->getClassMetadata(WhatsAppUserTemplate::class)
    ];
    
    // Afficher le SQL qui sera exécuté
    $sql = $schemaTool->getCreateSchemaSql($classes);
    echo "SQL à exécuter :\n";
    foreach ($sql as $query) {
        echo $query . ";\n";
    }
    echo "\n";
    
    // Mise à jour du schéma
    echo "Création/mise à jour des tables...\n";
    $schemaTool->updateSchema($classes, true);
    
    echo "\n✅ Tables WhatsApp créées/mises à jour avec succès :\n";
    echo "- whatsapp_message_history\n";
    echo "- whatsapp_templates\n";
    echo "- whatsapp_queue\n";
    echo "- whatsapp_user_templates\n\n";
    
    // Vérifier l'existence des tables
    $connection = $entityManager->getConnection();
    $schemaManager = $connection->createSchemaManager();
    $tables = $schemaManager->listTableNames();
    
    echo "Tables WhatsApp dans la base de données :\n";
    foreach ($tables as $table) {
        if (strpos($table, 'whatsapp') !== false) {
            echo "✅ $table\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la création des tables WhatsApp : " . $e->getMessage() . "\n";
    echo "Trace : " . $e->getTraceAsString() . "\n";
    exit(1);
}