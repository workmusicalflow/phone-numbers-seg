<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap-doctrine.php';

use Doctrine\ORM\Tools\SchemaTool;

try {
    $entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';
    
    echo "Création de la table WhatsApp Message History...\n";
    
    $metadata = [
        $entityManager->getClassMetadata(\App\Entities\WhatsApp\WhatsAppMessageHistory::class)
    ];
    
    $schemaTool = new SchemaTool($entityManager);
    
    // Créer le schéma
    $schemaTool->createSchema($metadata);
    
    echo "✓ Table whatsapp_message_history créée avec succès\n";
    
} catch (\Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Création terminée avec succès!\n";