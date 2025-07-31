<?php

/**
 * Script to clear SMS history for a specific user
 * 
 * This script deletes all SMS history records for a specific user from the database.
 * It uses the Doctrine SMSHistoryRepository to perform the deletion.
 * 
 * Usage: php scripts/clear-sms-history-for-user.php [user_id]
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Repositories\Doctrine\SMSHistoryRepository;
use Doctrine\ORM\EntityManager;

// Get the user ID from the command-line arguments, default to 2
$userId = isset($argv[1]) ? (int)$argv[1] : 2;

// Get the entity manager
/** @var EntityManager $entityManager */
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Get the SMS history repository
$smsHistoryRepository = new SMSHistoryRepository($entityManager);

// Count the number of SMS history records for the user
$count = $smsHistoryRepository->countByUserId($userId);
echo "Found $count SMS history records for user ID $userId\n";

// Delete all SMS history records for the user
$result = $smsHistoryRepository->removeAllByUserId($userId);

if ($result) {
    echo "Successfully deleted all SMS history records for user ID $userId\n";
} else {
    echo "Failed to delete SMS history records for user ID $userId\n";
}
