<?php

/**
 * Test script for the SMS Queue with History recording
 * 
 * This script tests the integration between SMSQueueService and history recording.
 * It enqueues a test SMS and processes it to verify that the history is properly recorded.
 */

// Include autoloader
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap-doctrine.php';

use App\Services\Interfaces\SMSQueueServiceInterface;
use App\Repositories\Interfaces\SMSHistoryRepositoryInterface;
use DI\Container;
use DI\ContainerBuilder;

// Create container
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../src/config/di.php');
$container = $containerBuilder->build();

try {
    echo "=== TEST SMS QUEUE WITH HISTORY RECORDING ===\n\n";
    
    // Get services from container
    $smsQueueService = $container->get(SMSQueueServiceInterface::class);
    $smsHistoryRepository = $container->get(SMSHistoryRepositoryInterface::class);
    
    // Generate a unique batch ID for this test
    $testBatchId = 'test_batch_' . uniqid();
    echo "Using batch ID: $testBatchId\n";
    
    // Test phone numbers (use test numbers that won't actually send SMS)
    $testPhoneNumbers = [
        '+22500000001',
        '+22500000002',
        '+22500000003'
    ];
    
    echo "Enqueueing " . count($testPhoneNumbers) . " test SMS messages...\n";
    
    // Enqueue test SMS messages
    $batchId = $smsQueueService->enqueueBulk(
        $testPhoneNumbers,
        "This is a test SMS for queue-history integration. Batch: $testBatchId",
        null, // No segment ID
        1,    // Use admin user ID
        'TEST', // Sender name
        2     // Priority normal
    );
    
    echo "Messages enqueued with batch ID: $batchId\n";
    
    // Process the queue
    echo "Processing the SMS queue...\n";
    $result = $smsQueueService->processNextBatch();
    
    echo "Queue processing result: " . json_encode($result) . "\n";
    
    // Check batch status
    $batchStatus = $smsQueueService->getBatchStatus($batchId);
    echo "Batch status: " . json_encode($batchStatus) . "\n";
    
    // Wait a moment to make sure all database operations complete
    sleep(1);
    
    // Check history records
    echo "\nChecking SMS history records...\n";
    
    // Rechercher par le batch ID
    $historyRecords = $smsHistoryRepository->findBy(['batchId' => $batchId], ['id' => 'DESC'], 10);
    
    echo "Found " . count($historyRecords) . " history records with our batch ID\n";
    
    if (count($historyRecords) > 0) {
        echo "History record details:\n";
        foreach ($historyRecords as $record) {
            echo " - Phone: " . $record->getPhoneNumber() . ", Status: " . $record->getStatus() . ", BatchID: " . $record->getBatchId() . "\n";
        }
    }
    
    // Check if all test phone numbers have a history record
    $foundPhoneNumbers = array_map(function($record) {
        // Le format de téléphone peut être différent (avec ou sans tel:)
        $phone = $record->getPhoneNumber();
        return str_replace('tel:', '', $phone);
    }, $historyRecords);
    
    // Normaliser aussi les numéros de test pour la comparaison
    $normalizedTestNumbers = array_map(function($phone) {
        return str_replace('tel:', '', $phone);
    }, $testPhoneNumbers);
    
    $missingPhoneNumbers = array_diff($normalizedTestNumbers, $foundPhoneNumbers);
    
    if (empty($missingPhoneNumbers)) {
        echo "SUCCESS: All test phone numbers have history records!\n";
    } else {
        echo "WARNING: Some test phone numbers are missing history records: " . 
            implode(", ", $missingPhoneNumbers) . "\n";
    }
    
    echo "\n=== TEST COMPLETED ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}