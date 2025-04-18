<?php

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "Fixing SMS History table schema...\n";

// Connect to the database
$dbPath = __DIR__ . '/../var/database.sqlite';
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Check if the table exists
$stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='sms_history'");
$tableExists = $stmt->fetchColumn();

if ($tableExists) {
    // Drop the existing table
    echo "Dropping existing sms_history table...\n";
    $pdo->exec("DROP TABLE sms_history");
}

// Create the table with the correct schema
echo "Creating sms_history table with correct schema...\n";
$pdo->exec("
    CREATE TABLE sms_history (
        id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        phone_number_id INTEGER DEFAULT NULL,
        phone_number VARCHAR(255) NOT NULL,
        message CLOB NOT NULL,
        status VARCHAR(50) NOT NULL,
        message_id VARCHAR(255) DEFAULT NULL,
        error_message CLOB DEFAULT NULL,
        sender_address VARCHAR(255) NOT NULL,
        sender_name VARCHAR(255) NOT NULL,
        segment_id INTEGER DEFAULT NULL,
        user_id INTEGER DEFAULT NULL,
        created_at DATETIME NOT NULL
    )
");

echo "SMS History table schema fixed successfully!\n";
