<?php

require_once __DIR__ . '/../vendor/autoload.php';
$entityManager = require_once __DIR__ . '/../src/bootstrap-doctrine.php';

use Doctrine\ORM\Tools\SchemaValidator;

echo "Validation des entités WhatsApp...\n\n";

try {
    // Test SchemaValidator
    $validator = new SchemaValidator($entityManager);
    $errors = $validator->validateMapping();
    
    if (empty($errors)) {
        echo "✓ Toutes les entités sont valides!\n";
    } else {
        echo "✗ Erreurs trouvées:\n";
        foreach ($errors as $className => $errorMessages) {
            echo "\n$className:\n";
            foreach ($errorMessages as $error) {
                echo "  - $error\n";
            }
        }
    }
    
    // Test de création d'instances
    echo "\nTest de création d'instances:\n";
    
    $entitiesToTest = [
        'App\\Entities\\WhatsApp\\WhatsAppQueue',
        'App\\Entities\\WhatsApp\\WhatsAppMessageHistory',
        'App\\Entities\\WhatsApp\\WhatsAppTemplate',
        'App\\Entities\\WhatsApp\\WhatsAppUserTemplate'
    ];
    
    foreach ($entitiesToTest as $entityClass) {
        try {
            $instance = new $entityClass();
            echo "✓ $entityClass créé avec succès\n";
        } catch (\Exception $e) {
            echo "✗ Erreur avec $entityClass: " . $e->getMessage() . "\n";
        }
    }
    
    // Vérifier la synchronisation avec la base de données
    echo "\nVérification de la synchronisation avec la base de données:\n";
    
    $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
    $metadata = [];
    
    foreach ($entitiesToTest as $entityClass) {
        $metadata[] = $entityManager->getClassMetadata($entityClass);
    }
    
    try {
        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($metadata);
        
        if (empty($updateSchemaSql)) {
            echo "✓ Les entités sont synchronisées avec la base de données\n";
        } else {
            echo "✗ Différences détectées:\n";
            foreach ($updateSchemaSql as $sql) {
                echo "  - $sql\n";
            }
        }
    } catch (\Exception $e) {
        echo "✗ Erreur lors de la vérification: " . $e->getMessage() . "\n";
    }
    
} catch (\Exception $e) {
    echo "Erreur fatale: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\nValidation terminée.\n";