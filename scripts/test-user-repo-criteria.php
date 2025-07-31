<?php

// scripts/test-user-repo-criteria.php

require_once __DIR__ . '/../vendor/autoload.php';

use App\GraphQL\DIContainer;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Psr\Log\LoggerInterface;

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Starting User Repository Criteria Test...\n";

try {
    // Create DI container
    $container = new DIContainer();
    $logger = $container->get(LoggerInterface::class);
    $userRepository = $container->get(UserRepositoryInterface::class);

    echo "Container and UserRepository obtained.\n";

    // Test Case 1: Find all users (limit 5)
    echo "\nTest Case 1: Find All (Limit 5)\n";
    $criteria1 = [];
    $limit1 = 5;
    $offset1 = 0;
    $users1 = $userRepository->findByCriteria($criteria1, $limit1, $offset1);
    echo "Found " . count($users1) . " users.\n";
    foreach ($users1 as $user) {
        echo " - ID: " . $user->getId() . ", Username: " . $user->getUsername() . "\n";
    }

    // Test Case 2: Search for a specific term (e.g., 'admin', limit 5)
    echo "\nTest Case 2: Search for 'admin' (Limit 5)\n";
    $criteria2 = ['search' => 'admin'];
    $limit2 = 5;
    $offset2 = 0;
    $users2 = $userRepository->findByCriteria($criteria2, $limit2, $offset2);
    echo "Found " . count($users2) . " users matching 'admin'.\n";
    foreach ($users2 as $user) {
        echo " - ID: " . $user->getId() . ", Username: " . $user->getUsername() . "\n";
    }

    // Test Case 3: Pagination (Page 2, 2 items per page)
    echo "\nTest Case 3: Pagination (Page 2, Limit 2)\n";
    $criteria3 = [];
    $limit3 = 2;
    $offset3 = 2; // Skip first 2 (page 1)
    $users3 = $userRepository->findByCriteria($criteria3, $limit3, $offset3);
    echo "Found " . count($users3) . " users on page 2.\n";
    foreach ($users3 as $user) {
        echo " - ID: " . $user->getId() . ", Username: " . $user->getUsername() . "\n";
    }


    echo "\nTest completed successfully.\n";
} catch (\Throwable $e) {
    echo "\nError during test: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    if (isset($logger)) {
        $logger->error("Error in test-user-repo-criteria.php: " . $e->getMessage(), ['exception' => $e]);
    }
}
