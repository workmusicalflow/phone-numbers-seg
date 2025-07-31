<?php
/**
 * Create SMS queue table for the queue system
 * 
 * This script creates the necessary database table for the SMS queue system.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize container
$container = new \App\GraphQL\DIContainer();

// Get database connection from container
$pdo = $container->get(PDO::class);

// Check database type
$isMySQL = strpos($pdo->getAttribute(PDO::ATTR_DRIVER_NAME), 'mysql') !== false;

// Choose the appropriate schema
$sqlFile = $isMySQL 
    ? __DIR__ . '/../src/database/migrations/create_sms_queue_table.sql'
    : null;

// For SQLite, use inline SQL
$sqliteSql = <<<SQL
CREATE TABLE IF NOT EXISTS "sms_queue" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "phone_number" TEXT NOT NULL,
  "message" TEXT NOT NULL,
  "user_id" INTEGER,
  "segment_id" INTEGER,
  "status" TEXT NOT NULL DEFAULT 'PENDING',
  "created_at" TEXT NOT NULL,
  "last_attempt_at" TEXT,
  "next_attempt_at" TEXT,
  "attempts" INTEGER NOT NULL DEFAULT 0,
  "priority" INTEGER NOT NULL DEFAULT 5,
  "error_message" TEXT,
  "message_id" TEXT,
  "sender_name" TEXT,
  "sender_address" TEXT,
  "batch_id" TEXT
);

CREATE INDEX IF NOT EXISTS "idx_sms_queue_status" ON "sms_queue" ("status");
CREATE INDEX IF NOT EXISTS "idx_sms_queue_next_attempt" ON "sms_queue" ("next_attempt_at");
CREATE INDEX IF NOT EXISTS "idx_sms_queue_user_id" ON "sms_queue" ("user_id");
CREATE INDEX IF NOT EXISTS "idx_sms_queue_segment_id" ON "sms_queue" ("segment_id");
CREATE INDEX IF NOT EXISTS "idx_sms_queue_batch_id" ON "sms_queue" ("batch_id");
SQL;

// If MySQL and SQL file exists, read from file
if ($isMySQL && file_exists($sqlFile)) {
    $sql = file_get_contents($sqlFile);
    
    // Split SQL statements
    $statements = explode(';', $sql);
    $statements = array_filter($statements, function($stmt) {
        return trim($stmt) !== '';
    });
    
    // Execute each statement
    foreach ($statements as $statement) {
        // Skip comments and empty lines
        if (substr(trim($statement), 0, 2) === '--' || trim($statement) === '') {
            continue;
        }
        
        // Execute statement
        try {
            $pdo->exec($statement);
            echo "Executed statement successfully.\n";
        } catch (PDOException $e) {
            echo "Error executing statement: " . $e->getMessage() . "\n";
            echo "Statement: " . $statement . "\n";
        }
    }
} else {
    // Execute SQLite SQL directly
    try {
        $statements = explode(';', $sqliteSql);
        foreach ($statements as $statement) {
            if (trim($statement) === '') {
                continue;
            }
            $pdo->exec($statement);
        }
        echo "Table 'sms_queue' created successfully (SQLite).\n";
    } catch (PDOException $e) {
        echo "Error creating table: " . $e->getMessage() . "\n";
    }
}

// Verify the table exists
try {
    $result = $pdo->query("SELECT 1 FROM sms_queue LIMIT 1");
    if ($result !== false) {
        echo "Success: Table 'sms_queue' exists and is accessible.\n";
    }
} catch (PDOException $e) {
    echo "Warning: Could not verify table existence: " . $e->getMessage() . "\n";
}

echo "Script execution completed.\n";