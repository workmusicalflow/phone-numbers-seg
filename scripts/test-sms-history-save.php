<?php

/**
 * Script to test SMS history saving
 * 
 * This script tests saving SMS history records to both the legacy and Doctrine repositories.
 * It helps diagnose issues with SMS history not being updated in the database.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\SMSHistory as DoctrineEntity;
use App\Models\SMSHistory as LegacyModel;
use App\Repositories\Doctrine\SMSHistoryRepository as DoctrineRepository;
use App\Repositories\SMSHistoryRepository as LegacyRepository;
use Doctrine\ORM\EntityManager;

// Get the entity manager
/** @var EntityManager $entityManager */
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Get the PDO connection
$dbConfig = require __DIR__ . '/../src/config/database.php';
$dsn = 'sqlite:' . __DIR__ . '/../var/database.sqlite';
$pdo = new PDO($dsn, null, null);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create repositories
$doctrineRepository = new DoctrineRepository($entityManager);
$legacyRepository = new LegacyRepository($pdo);

// Test data
$phoneNumber = '+2250123456789';
$message = 'Test SMS history save ' . date('Y-m-d H:i:s');
$status = 'SENT';
$senderAddress = 'tel:+2250595016840';
$senderName = 'Test Sender';
$userId = 2; // Default user ID

// Test saving to legacy repository
echo "Testing legacy repository...\n";
$legacyModel = new LegacyModel(
    null,
    $phoneNumber,
    $message,
    $status,
    $senderAddress,
    $senderName,
    null, // phoneNumberId
    null, // messageId
    null, // errorMessage
    null, // segmentId
    $userId
);

try {
    $savedLegacyModel = $legacyRepository->save($legacyModel);
    echo "Successfully saved to legacy repository. ID: {$savedLegacyModel->getId()}\n";
} catch (Exception $e) {
    echo "Error saving to legacy repository: {$e->getMessage()}\n";
}

// Test saving to Doctrine repository
echo "\nTesting Doctrine repository...\n";
$doctrineEntity = new DoctrineEntity();
$doctrineEntity->setPhoneNumber($phoneNumber);
$doctrineEntity->setMessage($message);
$doctrineEntity->setStatus($status);
$doctrineEntity->setSenderAddress($senderAddress);
$doctrineEntity->setSenderName($senderName);
$doctrineEntity->setUserId($userId);
$doctrineEntity->setCreatedAt(new DateTime());

try {
    $savedDoctrineEntity = $doctrineRepository->save($doctrineEntity);
    echo "Successfully saved to Doctrine repository. ID: {$savedDoctrineEntity->getId()}\n";
} catch (Exception $e) {
    echo "Error saving to Doctrine repository: {$e->getMessage()}\n";
}

// Verify records in the database
echo "\nVerifying records in the database...\n";

// Check legacy table
$stmt = $pdo->query("SELECT COUNT(*) FROM sms_history WHERE message LIKE 'Test SMS history save%'");
$legacyCount = $stmt->fetchColumn();
echo "Found $legacyCount records in legacy table\n";

// Check if the Doctrine entity was persisted
$count = $entityManager->createQuery('SELECT COUNT(s) FROM App\Entities\SMSHistory s WHERE s.message LIKE :message')
    ->setParameter('message', 'Test SMS history save%')
    ->getSingleScalarResult();
echo "Found $count records in Doctrine entity manager\n";

// Check if the records are visible in the frontend query
echo "\nTesting frontend query...\n";
$stmt = $pdo->prepare("SELECT * FROM sms_history WHERE user_id = :userId ORDER BY created_at DESC LIMIT 5");
$stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Latest 5 SMS history records for user $userId:\n";
foreach ($results as $row) {
    echo "ID: {$row['id']}, Phone: {$row['phone_number']}, Message: {$row['message']}, Status: {$row['status']}, Created: {$row['created_at']}\n";
}
