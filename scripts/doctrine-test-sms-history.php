<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\SMSHistory;
use App\Repositories\Doctrine\SMSHistoryRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Get the entity manager directly from bootstrap file
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Create the repository manually
$smsHistoryRepository = new SMSHistoryRepository($entityManager);

// Create a new SMS history record
$smsHistory = new SMSHistory();
$smsHistory->setPhoneNumber('+2250777104936');
$smsHistory->setMessage('This is a test SMS message');
$smsHistory->setStatus('SENT');
$smsHistory->setSenderAddress('tel:+2250595016840');
$smsHistory->setSenderName('225HBC');
$smsHistory->setUserId(1); // Assuming user ID 1 exists

// Save the SMS history record
try {
    echo "Creating a new SMS history record...\n";
    $smsHistoryRepository->save($smsHistory);
    echo "SMS history record created with ID: " . $smsHistory->getId() . "\n";
} catch (\Exception $e) {
    echo "Error creating SMS history record: " . $e->getMessage() . "\n";

    // Check if the error is related to missing tables
    if (strpos($e->getMessage(), 'no such table') !== false) {
        echo "It seems the database tables don't exist yet. Let's create them.\n";

        // Get the schema tool
        $schemaManager = $entityManager->getConnection()->createSchemaManager();

        // Create the schema for SMSHistory entity
        echo "Creating database schema for SMSHistory entity...\n";
        $smsHistoryMetadata = $entityManager->getClassMetadata(SMSHistory::class);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);

        try {
            // Try to create the tables
            $schemaTool->createSchema([$smsHistoryMetadata]);
        } catch (\Exception $e) {
            // If there's an error, try to update the schema instead
            echo "Error creating schema: " . $e->getMessage() . "\n";
            echo "Trying to update schema instead...\n";
            $schemaTool->updateSchema([$smsHistoryMetadata]);
        }

        echo "Schema created successfully. Please run the script again.\n";
        exit(1);
    }

    exit(1);
}

// Find all SMS history records
echo "\nFinding all SMS history records...\n";
$smsHistoryRecords = $smsHistoryRepository->findAll();
echo "Found " . count($smsHistoryRecords) . " SMS history records:\n";
foreach ($smsHistoryRecords as $record) {
    echo "- ID: " . $record->getId() . ", Phone: " . $record->getPhoneNumber() . ", Status: " . $record->getStatus() . "\n";
}

// Find SMS history record by ID
echo "\nFinding SMS history record by ID " . $smsHistory->getId() . "...\n";
$foundSMSHistory = $smsHistoryRepository->findById($smsHistory->getId());
if ($foundSMSHistory) {
    echo "Found SMS history record: ID: " . $foundSMSHistory->getId() . ", Phone: " . $foundSMSHistory->getPhoneNumber() . ", Status: " . $foundSMSHistory->getStatus() . "\n";
} else {
    echo "SMS history record not found.\n";
}

// Find SMS history records by phone number
echo "\nFinding SMS history records by phone number " . $smsHistory->getPhoneNumber() . "...\n";
$phoneNumberRecords = $smsHistoryRepository->findByPhoneNumber($smsHistory->getPhoneNumber());
echo "Found " . count($phoneNumberRecords) . " SMS history records for phone number " . $smsHistory->getPhoneNumber() . ":\n";
foreach ($phoneNumberRecords as $record) {
    echo "- ID: " . $record->getId() . ", Phone: " . $record->getPhoneNumber() . ", Status: " . $record->getStatus() . "\n";
}

// Find SMS history records by status
echo "\nFinding SMS history records by status " . $smsHistory->getStatus() . "...\n";
$statusRecords = $smsHistoryRepository->findByStatus($smsHistory->getStatus());
echo "Found " . count($statusRecords) . " SMS history records with status " . $smsHistory->getStatus() . ":\n";
foreach ($statusRecords as $record) {
    echo "- ID: " . $record->getId() . ", Phone: " . $record->getPhoneNumber() . ", Status: " . $record->getStatus() . "\n";
}

// Find SMS history records by user ID
echo "\nFinding SMS history records by user ID " . $smsHistory->getUserId() . "...\n";
$userRecords = $smsHistoryRepository->findByUserId($smsHistory->getUserId());
echo "Found " . count($userRecords) . " SMS history records for user ID " . $smsHistory->getUserId() . ":\n";
foreach ($userRecords as $record) {
    echo "- ID: " . $record->getId() . ", Phone: " . $record->getPhoneNumber() . ", Status: " . $record->getStatus() . "\n";
}

// Count all SMS history records
echo "\nCounting all SMS history records...\n";
$smsHistoryCount = $smsHistoryRepository->countAll();
echo "Total SMS history records: " . $smsHistoryCount . "\n";

// Count SMS history records by date
$today = date('Y-m-d');
echo "\nCounting SMS history records for date " . $today . "...\n";
$dateCount = $smsHistoryRepository->countByDate($today);
echo "Total SMS history records for date " . $today . ": " . $dateCount . "\n";

// Count SMS history records by user ID
echo "\nCounting SMS history records for user ID " . $smsHistory->getUserId() . "...\n";
$userCount = $smsHistoryRepository->countByUserId($smsHistory->getUserId());
echo "Total SMS history records for user ID " . $smsHistory->getUserId() . ": " . $userCount . "\n";

// Get daily counts for a date range
$startDate = date('Y-m-d', strtotime('-7 days'));
$endDate = date('Y-m-d');
echo "\nGetting daily counts for date range " . $startDate . " to " . $endDate . "...\n";
$dailyCounts = $smsHistoryRepository->getDailyCountsForDateRange($startDate, $endDate);
echo "Daily counts for date range " . $startDate . " to " . $endDate . ":\n";
foreach ($dailyCounts as $count) {
    echo "- Date: " . $count['date'] . ", Count: " . $count['count'] . "\n";
}

// Create a new SMS history record using the create method
echo "\nCreating a new SMS history record using the create method...\n";
$createdSMSHistory = $smsHistoryRepository->create(
    '+2250777104937',
    'This is another test SMS message',
    'SENT',
    'msg-123456',
    null,
    'tel:+2250595016840',
    '225HBC',
    null,
    null,
    1
);
echo "SMS history record created with ID: " . $createdSMSHistory->getId() . "\n";

// Update segment ID for phone numbers
echo "\nUpdating segment ID for phone numbers...\n";
$phoneNumbers = [$smsHistory->getPhoneNumber(), $createdSMSHistory->getPhoneNumber()];
$segmentId = 1; // Assuming segment ID 1 exists
$updateResult = $smsHistoryRepository->updateSegmentIdForPhoneNumbers($phoneNumbers, $segmentId);
echo "Update segment ID result: " . ($updateResult ? "Success" : "Failed") . "\n";

// Verify update
echo "\nVerifying segment ID update...\n";
$updatedSMSHistory = $smsHistoryRepository->findById($smsHistory->getId());
echo "Segment ID for SMS history record " . $updatedSMSHistory->getId() . ": " . ($updatedSMSHistory->getSegmentId() ?? 'NULL') . "\n";

// Store IDs before deletion
$smsHistoryId = $smsHistory->getId();
$createdSMSHistoryId = $createdSMSHistory->getId();

// Delete SMS history records
echo "\nDeleting SMS history records...\n";
$deleteResult1 = $smsHistoryRepository->delete($smsHistory);
$deleteResult2 = $smsHistoryRepository->delete($createdSMSHistory);
echo "Delete results: " . ($deleteResult1 ? "Success" : "Failed") . ", " . ($deleteResult2 ? "Success" : "Failed") . "\n";

// Verify deletion
$deletedSMSHistory1 = $smsHistoryRepository->findById($smsHistoryId);
$deletedSMSHistory2 = $smsHistoryRepository->findById($createdSMSHistoryId);
echo "SMS history records after deletion: " . ($deletedSMSHistory1 ? "Still exists (error)" : "Successfully deleted") . ", " . ($deletedSMSHistory2 ? "Still exists (error)" : "Successfully deleted") . "\n";

echo "\nDoctrine ORM SMSHistory test completed successfully!\n";
