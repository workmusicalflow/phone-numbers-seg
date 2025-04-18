<?php

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "Testing SMS History table insert...\n";

// Connect to the database
$dbPath = __DIR__ . '/../var/database.sqlite';
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Test inserting a record into the sms_history table
try {
    // Begin transaction
    $pdo->beginTransaction();

    // Insert a test record
    $stmt = $pdo->prepare("
        INSERT INTO sms_history (
            phone_number_id,
            phone_number,
            message,
            status,
            message_id,
            error_message,
            sender_address,
            sender_name,
            segment_id,
            user_id,
            created_at
        ) VALUES (
            :phone_number_id,
            :phone_number,
            :message,
            :status,
            :message_id,
            :error_message,
            :sender_address,
            :sender_name,
            :segment_id,
            :user_id,
            :created_at
        )
    ");

    $stmt->execute([
        'phone_number_id' => null,
        'phone_number' => '+2250123456789',
        'message' => 'Test message',
        'status' => 'sent',
        'message_id' => 'test-message-id',
        'error_message' => null,
        'sender_address' => 'tel:+2250595016840',
        'sender_name' => 'Qualitas CI',
        'segment_id' => null,
        'user_id' => 1,
        'created_at' => date('Y-m-d H:i:s')
    ]);

    // Get the ID of the inserted record
    $id = $pdo->lastInsertId();

    // Commit transaction
    $pdo->commit();

    echo "Successfully inserted a test record with ID: $id\n";

    // Verify the record was inserted
    $stmt = $pdo->prepare("SELECT * FROM sms_history WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "Retrieved record:\n";
    print_r($record);

    echo "SMS History table insert test passed!\n";
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
