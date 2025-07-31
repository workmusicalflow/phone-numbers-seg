<?php

/**
 * Script to check the schema of the var/database.sqlite file
 * 
 * This script connects to the var/database.sqlite file and prints the schema of the users table.
 * 
 * Usage: php scripts/check-var-schema.php
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

    // Get the schema of the users table
    $stmt = $pdo->query("PRAGMA table_info(users)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Schema of the users table in $dbPath:\n";
    foreach ($columns as $column) {
        echo "- {$column['name']} ({$column['type']})" . ($column['notnull'] ? ' NOT NULL' : '') . "\n";
    }

    // Check if the api_key and reset_token columns exist
    $hasApiKey = false;
    $hasResetToken = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'api_key') {
            $hasApiKey = true;
        }
        if ($column['name'] === 'reset_token') {
            $hasResetToken = true;
        }
    }

    if ($hasApiKey && $hasResetToken) {
        echo "\nThe api_key and reset_token columns exist in the users table.\n";
    } else {
        echo "\nWarning: ";
        if (!$hasApiKey) {
            echo "The api_key column does not exist in the users table. ";
        }
        if (!$hasResetToken) {
            echo "The reset_token column does not exist in the users table. ";
        }
        echo "\n";
    }

    // List all tables in the database
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "\nTables in the database:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
