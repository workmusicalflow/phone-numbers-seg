<?php

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "Fixing SMS History table schema with direct SQL...\n";

// Connect to the database
$dbPath = __DIR__ . '/../var/database.sqlite';
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create a backup of the database
$backupPath = $dbPath . '.backup.' . date('YmdHis');
copy($dbPath, $backupPath);
echo "Created backup at $backupPath\n";

// Drop and recreate the table with the correct schema
try {
    // Begin transaction
    $pdo->beginTransaction();

    // Drop the existing table if it exists
    $pdo->exec("DROP TABLE IF EXISTS sms_history");

    // Create the table with the correct schema using a single SQL statement
    $sql = "CREATE TABLE sms_history (
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
    )";

    $pdo->exec($sql);

    // Commit transaction
    $pdo->commit();

    // Verify the schema by checking if the table exists
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='sms_history'");
    $tableExists = $stmt->fetchColumn();
    echo "Table exists after fix: " . ($tableExists ? "Yes" : "No") . "\n";

    echo "SMS History table schema fixed successfully!\n";
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    echo "Restore from backup at $backupPath if needed.\n";
}
