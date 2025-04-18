<?php

/**
 * This script checks for issues with the GraphQLFormatterService
 * and its dependencies in the DI container.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use Psr\Log\LoggerInterface;
use App\GraphQL\Formatters\GraphQLFormatterInterface;
use App\GraphQL\Formatters\GraphQLFormatterService;
use App\Repositories\Interfaces\CustomSegmentRepositoryInterface;
use App\Services\SenderNameService;
use App\Services\OrangeAPIConfigService;

// Create a simple error handler to catch any issues
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    echo "ERROR: $errstr in $errfile on line $errline\n";
    return true;
});

try {
    echo "Building DI container...\n";

    // Build the container
    $containerBuilder = new ContainerBuilder();
    $definitions = require __DIR__ . '/../src/config/di.php';
    $containerBuilder->addDefinitions($definitions);
    $container = $containerBuilder->build();

    echo "Container built successfully.\n";

    // Check if all required dependencies for GraphQLFormatterService exist
    echo "\nChecking dependencies for GraphQLFormatterService:\n";

    // Check CustomSegmentRepositoryInterface
    echo "- CustomSegmentRepositoryInterface: ";
    try {
        $customSegmentRepo = $container->get(CustomSegmentRepositoryInterface::class);
        echo "OK\n";
    } catch (Exception $e) {
        echo "FAILED - " . $e->getMessage() . "\n";
    }

    // Check LoggerInterface
    echo "- LoggerInterface: ";
    try {
        $logger = $container->get(LoggerInterface::class);
        echo "OK\n";
    } catch (Exception $e) {
        echo "FAILED - " . $e->getMessage() . "\n";
    }

    // Check SenderNameService
    echo "- SenderNameService: ";
    try {
        $senderNameService = $container->get(SenderNameService::class);
        echo "OK\n";
    } catch (Exception $e) {
        echo "FAILED - " . $e->getMessage() . "\n";
    }

    // Check OrangeAPIConfigService
    echo "- OrangeAPIConfigService: ";
    try {
        $orangeAPIConfigService = $container->get(OrangeAPIConfigService::class);
        echo "OK\n";
    } catch (Exception $e) {
        echo "FAILED - " . $e->getMessage() . "\n";
    }

    // Try to get the GraphQLFormatterService
    echo "\nTrying to get GraphQLFormatterInterface from container: ";
    try {
        $formatter = $container->get(GraphQLFormatterInterface::class);
        echo "SUCCESS\n";
        echo "Class: " . get_class($formatter) . "\n";

        // Test a method to ensure it works
        if ($formatter instanceof GraphQLFormatterService) {
            echo "\nTesting formatter methods:\n";

            // Get a real User object from the repository instead of using a mock
            echo "- Getting a real User object: ";
            $mockUser = null;
            try {
                $userRepo = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);
                $users = $userRepo->findAll(1);
                if (!empty($users)) {
                    $mockUser = $users[0];
                    echo "OK\n";

                    // Try to format the user
                    echo "- formatUser: ";
                    try {
                        $formattedUser = $formatter->formatUser($mockUser);
                        echo "OK - " . json_encode($formattedUser) . "\n";
                    } catch (Exception $e) {
                        echo "FAILED - " . $e->getMessage() . "\n";
                    }
                } else {
                    echo "No users found in database\n";
                    echo "Skipping formatUser test due to no users found\n";
                }
            } catch (Exception $e) {
                echo "FAILED - " . $e->getMessage() . "\n";
                echo "Skipping formatUser test due to error\n";
            }
        }
    } catch (Exception $e) {
        echo "FAILED - " . $e->getMessage() . "\n";

        // Try to manually create the service to see if that works
        echo "\nTrying to manually create GraphQLFormatterService: ";
        try {
            $customSegmentRepo = $container->get(CustomSegmentRepositoryInterface::class);
            $logger = $container->get(LoggerInterface::class);
            $senderNameService = $container->get(SenderNameService::class);
            $orangeAPIConfigService = $container->get(OrangeAPIConfigService::class);

            $formatter = new GraphQLFormatterService(
                $customSegmentRepo,
                $logger,
                $senderNameService,
                $orangeAPIConfigService
            );

            echo "SUCCESS\n";
        } catch (Exception $e2) {
            echo "FAILED - " . $e2->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\nDebug complete.\n";
