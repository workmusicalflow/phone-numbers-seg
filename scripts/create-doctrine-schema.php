<?php

/**
 * Script to create all Doctrine entity tables in the var/database.sqlite file
 * 
 * This script uses Doctrine's schema tool to create all the tables for the entities
 * defined in the src/Entities directory.
 * 
 * Usage: php scripts/create-doctrine-schema.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Get the EntityManager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Get the schema tool
$tool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);

// Get all entity metadata
$classes = $entityManager->getMetadataFactory()->getAllMetadata();

try {
    // Create the schema for all entities
    echo "Creating schema for all entities...\n";
    $tool->createSchema($classes);
    echo "Schema created successfully!\n";
} catch (\Exception $e) {
    echo "Error creating schema: " . $e->getMessage() . "\n";

    // Try to update the schema instead
    try {
        echo "Trying to update schema instead...\n";
        $tool->updateSchema($classes, true);
        echo "Schema updated successfully!\n";
    } catch (\Exception $e2) {
        echo "Error updating schema: " . $e2->getMessage() . "\n";
        exit(1);
    }
}

// Apply additional schema updates from update-schema.sql
$sqlFilePath = __DIR__ . '/update-schema.sql';

// Check if the SQL file exists
if (!file_exists($sqlFilePath)) {
    echo "Warning: SQL file not found at $sqlFilePath\n";
    exit(0);
}

// Read the SQL file
$sql = file_get_contents($sqlFilePath);
if ($sql === false) {
    echo "Error: Failed to read SQL file\n";
    exit(1);
}

// Split the SQL into individual statements
$statements = array_filter(
    array_map(
        'trim',
        explode(';', $sql)
    ),
    function ($statement) {
        return !empty($statement) && strpos($statement, '--') !== 0;
    }
);

try {
    // Get the database connection
    $conn = $entityManager->getConnection();

    // Begin a transaction
    $conn->beginTransaction();

    // Execute each statement
    foreach ($statements as $statement) {
        echo "Executing: $statement\n";
        $conn->executeStatement($statement);
    }

    // Commit the transaction
    $conn->commit();

    echo "Additional schema updates applied successfully\n";
} catch (\Exception $e) {
    // Roll back the transaction on error
    if ($conn->isTransactionActive()) {
        $conn->rollBack();
    }
    echo "Error applying additional schema updates: " . $e->getMessage() . "\n";
    exit(1);
}

echo "All schema operations completed successfully!\n";
exit(0);
