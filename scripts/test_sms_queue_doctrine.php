<?php

/**
 * Test script for SMSQueue Doctrine Repository
 * 
 * This script tests the functionality of the SMSQueue Doctrine Repository
 * to ensure it works correctly after the refactoring.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\SMSQueue;
use App\Repositories\Interfaces\SMSQueueRepositoryInterface;
use DI\ContainerBuilder;

// Load the container using the configuration files
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../src/config/di.php');
$container = $containerBuilder->build();

// Get the repository from the container
$repository = $container->get(SMSQueueRepositoryInterface::class);

// Test saving a new SMS queue entry
$smsQueue = new SMSQueue();
$smsQueue->setPhoneNumber('+22501234567');
$smsQueue->setMessage('This is a test message');
$smsQueue->setUserId(1);
$smsQueue->setPriority(SMSQueue::PRIORITY_HIGH);
$smsQueue->setBatchId('test_batch_' . uniqid());
$smsQueue->setSenderName('Test Sender');
$smsQueue->setSenderAddress('tel:+22501234567');
$smsQueue->setStatus(SMSQueue::STATUS_PENDING);
$smsQueue->setCreatedAt(new \DateTime());
$smsQueue->setNextAttemptAt(new \DateTime());

echo "Testing save method...\n";
try {
    $savedQueue = $repository->save($smsQueue);
    echo "SMS Queue entry saved with ID: " . $savedQueue->getId() . "\n";
} catch (\Exception $e) {
    echo "Error saving SMS queue entry: " . $e->getMessage() . "\n";
    exit(1);
}

// Test findById
echo "\nTesting findById method...\n";
$foundQueue = $repository->findById($savedQueue->getId());
if ($foundQueue) {
    echo "Found SMS queue entry with ID: " . $foundQueue->getId() . "\n";
    echo "Phone number: " . $foundQueue->getPhoneNumber() . "\n";
    echo "Message: " . $foundQueue->getMessage() . "\n";
    echo "Status: " . $foundQueue->getStatus() . "\n";
} else {
    echo "Could not find SMS queue entry with ID: " . $savedQueue->getId() . "\n";
    exit(1);
}

// Test updateStatus
echo "\nTesting updateStatus method...\n";
$updateResult = $repository->updateStatus($savedQueue->getId(), SMSQueue::STATUS_PROCESSING);
if ($updateResult) {
    echo "Updated status to PROCESSING\n";
    
    // Verify the update
    $updatedQueue = $repository->findById($savedQueue->getId());
    echo "New status: " . $updatedQueue->getStatus() . "\n";
    
    if ($updatedQueue->getStatus() !== SMSQueue::STATUS_PROCESSING) {
        echo "Status was not updated correctly!\n";
        exit(1);
    }
} else {
    echo "Failed to update status\n";
    exit(1);
}

// Test findByStatus
echo "\nTesting findByStatus method...\n";
$processingEntries = $repository->findByStatus(SMSQueue::STATUS_PROCESSING);
echo "Found " . count($processingEntries) . " entries with PROCESSING status\n";

// Test marking as sent
echo "\nTesting marking as sent...\n";
$foundQueue->markAsSent('TEST_' . uniqid());
$repository->save($foundQueue);
echo "Marked as sent with message ID: " . $foundQueue->getMessageId() . "\n";

// Test countByStatus
echo "\nTesting countByStatus method...\n";
$sentCount = $repository->countByStatus(SMSQueue::STATUS_SENT);
echo "Found $sentCount entries with SENT status\n";

// Test increaseAttemptCount
echo "\nTesting increaseAttemptCount method...\n";
$foundQueue->setStatus(SMSQueue::STATUS_PENDING);
$repository->save($foundQueue);
$increaseResult = $repository->increaseAttemptCount($foundQueue->getId(), new \DateTime('+5 minutes'));
if ($increaseResult) {
    echo "Increased attempt count successfully\n";
    
    // Verify the update
    $updatedQueue = $repository->findById($foundQueue->getId());
    echo "New attempt count: " . $updatedQueue->getAttempts() . "\n";
    echo "Next attempt at: " . $updatedQueue->getNextAttemptAt()->format('Y-m-d H:i:s') . "\n";
} else {
    echo "Failed to increase attempt count\n";
    exit(1);
}

// Clean up - delete the test entry
echo "\nCleaning up (deleting test entry)...\n";
$deleteResult = $container->get(\Doctrine\ORM\EntityManager::class)->createQueryBuilder()
    ->delete(SMSQueue::class, 'sq')
    ->where('sq.id = :id')
    ->setParameter('id', $foundQueue->getId())
    ->getQuery()
    ->execute();

echo "Deleted $deleteResult test entries\n";

echo "\nAll tests completed successfully!\n";