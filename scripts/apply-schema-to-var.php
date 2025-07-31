<?php

/**
 * Script to apply schema updates to the var/database.sqlite file
 * 
 * This script executes the SQL statements in update-schema.sql to update the database schema
 * in the var/database.sqlite file used by Doctrine.
 * 
 * Usage: php scripts/apply-schema-to-var.php
 */

// Path to the SQLite database used by Doctrine
$dbPath = __DIR__ . '/../var/database.sqlite';

// Path to the SQL file
$sqlFilePath = __DIR__ . '/update-schema.sql';

// Check if the database file exists
if (!file_exists($dbPath)) {
    echo "Error: Database file not found at $dbPath\n";
    echo "Creating the directory and an empty database file...\n";

    // Create the directory if it doesn't exist
    $varDir = dirname($dbPath);
    if (!is_dir($varDir)) {
        mkdir($varDir, 0777, true);
    }

    // Create an empty database file
    $pdo = new PDO("sqlite:$dbPath");
    echo "Empty database file created at $dbPath\n";
}

// Check if the SQL file exists
if (!file_exists($sqlFilePath)) {
    echo "Error: SQL file not found at $sqlFilePath\n";
    exit(1);
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
    // Connect to the database
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Begin a transaction
    $pdo->beginTransaction();

    // Execute each statement
    foreach ($statements as $statement) {
        echo "Executing: $statement\n";
        $pdo->exec($statement);
    }

    // Commit the transaction
    $pdo->commit();

    echo "Schema updates applied successfully to $dbPath\n";
} catch (PDOException $e) {
    // Roll back the transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
