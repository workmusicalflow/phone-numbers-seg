<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Get the entity manager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Create a logger
$logger = new \App\Services\SimpleLogger('auth-test');

// Create repositories
$userRepository = new \App\Repositories\Doctrine\UserRepository($entityManager);

// Create services
$emailService = new \App\Services\EmailService();
$authService = new \App\Services\AuthService($userRepository, $emailService);

// Test credentials
$adminUsername = 'admin';
$adminPassword = 'admin123';

// Test Admin login
echo "Testing Admin login...\n";
$adminUser = $userRepository->findByUsername($adminUsername);
if ($adminUser) {
    echo "Admin user found in database.\n";
    echo "User ID: " . $adminUser->getId() . "\n";
    echo "Username: " . $adminUser->getUsername() . "\n";
    echo "Is Admin: " . ($adminUser->isAdmin() ? 'Yes' : 'No') . "\n";

    // Test password verification
    echo "Testing password verification...\n";
    $passwordCorrect = $adminUser->verifyPassword($adminPassword);
    echo "Password verification result: " . ($passwordCorrect ? 'Success' : 'Failed') . "\n";

    // Test authentication
    echo "Testing authentication service...\n";
    $authResult = $authService->authenticate($adminUsername, $adminPassword);
    echo "Authentication result: " . ($authResult ? 'Success' : 'Failed') . "\n";
} else {
    echo "Admin user not found in database.\n";
}

echo "\n";

// Test AfricaQSHE login
echo "Testing AfricaQSHE login...\n";
$africaQSHEUser = $userRepository->findByUsername($africaQSHEUsername);
if ($africaQSHEUser) {
    echo "AfricaQSHE user found in database.\n";
    echo "User ID: " . $africaQSHEUser->getId() . "\n";
    echo "Username: " . $africaQSHEUser->getUsername() . "\n";
    echo "Is Admin: " . ($africaQSHEUser->isAdmin() ? 'Yes' : 'No') . "\n";

    // Test password verification
    echo "Testing password verification...\n";
    $passwordCorrect = $africaQSHEUser->verifyPassword($africaQSHEPassword);
    echo "Password verification result: " . ($passwordCorrect ? 'Success' : 'Failed') . "\n";

    // Test authentication
    echo "Testing authentication service...\n";
    $authResult = $authService->authenticate($africaQSHEUsername, $africaQSHEPassword);
    echo "Authentication result: " . ($authResult ? 'Success' : 'Failed') . "\n";
} else {
    echo "AfricaQSHE user not found in database.\n";
}

// If users don't exist, we need to create them
if (!$adminUser && !$africaQSHEUser) {
    echo "\nUsers not found. Would you like to create them? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (trim($line) == 'y') {
        echo "Creating Admin user...\n";
        $adminUser = new \App\Entities\User();
        $adminUser->setUsername($adminUsername);
        $adminUser->setPassword(password_hash($adminPassword, PASSWORD_DEFAULT));
        $adminUser->setIsAdmin(true);
        $adminUser->setSmsCredit(1000);
        $userRepository->save($adminUser);
        echo "Admin user created.\n";

        echo "Creating AfricaQSHE user...\n";
        $africaQSHEUser = new \App\Entities\User();
        $africaQSHEUser->setUsername($africaQSHEUsername);
        $africaQSHEUser->setPassword(password_hash($africaQSHEPassword, PASSWORD_DEFAULT));
        $africaQSHEUser->setIsAdmin(false);
        $africaQSHEUser->setSmsCredit(500);
        $userRepository->save($africaQSHEUser);
        echo "AfricaQSHE user created.\n";
    }
}
