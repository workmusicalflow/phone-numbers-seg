<?php

/**
 * Script to test sending an SMS and verifying it's recorded in the history
 * 
 * This script sends a test SMS and checks if it's correctly recorded in the SMS history.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\SMSService;
use App\Services\OrangeAPIClient;
use App\Repositories\Doctrine\SMSHistoryRepository;
use App\Repositories\Doctrine\PhoneNumberRepository;
use App\Repositories\Doctrine\CustomSegmentRepository;
use App\Repositories\Doctrine\UserRepository;
use App\Repositories\Doctrine\ContactRepository;
use Doctrine\ORM\EntityManager;

// Get the entity manager
/** @var EntityManager $entityManager */
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Create repositories
$smsHistoryRepository = new SMSHistoryRepository($entityManager);
$phoneNumberRepository = new PhoneNumberRepository($entityManager, null, null);
$customSegmentRepository = new CustomSegmentRepository($entityManager);
$userRepository = new UserRepository($entityManager);
$contactRepository = new ContactRepository($entityManager);

// Create the Orange API client
$clientId = $_ENV['ORANGE_API_CLIENT_ID'] ?? '';
$clientSecret = $_ENV['ORANGE_API_CLIENT_SECRET'] ?? '';
$defaultSenderAddress = $_ENV['ORANGE_DEFAULT_SENDER_ADDRESS'] ?? '';
$defaultSenderName = $_ENV['ORANGE_DEFAULT_SENDER_NAME'] ?? '';
$orangeApiClient = new OrangeAPIClient($clientId, $clientSecret, $defaultSenderAddress, $defaultSenderName);

// Create the SMS service
$smsService = new SMSService(
    $orangeApiClient,
    $phoneNumberRepository,
    $customSegmentRepository,
    $smsHistoryRepository,
    $userRepository,
    $contactRepository
);

// Test data
$phoneNumber = '+2250123456789';
$message = 'Test SMS ' . date('Y-m-d H:i:s');
$userId = 2; // Default user ID

// Count SMS history records before sending
$countBefore = $smsHistoryRepository->countByUserId($userId);
echo "SMS history records before sending: $countBefore\n";

// Send the SMS
try {
    echo "Sending SMS to $phoneNumber with message: $message\n";
    $result = $smsService->sendSMS($phoneNumber, $message, $userId);
    echo "SMS sent successfully\n";
    echo "API response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Error sending SMS: " . $e->getMessage() . "\n";
}

// Count SMS history records after sending
$countAfter = $smsHistoryRepository->countByUserId($userId);
echo "SMS history records after sending: $countAfter\n";

// Check if a new record was added
if ($countAfter > $countBefore) {
    echo "SUCCESS: New SMS history record was added\n";
} else {
    echo "ERROR: No new SMS history record was added\n";
}

// Get the latest SMS history record
$latestRecords = $smsHistoryRepository->findByUserId($userId, 1);
if (!empty($latestRecords)) {
    $latest = $latestRecords[0];
    echo "Latest SMS history record:\n";
    echo "ID: " . $latest->getId() . "\n";
    echo "Phone: " . $latest->getPhoneNumber() . "\n";
    echo "Message: " . $latest->getMessage() . "\n";
    echo "Status: " . $latest->getStatus() . "\n";
    echo "Created: " . $latest->getCreatedAt()->format('Y-m-d H:i:s') . "\n";
} else {
    echo "No SMS history records found\n";
}
