<?php

/**
 * SMS Queue Data Migration Script
 * 
 * This script migrates SMS queue data from a PDO-based database table structure
 * to the Doctrine ORM entities structure if needed. It should be run once after
 * deploying the refactored code to ensure data consistency.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\SMSQueue;
use DI\ContainerBuilder;

// Parse command line arguments
$options = getopt('', ['dry-run::', 'batch-size::', 'help::']);

if (isset($options['help'])) {
    echo "SMS Queue Data Migration Script\n";
    echo "==============================\n\n";
    echo "This script migrates data from the old PDO-based SMS queue to the new Doctrine ORM structure.\n\n";
    echo "Options:\n";
    echo "  --dry-run     Show what would be migrated without making any changes\n";
    echo "  --batch-size  Number of records to process at once (default: 100)\n";
    echo "  --help        Show this help message\n";
    exit(0);
}

$dryRun = isset($options['dry-run']);
$batchSize = isset($options['batch-size']) ? (int)$options['batch-size'] : 100;

// Build container
echo "Initializing container...\n";
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../src/config/di.php');
$container = $containerBuilder->build();

// Create a direct PDO connection
$pdo = $container->get(PDO::class);

// Get the Doctrine entity manager
$entityManager = $container->get(\Doctrine\ORM\EntityManager::class);

echo "SMS Queue Data Migration - " . date('Y-m-d H:i:s') . "\n";
echo "===================================" . str_repeat("=", strlen(date('Y-m-d H:i:s'))) . "\n\n";

if ($dryRun) {
    echo "DRY RUN MODE: No actual changes will be made.\n\n";
}

try {
    // Check if the table exists
    $tableExists = false;
    try {
        $stmt = $pdo->query("SELECT 1 FROM sms_queue LIMIT 1");
        $tableExists = ($stmt !== false);
    } catch (\Exception $e) {
        echo "The sms_queue table doesn't exist or is not accessible.\n";
        exit(1);
    }
    
    if (!$tableExists) {
        echo "The sms_queue table doesn't exist. Nothing to migrate.\n";
        exit(0);
    }
    
    // Count total records
    $stmt = $pdo->query("SELECT COUNT(*) FROM sms_queue");
    $totalCount = (int)$stmt->fetchColumn();
    
    echo "Found $totalCount SMS queue records to migrate.\n\n";
    
    if ($totalCount === 0) {
        echo "No records to migrate. Exiting.\n";
        exit(0);
    }
    
    // Start the migration
    echo "Starting migration using batch size of $batchSize...\n";
    
    $offset = 0;
    $migratedCount = 0;
    $errorCount = 0;
    
    while ($offset < $totalCount) {
        echo "Processing batch starting at offset $offset...\n";
        
        // Get a batch of records
        $stmt = $pdo->prepare("SELECT * FROM sms_queue ORDER BY id LIMIT :limit OFFSET :offset");
        $stmt->bindParam(':limit', $batchSize, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($records)) {
            break;
        }
        
        // Begin transaction if not dry run
        if (!$dryRun) {
            $entityManager->beginTransaction();
        }
        
        // Process each record
        $batchMigratedCount = 0;
        foreach ($records as $record) {
            try {
                // Create a new entity
                $smsQueue = new SMSQueue();
                
                // Map fields
                $smsQueue->setId((int)$record['id']);
                $smsQueue->setPhoneNumber((string)$record['phone_number']);
                $smsQueue->setMessage((string)$record['message']);
                $smsQueue->setUserId($record['user_id'] !== null ? (int)$record['user_id'] : null);
                $smsQueue->setSegmentId($record['segment_id'] !== null ? (int)$record['segment_id'] : null);
                $smsQueue->setStatus((string)$record['status']);
                $smsQueue->setCreatedAt(new \DateTime($record['created_at']));
                
                if (!empty($record['last_attempt_at'])) {
                    $smsQueue->setLastAttemptAt(new \DateTime($record['last_attempt_at']));
                }
                
                if (!empty($record['next_attempt_at'])) {
                    $smsQueue->setNextAttemptAt(new \DateTime($record['next_attempt_at']));
                }
                
                $smsQueue->setAttempts((int)$record['attempts']);
                $smsQueue->setPriority((int)$record['priority']);
                
                if (!empty($record['error_message'])) {
                    $smsQueue->setErrorMessage((string)$record['error_message']);
                }
                
                if (!empty($record['message_id'])) {
                    $smsQueue->setMessageId((string)$record['message_id']);
                }
                
                if (!empty($record['sender_name'])) {
                    $smsQueue->setSenderName((string)$record['sender_name']);
                }
                
                if (!empty($record['sender_address'])) {
                    $smsQueue->setSenderAddress((string)$record['sender_address']);
                }
                
                if (!empty($record['batch_id'])) {
                    $smsQueue->setBatchId((string)$record['batch_id']);
                }
                
                // Persist the entity if not dry run
                if (!$dryRun) {
                    $entityManager->persist($smsQueue);
                }
                
                $batchMigratedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                echo "Error migrating record ID " . $record['id'] . ": " . $e->getMessage() . "\n";
            }
        }
        
        // Commit transaction if not dry run
        if (!$dryRun && $batchMigratedCount > 0) {
            try {
                $entityManager->flush();
                $entityManager->commit();
                $migratedCount += $batchMigratedCount;
                echo "Successfully migrated $batchMigratedCount records in this batch.\n";
            } catch (\Exception $e) {
                $entityManager->rollback();
                $errorCount += $batchMigratedCount;
                echo "Error saving batch: " . $e->getMessage() . "\n";
            }
        } else if ($dryRun) {
            $migratedCount += $batchMigratedCount;
            echo "Dry run: Would migrate $batchMigratedCount records in this batch.\n";
        }
        
        $offset += $batchSize;
        echo "\n";
    }
    
    // Summary
    echo "\nMigration Summary:\n";
    echo "----------------\n";
    echo "Total records:    $totalCount\n";
    echo "Migrated records: $migratedCount\n";
    echo "Errors:           $errorCount\n";
    
    if ($dryRun) {
        echo "\nThis was a dry run. No actual changes were made. Run without --dry-run to perform the migration.\n";
    } else if ($migratedCount === $totalCount) {
        echo "\nMigration completed successfully!\n";
    } else {
        echo "\nMigration completed with errors. Please check the output and try again.\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}