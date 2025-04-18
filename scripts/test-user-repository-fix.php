<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Get the entity manager directly from bootstrap file
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Create a UserRepository instance
$userRepository = new App\Repositories\Doctrine\UserRepository($entityManager);

// Try to find a user by username
echo "Testing findByUsername method...\n";
$user = $userRepository->findByUsername('AfricaQSHE');

if ($user) {
    echo "Success! Found user: " . $user->getUsername() . " (ID: " . $user->getId() . ")\n";
} else {
    echo "User not found, but no error occurred.\n";
}

// Create a ContactRepository instance
$contactRepository = new App\Repositories\Doctrine\ContactRepository($entityManager);

// Try to count contacts
echo "\nTesting count method in ContactRepository...\n";
$count = $contactRepository->count();
echo "Contact count: $count\n";

// Create a ContactGroupRepository instance
$groupRepository = new App\Repositories\Doctrine\ContactGroupRepository($entityManager);

// Try to count groups
echo "\nTesting count method in ContactGroupRepository...\n";
$count = $groupRepository->count();
echo "Contact group count: $count\n";

echo "\nAll tests completed successfully!\n";
