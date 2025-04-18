<?php

// Check the main di.php file
$mainDiPhpPath = __DIR__ . '/../src/config/di.php';
echo "Checking main di.php file at: $mainDiPhpPath\n";

if (file_exists($mainDiPhpPath)) {
    echo "File exists.\n";
    $content = file_get_contents($mainDiPhpPath);

    // Check for GraphQLFormatterService instantiation
    if (preg_match('/new\s+(?:\\\\?App\\\\GraphQL\\\\Formatters\\\\)?GraphQLFormatterService\s*\((.*?)\);/s', $content, $matches)) {
        echo "Found GraphQLFormatterService instantiation:\n";
        echo trim($matches[0]) . "\n";

        // Count the number of arguments
        $args = $matches[1];
        $commaCount = substr_count($args, ',');
        $argCount = $commaCount + 1;

        echo "Number of arguments: $argCount\n";
        echo "Arguments:\n" . trim($args) . "\n";

        // Extract the individual arguments
        $arguments = explode(',', $args);
        echo "Individual arguments:\n";
        foreach ($arguments as $index => $argument) {
            echo "Argument " . ($index + 1) . ": " . trim($argument) . "\n";
        }
    } else {
        echo "GraphQLFormatterService instantiation not found.\n";
    }
} else {
    echo "File does not exist.\n";
}

// Check for cached versions of di.php
echo "\nChecking for cached versions of di.php...\n";
$cacheDir = __DIR__ . '/../var/cache';

if (is_dir($cacheDir)) {
    echo "Cache directory exists at: $cacheDir\n";
    $cachedFiles = glob("$cacheDir/*.php");

    if (!empty($cachedFiles)) {
        echo "Found " . count($cachedFiles) . " cached PHP files:\n";
        foreach ($cachedFiles as $file) {
            echo "- $file\n";

            // Check if the file contains GraphQLFormatterService
            $content = file_get_contents($file);
            if (strpos($content, 'GraphQLFormatterService') !== false) {
                echo "  File contains GraphQLFormatterService.\n";

                // Check for GraphQLFormatterService instantiation
                if (preg_match('/new\s+(?:\\\\?App\\\\GraphQL\\\\Formatters\\\\)?GraphQLFormatterService\s*\(([^)]+)\)/s', $content, $matches)) {
                    echo "  Found GraphQLFormatterService instantiation:\n";
                    echo "  " . trim($matches[0]) . "\n";

                    // Count the number of arguments
                    $args = $matches[1];
                    $commaCount = substr_count($args, ',');
                    $argCount = $commaCount + 1;

                    echo "  Number of arguments: $argCount\n";
                    echo "  Arguments:\n  " . trim($args) . "\n";
                }
            }
        }
    } else {
        echo "No cached PHP files found.\n";
    }
} else {
    echo "Cache directory does not exist.\n";
}

// Check for any other di.php files in the project
echo "\nChecking for any other di.php files in the project...\n";
$projectDir = __DIR__ . '/..';

$command = "find $projectDir -name 'di.php' -not -path '$mainDiPhpPath'";
exec($command, $output);

if (!empty($output)) {
    echo "Found " . count($output) . " other di.php files:\n";
    foreach ($output as $file) {
        echo "- $file\n";

        // Check if the file contains GraphQLFormatterService
        $content = file_get_contents($file);
        if (strpos($content, 'GraphQLFormatterService') !== false) {
            echo "  File contains GraphQLFormatterService.\n";

            // Check for GraphQLFormatterService instantiation
            if (preg_match('/new\s+(?:\\\\?App\\\\GraphQL\\\\Formatters\\\\)?GraphQLFormatterService\s*\(([^)]+)\)/s', $content, $matches)) {
                echo "  Found GraphQLFormatterService instantiation:\n";
                echo "  " . trim($matches[0]) . "\n";

                // Count the number of arguments
                $args = $matches[1];
                $commaCount = substr_count($args, ',');
                $argCount = $commaCount + 1;

                echo "  Number of arguments: $argCount\n";
                echo "  Arguments:\n  " . trim($args) . "\n";
            }
        }
    }
} else {
    echo "No other di.php files found.\n";
}

// Check if there are any other files that might be instantiating GraphQLFormatterService
echo "\nChecking for any other files that might be instantiating GraphQLFormatterService...\n";

$command = "grep -r 'new.*GraphQLFormatterService' $projectDir --include='*.php' | grep -v '$mainDiPhpPath'";
exec($command, $output);

if (!empty($output)) {
    echo "Found " . count($output) . " other files instantiating GraphQLFormatterService:\n";
    foreach ($output as $line) {
        echo "- $line\n";
    }
} else {
    echo "No other files instantiating GraphQLFormatterService found.\n";
}

// Check if there are any files that might be using GraphQLFormatterService with 2 arguments
echo "\nChecking for any files that might be using GraphQLFormatterService with 2 arguments...\n";

$command = "grep -r 'GraphQLFormatterService.*(.*)' $projectDir --include='*.php' | grep -v '$mainDiPhpPath'";
exec($command, $output);

if (!empty($output)) {
    echo "Found " . count($output) . " other files using GraphQLFormatterService:\n";
    foreach ($output as $line) {
        echo "- $line\n";
    }
} else {
    echo "No other files using GraphQLFormatterService found.\n";
}

// Check the line 371 of di.php
echo "\nChecking line 371 of di.php...\n";
$lines = file($mainDiPhpPath);
if (isset($lines[370])) { // 0-indexed, so line 371 is at index 370
    echo "Line 371: " . $lines[370] . "\n";

    // Show context (10 lines before and after)
    echo "Context:\n";
    $start = max(0, 370 - 10);
    $end = min(count($lines), 370 + 10);
    for ($i = $start; $i < $end; $i++) {
        echo ($i + 1) . ": " . $lines[$i];
    }
} else {
    echo "Line 371 does not exist in di.php (file has " . count($lines) . " lines).\n";
}
