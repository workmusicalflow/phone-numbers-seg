<?php

/**
 * Script pour ajouter les colonnes manquantes à la table whatsapp_templates
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap-doctrine.php';

// Créer le conteneur DI
$container = new App\GraphQL\DIContainer();
$entityManager = $container->get(Doctrine\ORM\EntityManagerInterface::class);
$connection = $entityManager->getConnection();

echo "Mise à jour de la structure de la table whatsapp_templates...\n";

// Récupérer la structure actuelle
$schemaManager = $connection->createSchemaManager();
$columns = $schemaManager->listTableColumns('whatsapp_templates');
$existingColumns = array_keys($columns);

echo "Colonnes existantes : " . implode(', ', $existingColumns) . "\n\n";

// Définir les colonnes à ajouter
$columnsToAdd = [
    'body_text' => "ALTER TABLE whatsapp_templates ADD COLUMN body_text TEXT NOT NULL DEFAULT ''",
    'header_format' => "ALTER TABLE whatsapp_templates ADD COLUMN header_format VARCHAR(20) NOT NULL DEFAULT 'NONE'",
    'header_text' => "ALTER TABLE whatsapp_templates ADD COLUMN header_text TEXT",
    'footer_text' => "ALTER TABLE whatsapp_templates ADD COLUMN footer_text TEXT",
    'meta_template_id' => "ALTER TABLE whatsapp_templates ADD COLUMN meta_template_id VARCHAR(255)"
];

// Ajouter les colonnes manquantes
$success = true;
foreach ($columnsToAdd as $columnName => $sql) {
    if (!in_array($columnName, $existingColumns)) {
        try {
            echo "Ajout de la colonne '$columnName'...";
            $connection->executeStatement($sql);
            echo " OK\n";
        } catch (\Exception $e) {
            echo " ERREUR : " . $e->getMessage() . "\n";
            $success = false;
        }
    } else {
        echo "La colonne '$columnName' existe déjà.\n";
    }
}

// Vérifier la structure finale
echo "\nStructure finale de la table :\n";
$columns = $schemaManager->listTableColumns('whatsapp_templates');
foreach ($columns as $column) {
    echo "- " . $column->getName() . " (" . $column->getType()->getName() . ")\n";
}

echo "\n" . ($success ? "Mise à jour terminée avec succès." : "Mise à jour terminée avec des erreurs.") . "\n";