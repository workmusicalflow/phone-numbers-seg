<?php

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "Updating Doctrine schema...\n";

// Get the EntityManager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Get the schema tool
$tool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);

// Get the metadata for the SMSHistory entity
$classes = [
    $entityManager->getClassMetadata(\App\Entities\SMSHistory::class)
];

try {
    // Update the schema for the SMSHistory entity
    echo "Updating schema for SMSHistory entity...\n";
    $tool->updateSchema($classes, true);
    echo "Schema updated successfully!\n";
} catch (Exception $e) {
    echo "Error updating schema: " . $e->getMessage() . "\n";

    // Try to drop and recreate the schema
    try {
        echo "Trying to drop and recreate schema...\n";
        $tool->dropSchema($classes);
        $tool->createSchema($classes);
        echo "Schema recreated successfully!\n";
    } catch (Exception $e2) {
        echo "Error recreating schema: " . $e2->getMessage() . "\n";
    }
}
