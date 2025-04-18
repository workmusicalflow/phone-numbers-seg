<?php

/**
 * Script to add the api_key column to the users table in var/database.sqlite
 * 
 * This script connects to the var/database.sqlite file and adds the api_key column to the users table.
 * 
 * Usage: php scripts/add-api-key.php
 */

// Path to the SQLite database used by Doctrine
$dbPath = __DIR__ . '/../var/database.sqlite';

// Check if the database file exists
if (!file_exists($dbPath)) {
    echo "Error: Database file not found at $dbPath\n";
    exit(1);
}

try {
    // Connect to the database
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Add the api_key column to the users table
    $sql = "ALTER TABLE users ADD COLUMN api_key TEXT";
    $pdo->exec($sql);

    echo "api_key column added to the users table in $dbPath\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
