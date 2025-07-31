<?php

declare(strict_types=1);

// Charger l'EntityManager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine-simple.php';

echo "=== Mise à jour du schéma de la table WhatsAppTemplate ===\n\n";

try {
    // Connexion à la base de données
    $conn = $entityManager->getConnection();
    
    // 1. Vérifier si la colonne is_global existe
    $tableColumns = $conn->createSchemaManager()->listTableColumns('whatsapp_templates');
    $hasIsGlobal = false;
    
    foreach ($tableColumns as $column) {
        if ($column->getName() === 'is_global') {
            $hasIsGlobal = true;
            break;
        }
    }
    
    // 2. Ajouter la colonne is_global si elle n'existe pas
    if (!$hasIsGlobal) {
        echo "Ajout de la colonne 'is_global' à la table 'whatsapp_templates'...\n";
        $conn->executeStatement('ALTER TABLE whatsapp_templates ADD COLUMN is_global BOOLEAN DEFAULT 0 NOT NULL');
        echo "Colonne 'is_global' ajoutée avec succès.\n";
    } else {
        echo "La colonne 'is_global' existe déjà.\n";
    }
    
    echo "\n=== Mise à jour du schéma terminée avec succès ===\n";
    
} catch (\Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}