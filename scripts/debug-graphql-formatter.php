<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..'); // Specify the directory containing .env
$dotenv->load();

// Create DI container
$container = new \App\GraphQL\DIContainer();

// Get the GraphQLFormatterService from the container
try {
    $formatter = $container->get(\App\GraphQL\Formatters\GraphQLFormatterInterface::class);
    echo "Successfully retrieved GraphQLFormatterInterface from container.\n";
    echo "Class: " . get_class($formatter) . "\n";

    // Check if it's an instance of GraphQLFormatterService
    if ($formatter instanceof \App\GraphQL\Formatters\GraphQLFormatterService) {
        echo "It's an instance of GraphQLFormatterService.\n";

        // Reflect on the object to see its properties
        $reflection = new ReflectionClass($formatter);
        $properties = $reflection->getProperties();

        echo "Properties:\n";
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($formatter);
            echo "- " . $property->getName() . ": " . (is_object($value) ? get_class($value) : var_export($value, true)) . "\n";
        }
    }

    // Get the constructor parameters
    $constructor = (new ReflectionClass(\App\GraphQL\Formatters\GraphQLFormatterService::class))->getConstructor();
    $parameters = $constructor->getParameters();

    echo "\nConstructor parameters:\n";
    foreach ($parameters as $parameter) {
        echo "- " . $parameter->getName() . ": " . ($parameter->getType() ? $parameter->getType()->getName() : 'unknown') . "\n";
    }

    // Check the di.php configuration
    echo "\nDI configuration for GraphQLFormatterInterface:\n";
    $definitions = require __DIR__ . '/../src/config/di.php';
    if (isset($definitions[\App\GraphQL\Formatters\GraphQLFormatterInterface::class])) {
        echo "Found in di.php\n";
    } else {
        echo "Not found in di.php\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

// Try to instantiate GraphQLFormatterService directly
try {
    echo "\nTrying to instantiate GraphQLFormatterService directly...\n";

    // Get the required dependencies
    $customSegmentRepository = $container->get(\App\Repositories\Interfaces\CustomSegmentRepositoryInterface::class);
    $logger = $container->get(\Psr\Log\LoggerInterface::class);
    $senderNameService = $container->get(\App\Services\SenderNameService::class);
    $orangeAPIConfigService = $container->get(\App\Services\OrangeAPIConfigService::class);

    // Create the formatter
    $formatter = new \App\GraphQL\Formatters\GraphQLFormatterService(
        $customSegmentRepository,
        $logger,
        $senderNameService,
        $orangeAPIConfigService
    );

    echo "Successfully created GraphQLFormatterService directly.\n";
} catch (Exception $e) {
    echo "Error creating GraphQLFormatterService directly: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

// Check if there are any other classes that might be instantiating GraphQLFormatterService
echo "\nChecking for potential instantiations of GraphQLFormatterService...\n";

// Define the directory to search
$srcDir = __DIR__ . '/../src';

// Function to search for a pattern in files
function searchInFiles($dir, $pattern)
{
    $results = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            if (preg_match($pattern, $content)) {
                $results[] = [
                    'file' => $file->getPathname(),
                    'matches' => []
                ];

                // Extract the matching lines
                $lines = explode("\n", $content);
                foreach ($lines as $lineNumber => $line) {
                    if (preg_match($pattern, $line)) {
                        $results[count($results) - 1]['matches'][] = [
                            'line' => $lineNumber + 1,
                            'content' => trim($line)
                        ];
                    }
                }
            }
        }
    }

    return $results;
}

// Search for direct instantiations of GraphQLFormatterService
$pattern = '/new\s+(?:\\\\?App\\\\GraphQL\\\\Formatters\\\\)?GraphQLFormatterService\s*\(/i';
$results = searchInFiles($srcDir, $pattern);

if (empty($results)) {
    echo "No direct instantiations of GraphQLFormatterService found.\n";
} else {
    echo "Found potential direct instantiations of GraphQLFormatterService:\n";
    foreach ($results as $result) {
        echo "File: " . $result['file'] . "\n";
        foreach ($result['matches'] as $match) {
            echo "  Line " . $match['line'] . ": " . $match['content'] . "\n";
        }
    }
}

// Search for references to GraphQLFormatterService
$pattern = '/GraphQLFormatterService/i';
$results = searchInFiles($srcDir, $pattern);

if (empty($results)) {
    echo "No references to GraphQLFormatterService found.\n";
} else {
    echo "\nFound references to GraphQLFormatterService:\n";
    foreach ($results as $result) {
        echo "File: " . $result['file'] . "\n";
        foreach ($result['matches'] as $match) {
            echo "  Line " . $match['line'] . ": " . $match['content'] . "\n";
        }
    }
}
