<?php

/**
 * Script to delete SMS history for a specific user
 * 
 * This script deletes all SMS history records for a specific user from the database.
 * By default, it deletes SMS history for user ID 2, but this can be changed by passing
 * a different user ID as a command-line argument.
 * 
 * Usage: php scripts/delete-user-sms-history.php [user_id]
 */

// Get the user ID from the command-line arguments, default to 2
$userId = isset($argv[1]) ? (int)$argv[1] : 2;

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

    // Begin a transaction
    $pdo->beginTransaction();

    // Count the number of records to be deleted
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM sms_history WHERE user_id = :user_id");
    $countStmt->execute(['user_id' => $userId]);
    $count = $countStmt->fetchColumn();

    echo "Found $count SMS history records for user ID $userId\n";

    // Delete the records
    $deleteStmt = $pdo->prepare("DELETE FROM sms_history WHERE user_id = :user_id");
    $deleteStmt->execute(['user_id' => $userId]);
    $deletedCount = $deleteStmt->rowCount();

    // Commit the transaction
    $pdo->commit();

    echo "Successfully deleted $deletedCount SMS history records for user ID $userId\n";
} catch (PDOException $e) {
    // Roll back the transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
