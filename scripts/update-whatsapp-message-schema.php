<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap-doctrine.php';

use Doctrine\ORM\EntityManager;

try {
    $entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';
    
    echo "Mise à jour du schéma WhatsApp Message History...\n";
    
    // Pour SQLite, nous devons recréer la table pour modifier les contraintes
    // Mais d'abord, mettons à jour les données
    $sql = "UPDATE whatsapp_message_history 
            SET waba_message_id = NULL 
            WHERE waba_message_id = ''";
    
    $entityManager->getConnection()->executeStatement($sql);
    
    echo "✓ Messages avec wabaMessageId vide mis à jour\n";
    
    // Utilisons la méthode update schema de Doctrine
    $metadata = $entityManager->getClassMetadata(\App\Entities\WhatsApp\WhatsAppMessageHistory::class);
    $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
    $schemaTool->updateSchema([$metadata], true);
    
    echo "✓ Schéma mis à jour avec Doctrine\n";
    
} catch (\Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Migration terminée avec succès!\n";