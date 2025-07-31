<?php

/**
 * Script to apply schema updates to the database
 * 
 * This script executes the SQL statements in update-schema.sql to update the database schema.
 * 
 * Usage: php scripts/apply-schema-updates.php
 */

// Path to the SQLite database
$dbPath = __DIR__ . '/../src/database/database.sqlite';

// Path to the SQL file
$sqlFilePath = __DIR__ . '/update-schema.sql';

// Check if the database file exists
if (!file_exists($dbPath)) {
    echo "Error: Database file not found at $dbPath\n";
    exit(1);
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

    echo "Schema updates applied successfully\n";
} catch (PDOException $e) {
    // Roll back the transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
