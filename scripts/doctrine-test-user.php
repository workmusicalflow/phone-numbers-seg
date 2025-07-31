<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\User;
use App\Repositories\Doctrine\UserRepository;
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
$userRepository = new UserRepository($entityManager);

// Create a new user
$user = new User();
$user->setUsername('testuser');
$user->setPassword(password_hash('password123', PASSWORD_DEFAULT)); // Hash the password
$user->setEmail('test@example.com');
$user->setSmsCredit(100);
$user->setSmsLimit(1000);
$user->setIsAdmin(false);

// Save the user
try {
    echo "Creating a new user...\n";
    $userRepository->save($user);
    echo "User created with ID: " . $user->getId() . "\n";
} catch (\Exception $e) {
    echo "Error creating user: " . $e->getMessage() . "\n";

    // Check if the error is related to missing tables
    if (strpos($e->getMessage(), 'no such table') !== false) {
        echo "It seems the database tables don't exist yet. Let's create them.\n";

        // Get the schema tool
        $schemaManager = $entityManager->getConnection()->createSchemaManager();

        // Create the schema for User entity only
        echo "Creating database schema for User entity...\n";
        $userMetadata = $entityManager->getClassMetadata(User::class);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);

        try {
            // Try to create just the users table
            $schemaTool->createSchema([$userMetadata]);
        } catch (\Exception $e) {
            // If there's an error, try to update the schema instead
            echo "Error creating schema: " . $e->getMessage() . "\n";
            echo "Trying to update schema instead...\n";
            $schemaTool->updateSchema([$userMetadata]);
        }

        echo "Schema created successfully. Please run the script again.\n";
        exit(1);
    }

    exit(1);
}

// Find all users
echo "\nFinding all users...\n";
$users = $userRepository->findAll();
echo "Found " . count($users) . " users:\n";
foreach ($users as $u) {
    echo "- ID: " . $u->getId() . ", Username: " . $u->getUsername() . ", Email: " . $u->getEmail() . "\n";
}

// Find user by username
echo "\nFinding user by username 'testuser'...\n";
$foundUser = $userRepository->findByUsername('testuser');
if ($foundUser) {
    echo "Found user: ID: " . $foundUser->getId() . ", Username: " . $foundUser->getUsername() . ", Email: " . $foundUser->getEmail() . "\n";
} else {
    echo "User not found.\n";
}

// Find user by email
echo "\nFinding user by email 'test@example.com'...\n";
$foundUser = $userRepository->findByEmail('test@example.com');
if ($foundUser) {
    echo "Found user: ID: " . $foundUser->getId() . ", Username: " . $foundUser->getUsername() . ", Email: " . $foundUser->getEmail() . "\n";
} else {
    echo "User not found.\n";
}

// Test password verification
echo "\nTesting password verification...\n";
$correctPassword = 'password123';
$wrongPassword = 'wrongpassword';
$foundUser = $userRepository->findByUsername('testuser');
if ($foundUser) {
    echo "Correct password verification: " . ($foundUser->verifyPassword($correctPassword) ? "Success" : "Failed") . "\n";
    echo "Wrong password verification: " . ($foundUser->verifyPassword($wrongPassword) ? "Failed (should not verify)" : "Success (correctly rejected)") . "\n";
} else {
    echo "User not found for password verification.\n";
}

// Update SMS credits
echo "\nUpdating SMS credits...\n";
$newCredits = 200;
$userId = $foundUser->getId();
$result = $userRepository->updateSmsCredits($userId, $newCredits);
echo "Update result: " . ($result ? "Success" : "Failed") . "\n";

// Verify SMS credits update
$updatedUser = $userRepository->findById($userId);
echo "Updated SMS credits: " . $updatedUser->getSmsCredit() . " (expected: $newCredits)\n";

// Update SMS limit
echo "\nUpdating SMS limit...\n";
$newLimit = 2000;
$result = $userRepository->updateSmsLimit($userId, $newLimit);
echo "Update result: " . ($result ? "Success" : "Failed") . "\n";

// Verify SMS limit update
$updatedUser = $userRepository->findById($userId);
echo "Updated SMS limit: " . $updatedUser->getSmsLimit() . " (expected: $newLimit)\n";

// Test adding credits
echo "\nTesting adding credits...\n";
$creditsToAdd = 50;
$updatedUser->addCredits($creditsToAdd);
$userRepository->save($updatedUser);
$refreshedUser = $userRepository->findById($userId);
echo "Credits after adding $creditsToAdd: " . $refreshedUser->getSmsCredit() . " (expected: " . ($newCredits + $creditsToAdd) . ")\n";

// Test deducting credits
echo "\nTesting deducting credits...\n";
$creditsToDeduct = 30;
$refreshedUser->deductCredits($creditsToDeduct);
$userRepository->save($refreshedUser);
$finalUser = $userRepository->findById($userId);
echo "Credits after deducting $creditsToDeduct: " . $finalUser->getSmsCredit() . " (expected: " . ($newCredits + $creditsToAdd - $creditsToDeduct) . ")\n";

// Test has enough credits
echo "\nTesting has enough credits...\n";
$amountToCheck = 100;
echo "Has enough credits for $amountToCheck: " . ($finalUser->hasEnoughCredits($amountToCheck) ? "Yes" : "No") . "\n";
$tooMuch = 1000;
echo "Has enough credits for $tooMuch: " . ($finalUser->hasEnoughCredits($tooMuch) ? "Yes" : "No") . "\n";

echo "\nDoctrine ORM User test completed successfully!\n";
