<?php

/**
 * Database Initialization Script
 * 
 * This script creates the SQLite database and runs the initial migrations.
 */

// Define the database directory and file
$dbDir = __DIR__;
$dbFile = $dbDir . '/database.sqlite';

// Create the database file if it doesn't exist
if (!file_exists($dbFile)) {
    touch($dbFile);
    echo "Database file created: $dbFile\n";
} else {
    echo "Database file already exists: $dbFile\n";
}

// Connect to the database
try {
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to the database successfully\n";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Run the migrations
$migrationFile = $dbDir . '/migrations/create_tables.sql';
if (file_exists($migrationFile)) {
    try {
        $sql = file_get_contents($migrationFile);
        $pdo->exec($sql);
        echo "Migrations executed successfully\n";
    } catch (PDOException $e) {
        die("Migration failed: " . $e->getMessage() . "\n");
    }
} else {
    die("Migration file not found: $migrationFile\n");
}

echo "Database initialization completed\n";
