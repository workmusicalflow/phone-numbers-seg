<?php

/**
 * This script tests the Doctrine relationships for a specific user
 * to verify if the associations between User and related entities are correctly established.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Get the EntityManager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Set the user ID to test
$userId = 2; // AfricaQSHE

echo "Testing Doctrine relationships for User ID: $userId\n";
echo "=================================================\n\n";

try {
    // Get the User repository
    $userRepository = $entityManager->getRepository(\App\Entities\User::class);

    // Find the user
    $user = $userRepository->find($userId);

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

    echo "Contacts found in database (direct query): " . count($contacts) . "\n";

    // Check if the User entity has a getContacts method
    if (method_exists($user, 'getContacts')) {
        $userContacts = $user->getContacts();
        echo "Contacts found via User->getContacts(): " . (is_countable($userContacts) ? count($userContacts) : 'N/A - method exists but returns non-countable') . "\n";
    } else {
        echo "NOTE: User entity does not have a getContacts() method. This suggests the relationship might not be defined in the entity.\n";

        // Alternative: Check if there's a relationship defined in the Contact entity
        echo "Checking if Contact entity has a getUser() method...\n";
        if (count($contacts) > 0 && method_exists($contacts[0], 'getUser')) {
            $firstContact = $contacts[0];
            $contactUser = $firstContact->getUser();
            echo "First contact's user: " . ($contactUser ? $contactUser->getUsername() : 'NULL') . "\n";
        } else if (count($contacts) > 0) {
            echo "Contact entity does not have a getUser() method. Relationship might be missing.\n";
        }
    }

    // Test ContactGroup relationships
    echo "\nTesting ContactGroup relationships...\n";
    $groupRepository = $entityManager->getRepository(\App\Entities\ContactGroup::class);
    $groups = $groupRepository->findBy(['userId' => $userId]);

    echo "Contact groups found in database (direct query): " . count($groups) . "\n";

    // Check if the User entity has a getContactGroups method
    if (method_exists($user, 'getContactGroups')) {
        $userGroups = $user->getContactGroups();
        echo "Contact groups found via User->getContactGroups(): " . (is_countable($userGroups) ? count($userGroups) : 'N/A - method exists but returns non-countable') . "\n";
    } else {
        echo "NOTE: User entity does not have a getContactGroups() method. This suggests the relationship might not be defined in the entity.\n";

        // Alternative: Check if there's a relationship defined in the ContactGroup entity
        if (count($groups) > 0 && method_exists($groups[0], 'getUser')) {
            $firstGroup = $groups[0];
            $groupUser = $firstGroup->getUser();
            echo "First group's user: " . ($groupUser ? $groupUser->getUsername() : 'NULL') . "\n";
        } else if (count($groups) > 0) {
            echo "ContactGroup entity does not have a getUser() method. Relationship might be missing.\n";
        }
    }

    // Test SMSHistory relationships
    echo "\nTesting SMSHistory relationships...\n";
    $historyRepository = $entityManager->getRepository(\App\Entities\SMSHistory::class);
    $history = $historyRepository->findBy(['userId' => $userId]);

    echo "SMS history entries found in database (direct query): " . count($history) . "\n";

    // Check if the User entity has a getSmsHistory method
    if (method_exists($user, 'getSmsHistory')) {
        $userHistory = $user->getSmsHistory();
        echo "SMS history entries found via User->getSmsHistory(): " . (is_countable($userHistory) ? count($userHistory) : 'N/A - method exists but returns non-countable') . "\n";
    } else {
        echo "NOTE: User entity does not have a getSmsHistory() method. This suggests the relationship might not be defined in the entity.\n";

        // Alternative: Check if there's a relationship defined in the SMSHistory entity
        if (count($history) > 0 && method_exists($history[0], 'getUser')) {
            $firstHistory = $history[0];
            $historyUser = $firstHistory->getUser();
            echo "First history entry's user: " . ($historyUser ? $historyUser->getUsername() : 'NULL') . "\n";
        } else if (count($history) > 0) {
            echo "SMSHistory entity does not have a getUser() method. Relationship might be missing.\n";
        }
    }

    // Test for Admin user (ID 1) for comparison
    echo "\n\nTesting Doctrine relationships for Admin User (ID: 1) for comparison\n";
    echo "=================================================================\n\n";

    $adminUser = $userRepository->find(1);

    if (!$adminUser) {
        echo "ERROR: Admin User with ID 1 not found!\n";
    } else {
        echo "Admin User found: " . $adminUser->getUsername() . " (ID: " . $adminUser->getId() . ")\n";

        // Test Contact relationships for Admin
        $adminContacts = $contactRepository->findBy(['userId' => 1]);
        echo "Admin contacts found in database (direct query): " . count($adminContacts) . "\n";

        // Test ContactGroup relationships for Admin
        $adminGroups = $groupRepository->findBy(['userId' => 1]);
        echo "Admin contact groups found in database (direct query): " . count($adminGroups) . "\n";

        // Test SMSHistory relationships for Admin
        $adminHistory = $historyRepository->findBy(['userId' => 1]);
        echo "Admin SMS history entries found in database (direct query): " . count($adminHistory) . "\n";
    }

    echo "\n\nConclusion\n";
    echo "==========\n";
    echo "If the direct database queries show data exists but the relationship methods return empty results,\n";
    echo "this confirms the hypothesis that the Doctrine relationships were not properly established during migration.\n";
    echo "The solution would be to fix the migration scripts to properly set up the object relationships,\n";
    echo "not just the database foreign keys.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
