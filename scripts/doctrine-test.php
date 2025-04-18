<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\SenderName;
use App\Repositories\Doctrine\SenderNameRepository;
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
$senderNameRepository = new SenderNameRepository($entityManager);

// Create a new sender name
$senderName = new SenderName();
$senderName->setUserId(1);
$senderName->setName('Test Sender Name');
$senderName->setStatus('pending');

// Save the sender name
try {
    echo "Creating a new sender name...\n";
    $senderNameRepository->save($senderName);
    echo "Sender name created with ID: " . $senderName->getId() . "\n";
} catch (\Exception $e) {
    echo "Error creating sender name: " . $e->getMessage() . "\n";

    // Check if the error is related to missing tables
    if (strpos($e->getMessage(), 'no such table') !== false) {
        echo "It seems the database tables don't exist yet. Let's create them.\n";

        // Get the schema tool
        $schemaManager = $entityManager->getConnection()->createSchemaManager();

        // Create the schema
        echo "Creating database schema...\n";
        $metadataFactory = $entityManager->getMetadataFactory();
        $metadata = $metadataFactory->getAllMetadata();

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
        $schemaTool->createSchema($metadata);

        echo "Schema created successfully. Please run the script again.\n";
        exit(1);
    }

    exit(1);
}

// Find all sender names
echo "\nFinding all sender names...\n";
$senderNames = $senderNameRepository->findAll();
echo "Found " . count($senderNames) . " sender names:\n";
foreach ($senderNames as $sn) {
    echo "- ID: " . $sn->getId() . ", Name: " . $sn->getName() . ", Status: " . $sn->getStatus() . "\n";
}

// Find sender names by user ID
echo "\nFinding sender names for user ID 1...\n";
$userSenderNames = $senderNameRepository->findByUserId(1);
echo "Found " . count($userSenderNames) . " sender names for user ID 1:\n";
foreach ($userSenderNames as $sn) {
    echo "- ID: " . $sn->getId() . ", Name: " . $sn->getName() . ", Status: " . $sn->getStatus() . "\n";
}

// Find sender names by status
echo "\nFinding pending sender names...\n";
$pendingSenderNames = $senderNameRepository->findByStatus('pending');
echo "Found " . count($pendingSenderNames) . " pending sender names:\n";
foreach ($pendingSenderNames as $sn) {
    echo "- ID: " . $sn->getId() . ", Name: " . $sn->getName() . ", Status: " . $sn->getStatus() . "\n";
}

// Approve a sender name
if (!empty($pendingSenderNames)) {
    $senderNameToApprove = $pendingSenderNames[0];
    echo "\nApproving sender name with ID " . $senderNameToApprove->getId() . "...\n";
    $approvedSenderName = $senderNameRepository->approve($senderNameToApprove->getId());
    echo "Sender name approved. New status: " . $approvedSenderName->getStatus() . "\n";
}

// Count sender names
echo "\nCounting sender names...\n";
$totalCount = $senderNameRepository->count();
$pendingCount = $senderNameRepository->countByStatus('pending');
$approvedCount = $senderNameRepository->countByStatus('approved');
$rejectedCount = $senderNameRepository->countByStatus('rejected');
echo "Total: $totalCount, Pending: $pendingCount, Approved: $approvedCount, Rejected: $rejectedCount\n";

echo "\nDoctrine ORM test completed successfully!\n";
