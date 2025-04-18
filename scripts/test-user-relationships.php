<?php

/**
 * This script tests the relationships between User and related entities
 * using our custom repository factory.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Get the EntityManager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Set the user ID to test
$userId = 2; // AfricaQSHE

echo "Testing User relationships for User ID: $userId\n";
echo "=================================================\n\n";

try {
    // Get the User repository
    $userRepository = $entityManager->getRepository(\App\Entities\User::class);

    // Find the user
    $user = $userRepository->findById($userId);

    if (!$user) {
        echo "ERROR: User with ID $userId not found!\n";
        exit(1);
    }

    echo "User found: " . $user->getUsername() . " (ID: " . $user->getId() . ")\n";
    echo "Email: " . ($user->getEmail() ?: 'Not set') . "\n";
    echo "SMS Credit: " . $user->getSmsCredit() . "\n";
    echo "Is Admin: " . ($user->isAdmin() ? 'Yes' : 'No') . "\n";
    echo "Created At: " . $user->getCreatedAt()->format('Y-m-d H:i:s') . "\n\n";

    // Test Contact relationships
    echo "Testing Contact relationships...\n";
    $contactRepository = $entityManager->getRepository(\App\Entities\Contact::class);
    $contacts = $contactRepository->findBy(['userId' => $userId]);

    echo "Contacts found for user (direct query): " . count($contacts) . "\n";

    if (count($contacts) > 0) {
        $firstContact = $contacts[0];
        echo "First contact: " . $firstContact->getName() . " (" . $firstContact->getPhoneNumber() . ")\n";
    }

    // Test ContactGroup relationships
    echo "\nTesting ContactGroup relationships...\n";
    $groupRepository = $entityManager->getRepository(\App\Entities\ContactGroup::class);
    $groups = $groupRepository->findBy(['userId' => $userId]);

    echo "Contact groups found for user (direct query): " . count($groups) . "\n";

    if (count($groups) > 0) {
        $firstGroup = $groups[0];
        echo "First group: " . $firstGroup->getName() . "\n";
    }

    // Test SMSHistory relationships
    echo "\nTesting SMSHistory relationships...\n";
    $historyRepository = $entityManager->getRepository(\App\Entities\SMSHistory::class);
    $history = $historyRepository->findBy(['userId' => $userId]);

    echo "SMS history entries found for user (direct query): " . count($history) . "\n";

    if (count($history) > 0) {
        $firstHistory = $history[0];
        echo "First SMS history: To " . $firstHistory->getPhoneNumber() . " - Status: " . $firstHistory->getStatus() . "\n";
    }

    // Test for Admin user (ID 1) for comparison
    echo "\n\nTesting relationships for Admin User (ID: 1) for comparison\n";
    echo "=================================================================\n\n";

    $adminUser = $userRepository->findById(1);

    if (!$adminUser) {
        echo "ERROR: Admin User with ID 1 not found!\n";
    } else {
        echo "Admin User found: " . $adminUser->getUsername() . " (ID: " . $adminUser->getId() . ")\n";

        // Test Contact relationships for Admin
        $adminContacts = $contactRepository->findBy(['userId' => 1]);
        echo "Admin contacts found (direct query): " . count($adminContacts) . "\n";

        // Test ContactGroup relationships for Admin
        $adminGroups = $groupRepository->findBy(['userId' => 1]);
        echo "Admin contact groups found (direct query): " . count($adminGroups) . "\n";

        // Test SMSHistory relationships for Admin
        $adminHistory = $historyRepository->findBy(['userId' => 1]);
        echo "Admin SMS history entries found (direct query): " . count($adminHistory) . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
