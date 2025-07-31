<?php

/**
 * Script to align the users table with the User entity schema
 * 
 * This script will check if the users table has all the columns defined in the User entity
 * and add missing columns if needed. It uses direct PDO statements to ensure the database
 * matches the expected schema, regardless of whether Doctrine was used to create the tables.
 * 
 * Usage: php scripts/fix-user-schema.php
 */

// Path to the SQLite database
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
    
    // Get table information
    $stmt = $pdo->query("PRAGMA table_info(users)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create a map of existing columns
    $existingColumns = [];
    foreach ($columns as $column) {
        $existingColumns[$column['name']] = true;
    }
    
    // Check for missing columns and add them
    $missingColumns = [];
    
    // Check for api_key column
    if (!isset($existingColumns['api_key'])) {
        $missingColumns[] = "api_key";
        $pdo->exec("ALTER TABLE users ADD COLUMN api_key TEXT DEFAULT NULL");
    }
    
    // Check for reset_token column
    if (!isset($existingColumns['reset_token'])) {
        $missingColumns[] = "reset_token";
        $pdo->exec("ALTER TABLE users ADD COLUMN reset_token TEXT DEFAULT NULL");
    }
    
    // Check for is_admin column
    if (!isset($existingColumns['is_admin'])) {
        $missingColumns[] = "is_admin";
        // SQLite doesn't support default values in ALTER TABLE ADD COLUMN
        // So we need a workaround using a temporary table
        $pdo->exec("
            CREATE TABLE users_temp (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                email TEXT UNIQUE,
                sms_credit INTEGER DEFAULT 10,
                sms_limit INTEGER NULL,
                api_key TEXT DEFAULT NULL,
                reset_token TEXT DEFAULT NULL,
                is_admin INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
            
            INSERT INTO users_temp (id, username, password, email, sms_credit, sms_limit, api_key, reset_token, created_at, updated_at)
            SELECT id, username, password, email, sms_credit, sms_limit, api_key, reset_token, created_at, updated_at FROM users;
            
            DROP TABLE users;
            
            ALTER TABLE users_temp RENAME TO users;
            
            CREATE INDEX idx_users_username ON users(username);
        ");
    }
    
    // Output results
    if (empty($missingColumns)) {
        echo "Users table schema is already up to date.\n";
    } else {
        echo "Added missing columns to users table: " . implode(", ", $missingColumns) . "\n";
    }
    
    // Now let's check if the users table exists and has records
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    
    echo "Number of users in the database: $count\n";
    
    if ($count == 0) {
        echo "Warning: No users found in the database!\n";
        echo "Consider running: php src/scripts/create_user.php to create a test user.\n";
    }
    
    // Verify the structure matches expectations
    echo "\nCurrent users table structure:\n";
    $stmt = $pdo->query("PRAGMA table_info(users)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "- " . $column['name'] . " (" . $column['type'] . ")" . 
             ($column['notnull'] ? " NOT NULL" : "") . 
             ($column['pk'] ? " PRIMARY KEY" : "") . 
             ($column['dflt_value'] ? " DEFAULT " . $column['dflt_value'] : "") . 
             "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}