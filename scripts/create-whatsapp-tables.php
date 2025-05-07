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
use App\Entities\WhatsApp\WhatsAppMessage;

echo "Création des tables WhatsApp...\n";

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
        $entityManager->getClassMetadata(WhatsAppMessage::class)
    ];
    
    // Mise à jour du schéma
    echo "Mise à jour du schéma pour: " . WhatsAppMessage::class . "\n";
    $schemaTool->updateSchema($classes, true);
    
    echo "Tables WhatsApp créées avec succès!\n";
    
} catch (Exception $e) {
    echo "Erreur lors de la création des tables WhatsApp: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}