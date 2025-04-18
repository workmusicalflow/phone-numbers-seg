<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Get the entity manager directly from bootstrap file
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Create a UserRepository instance
$userRepository = new App\Repositories\Doctrine\UserRepository($entityManager);

// Try to find the test user
echo "Looking for user with username 'testuser'...\n";
$user = $userRepository->findByUsername('testuser');

if ($user) {
    echo "Found user: " . $user->getUsername() . " (ID: " . $user->getId() . ")\n";

    // Delete the user
    echo "Deleting user...\n";
    $result = $userRepository->deleteById($user->getId());

    if ($result) {
        echo "User deleted successfully!\n";
    } else {
        echo "Failed to delete user.\n";
    }
} else {
    echo "User 'testuser' not found in the database.\n";
}
