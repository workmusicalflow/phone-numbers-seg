<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Get the entity manager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Get the user repository
$userRepository = $entityManager->getRepository(\App\Entities\User::class);

// Test user credentials
$username = 'AfricaQSHE';
$password = 'Qualitas@2024';

// Find the user
$user = $userRepository->findByUsername($username);

if (!$user) {
    echo "User not found: $username\n";
    exit(1);
}

echo "User found: " . $user->getUsername() . " (ID: " . $user->getId() . ")\n";
echo "Email: " . $user->getEmail() . "\n";
echo "Is Admin: " . ($user->isAdmin() ? 'Yes' : 'No') . "\n";
echo "SMS Credit: " . $user->getSmsCredit() . "\n";

// Verify password
if ($user->verifyPassword($password)) {
    echo "Password is correct!\n";
} else {
    echo "Password is incorrect!\n";
}

// Test session creation
$authService = new \App\Services\AuthService($userRepository, new \App\Services\EmailService());
$authenticatedUser = $authService->authenticate($username, $password);

if ($authenticatedUser) {
    echo "Authentication successful!\n";
    echo "Session created with user ID: " . $_SESSION['user_id'] . "\n";
} else {
    echo "Authentication failed!\n";
}
