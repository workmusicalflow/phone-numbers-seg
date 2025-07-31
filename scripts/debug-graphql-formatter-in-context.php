<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..'); // Specify the directory containing .env
$dotenv->load();

// Start the session before any output or session access
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create DI container
try {
    echo "Creating DIContainer...\n";
    $container = new \App\GraphQL\DIContainer();
    echo "DIContainer created successfully.\n";

    // Get the AuthResolver from the container
    echo "\nTrying to get AuthResolver from container...\n";
    $authResolver = $container->get(\App\GraphQL\Resolvers\AuthResolver::class);
    echo "AuthResolver retrieved successfully.\n";
    echo "Class: " . get_class($authResolver) . "\n";

    // Reflect on the AuthResolver to see its properties
    $reflection = new ReflectionClass($authResolver);
    $properties = $reflection->getProperties();

    echo "AuthResolver Properties:\n";
    foreach ($properties as $property) {
        $property->setAccessible(true);
        $value = $property->getValue($authResolver);
        echo "- " . $property->getName() . ": " . (is_object($value) ? get_class($value) : var_export($value, true)) . "\n";
    }

    // Get the formatter property specifically
    $formatterProperty = $reflection->getProperty('formatter');
    $formatterProperty->setAccessible(true);
    $formatter = $formatterProperty->getValue($authResolver);

    echo "\nFormatter from AuthResolver:\n";
    echo "Class: " . get_class($formatter) . "\n";

    // Reflect on the formatter to see its properties
    $formatterReflection = new ReflectionClass($formatter);
    $formatterProperties = $formatterReflection->getProperties();

    echo "Formatter Properties:\n";
    foreach ($formatterProperties as $property) {
        $property->setAccessible(true);
        $value = $property->getValue($formatter);
        echo "- " . $property->getName() . ": " . (is_object($value) ? get_class($value) : var_export($value, true)) . "\n";
    }

    // Check the constructor parameters of GraphQLFormatterService
    echo "\nGraphQLFormatterService Constructor Parameters:\n";
    $constructor = $formatterReflection->getConstructor();
    $parameters = $constructor->getParameters();
    foreach ($parameters as $parameter) {
        echo "- " . $parameter->getName() . ": " . ($parameter->getType() ? $parameter->getType()->getName() : 'unknown') . "\n";
    }

    // Try to get the GraphQLFormatterInterface directly
    echo "\nTrying to get GraphQLFormatterInterface directly...\n";
    $formatterInterface = $container->get(\App\GraphQL\Formatters\GraphQLFormatterInterface::class);
    echo "GraphQLFormatterInterface retrieved successfully.\n";
    echo "Class: " . get_class($formatterInterface) . "\n";

    // Check if it's the same instance as the one in AuthResolver
    echo "Is it the same instance as the one in AuthResolver? " . ($formatter === $formatterInterface ? "Yes" : "No") . "\n";

    // Check the di.php file for the GraphQLFormatterInterface definition
    echo "\nChecking di.php for GraphQLFormatterInterface definition...\n";
    $diPhpContent = file_get_contents(__DIR__ . '/../src/config/di.php');
    if (preg_match('/\\\\App\\\\GraphQL\\\\Formatters\\\\GraphQLFormatterInterface::class\s*=>\s*factory\(.*?\)/s', $diPhpContent, $matches)) {
        echo "Found definition in di.php:\n";
        echo substr($matches[0], 0, 500) . (strlen($matches[0]) > 500 ? "..." : "") . "\n";
    } else {
        echo "Definition not found in di.php.\n";
    }

    // Check if there are any other places where GraphQLFormatterService is instantiated
    echo "\nChecking for other instantiations of GraphQLFormatterService...\n";
    $srcDir = __DIR__ . '/../src';
    $pattern = '/new\s+(?:\\\\?App\\\\GraphQL\\\\Formatters\\\\)?GraphQLFormatterService\s*\(/i';

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir));
    $found = false;

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                $found = true;
                echo "File: " . $file->getPathname() . "\n";

                foreach ($matches[0] as $match) {
                    // Get the line number
                    $lineNumber = substr_count(substr($content, 0, $match[1]), "\n") + 1;

                    // Get the line content
                    $lines = explode("\n", $content);
                    $line = $lines[$lineNumber - 1];

                    echo "  Line " . $lineNumber . ": " . trim($line) . "\n";

                    // Try to get the constructor arguments
                    $start = $match[1] + strlen($match[0]);
                    $balance = 1;
                    $args = "";

                    for ($i = $start; $i < strlen($content); $i++) {
                        $char = $content[$i];
                        if ($char === '(') {
                            $balance++;
                        } elseif ($char === ')') {
                            $balance--;
                            if ($balance === 0) {
                                break;
                            }
                        }
                        $args .= $char;
                    }

                    echo "  Arguments: " . trim($args) . "\n";
                }
            }
        }
    }

    if (!$found) {
        echo "No other instantiations found.\n";
    }

    // Check if there are any places where GraphQLFormatterService is used with only 2 arguments
    echo "\nChecking for places where GraphQLFormatterService is used with only 2 arguments...\n";
    $pattern = '/new\s+(?:\\\\?App\\\\GraphQL\\\\Formatters\\\\)?GraphQLFormatterService\s*\([^)]{0,100},[^)]{0,100}\)/i';

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir));
    $found = false;

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                $found = true;
                echo "File: " . $file->getPathname() . "\n";

                foreach ($matches[0] as $match) {
                    // Get the line number
                    $lineNumber = substr_count(substr($content, 0, $match[1]), "\n") + 1;

                    // Get the line content
                    $lines = explode("\n", $content);
                    $line = $lines[$lineNumber - 1];

                    echo "  Line " . $lineNumber . ": " . trim($line) . "\n";
                }
            }
        }
    }

    if (!$found) {
        echo "No instances of GraphQLFormatterService with only 2 arguments found.\n";
    }

    // Check if there are any places where GraphQLFormatterService is used with 4 arguments
    echo "\nChecking for places where GraphQLFormatterService is used with 4 arguments...\n";
    $pattern = '/new\s+(?:\\\\?App\\\\GraphQL\\\\Formatters\\\\)?GraphQLFormatterService\s*\([^)]{0,100},[^)]{0,100},[^)]{0,100},[^)]{0,100}\)/i';

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir));
    $found = false;

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                $found = true;
                echo "File: " . $file->getPathname() . "\n";

                foreach ($matches[0] as $match) {
                    // Get the line number
                    $lineNumber = substr_count(substr($content, 0, $match[1]), "\n") + 1;

                    // Get the line content
                    $lines = explode("\n", $content);
                    $line = $lines[$lineNumber - 1];

                    echo "  Line " . $lineNumber . ": " . trim($line) . "\n";
                }
            }
        }
    }

    if (!$found) {
        echo "No instances of GraphQLFormatterService with 4 arguments found.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
