<?php
/**
 * Test the SMS queue system
 * 
 * This script tests the SMS queue system by enqueueing test messages and processing them.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize container
$container = new \App\GraphQL\DIContainer();

// Get services
$smsQueueService = $container->get(\App\Services\Interfaces\SMSQueueServiceInterface::class);
$orangeAPIClient = $container->get(\App\Services\Interfaces\OrangeAPIClientInterface::class);
$tokenCache = $container->get(\App\Services\Interfaces\TokenCacheInterface::class);
$pdo = $container->get(PDO::class);

// Display test menu
echo "SMS Queue System Test\n";
echo "====================\n";
echo "1. Enqueue a test SMS\n";
echo "2. Enqueue 5 test SMS messages\n";
echo "3. Process next batch of SMS messages\n";
echo "4. Get queue statistics\n";
echo "5. Check token cache\n";
echo "6. Clear token cache\n";
echo "7. Get status of a batch\n";
echo "8. Cancel a batch\n";
echo "9. Exit\n";
echo "\nEnter your choice: ";

$choice = trim(fgets(STDIN));

switch ($choice) {
    case '1':
        // Enqueue a test SMS
        echo "Enter phone number (or leave empty for test number): ";
        $phoneNumber = trim(fgets(STDIN));
        if (empty($phoneNumber)) {
            $phoneNumber = '+2250700000000'; // Test number
        }

        echo "Enter message (or leave empty for test message): ";
        $message = trim(fgets(STDIN));
        if (empty($message)) {
            $message = "This is a test message from the SMS queue system.";
        }

        try {
            $smsQueue = $smsQueueService->enqueue($phoneNumber, $message);
            echo "SMS enqueued successfully with ID: " . $smsQueue->getId() . "\n";
        } catch (Exception $e) {
            echo "Error enqueueing SMS: " . $e->getMessage() . "\n";
        }
        break;

    case '2':
        // Enqueue 5 test SMS messages
        echo "Enqueueing 5 test SMS messages...\n";
        $testNumbers = [
            '+2250700000001',
            '+2250700000002',
            '+2250700000003',
            '+2250700000004',
            '+2250700000005'
        ];

        echo "Enter message (or leave empty for test message): ";
        $message = trim(fgets(STDIN));
        if (empty($message)) {
            $message = "This is a bulk test message from the SMS queue system.";
        }

        try {
            $batchId = $smsQueueService->enqueueBulk($testNumbers, $message);
            echo "5 SMS messages enqueued successfully with batch ID: " . $batchId . "\n";
        } catch (Exception $e) {
            echo "Error enqueueing bulk SMS: " . $e->getMessage() . "\n";
        }
        break;

    case '3':
        // Process next batch of SMS messages
        echo "Enter batch size (or leave empty for default 50): ";
        $batchSize = trim(fgets(STDIN));
        if (empty($batchSize)) {
            $batchSize = 50;
        } else {
            $batchSize = (int)$batchSize;
        }

        try {
            echo "Processing next batch of SMS messages...\n";
            $result = $smsQueueService->processNextBatch($batchSize);
            echo "Batch processing complete.\n";
            echo "  - SMS sent: " . $result['sent'] . "\n";
            echo "  - SMS failed: " . $result['failed'] . "\n";
            echo "  - Total processed: " . $result['total'] . "\n";
        } catch (Exception $e) {
            echo "Error processing batch: " . $e->getMessage() . "\n";
        }
        break;

    case '4':
        // Get queue statistics
        try {
            $stats = $smsQueueService->getQueueStats();
            echo "Queue statistics:\n";
            echo "  - Total: " . $stats['total'] . "\n";
            echo "  - Pending: " . $stats['pending'] . "\n";
            echo "  - Processing: " . $stats['processing'] . "\n";
            echo "  - Sent: " . $stats['sent'] . "\n";
            echo "  - Failed: " . $stats['failed'] . "\n";
            echo "  - Cancelled: " . $stats['cancelled'] . "\n";

            // Also show queue entries from the database
            $stmt = $pdo->query("SELECT id, phone_number, status, created_at FROM sms_queue ORDER BY created_at DESC LIMIT 10");
            $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($entries) > 0) {
                echo "\nLatest 10 queue entries:\n";
                echo str_pad("ID", 5) . " | " . str_pad("Phone", 15) . " | " . str_pad("Status", 10) . " | Created At\n";
                echo str_repeat("-", 60) . "\n";
                foreach ($entries as $entry) {
                    echo str_pad($entry['id'], 5) . " | " 
                         . str_pad($entry['phone_number'], 15) . " | "
                         . str_pad($entry['status'], 10) . " | "
                         . $entry['created_at'] . "\n";
                }
            } else {
                echo "\nNo queue entries found.\n";
            }
        } catch (Exception $e) {
            echo "Error getting queue statistics: " . $e->getMessage() . "\n";
        }
        break;

    case '5':
        // Check token cache
        try {
            $token = $tokenCache->getToken();
            if ($token !== null) {
                echo "Token found in cache: " . substr($token, 0, 10) . "...\n";
                echo "Token is valid.\n";
            } else {
                echo "No valid token found in cache.\n";
                echo "Attempting to get a new token...\n";
                
                try {
                    $token = $orangeAPIClient->getAccessToken();
                    echo "New token obtained: " . substr($token, 0, 10) . "...\n";
                } catch (Exception $e) {
                    echo "Error getting new token: " . $e->getMessage() . "\n";
                }
            }
        } catch (Exception $e) {
            echo "Error checking token cache: " . $e->getMessage() . "\n";
        }
        break;

    case '6':
        // Clear token cache
        try {
            $tokenCache->invalidateToken();
            echo "Token cache cleared successfully.\n";
        } catch (Exception $e) {
            echo "Error clearing token cache: " . $e->getMessage() . "\n";
        }
        break;

    case '7':
        // Get status of a batch
        echo "Enter batch ID: ";
        $batchId = trim(fgets(STDIN));
        if (empty($batchId)) {
            echo "Batch ID cannot be empty.\n";
            break;
        }

        try {
            $status = $smsQueueService->getBatchStatus($batchId);
            echo "Batch status:\n";
            echo "  - Total: " . $status['total'] . "\n";
            echo "  - Sent: " . $status['sent'] . "\n";
            echo "  - Failed: " . $status['failed'] . "\n";
            echo "  - Pending: " . $status['pending'] . "\n";
            echo "  - Processing: " . $status['processing'] . "\n";
            echo "  - Cancelled: " . $status['cancelled'] . "\n";
            echo "  - Overall status: " . $status['status'] . "\n";
        } catch (Exception $e) {
            echo "Error getting batch status: " . $e->getMessage() . "\n";
        }
        break;

    case '8':
        // Cancel a batch
        echo "Enter batch ID: ";
        $batchId = trim(fgets(STDIN));
        if (empty($batchId)) {
            echo "Batch ID cannot be empty.\n";
            break;
        }

        echo "Enter reason for cancellation (optional): ";
        $reason = trim(fgets(STDIN));

        try {
            $cancelledCount = $smsQueueService->cancelBatch($batchId, $reason);
            echo "Batch cancelled successfully. " . $cancelledCount . " SMS messages cancelled.\n";
        } catch (Exception $e) {
            echo "Error cancelling batch: " . $e->getMessage() . "\n";
        }
        break;

    case '9':
        // Exit
        echo "Exiting...\n";
        break;

    default:
        echo "Invalid choice.\n";
        break;
}

echo "\nTest script execution completed.\n";