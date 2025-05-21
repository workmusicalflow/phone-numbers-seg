<?php

// Define application root and include necessary files
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/vendor/autoload.php';

// Create DI container
$container = new \App\GraphQL\DIContainer();

// Get the WhatsAppController
$whatsAppController = $container->get(\App\Controllers\WhatsAppController::class);

// Mock user for testing
$user = new \App\Entities\User();
$user->setUsername('testuser');
$user->setPassword(password_hash('password', PASSWORD_DEFAULT));
$user->setEmail('test@example.com');
$user->setIsAdmin(false);
$user->setSmsCredit(100);
$user->setSmsLimit(1000);
$user->generateApiKey();

// Manually set ID using reflection since it's usually auto-generated
$reflection = new ReflectionClass($user);
$idProperty = $reflection->getProperty('id');
$idProperty->setAccessible(true);
$idProperty->setValue($user, 1);

// Set parameters for the API call
$params = [
    'force_meta' => true,
    'force_refresh' => true,
    'use_cache' => false,
    'debug' => true
];

// Call the getApprovedTemplates method
try {
    $result = $whatsAppController->getApprovedTemplates($user, $params);
    echo "Success! Retrieved " . count($result['templates']) . " templates\n";
    echo "Source: " . $result['meta']['source'] . "\n";
    echo "Used fallback: " . ($result['meta']['usedFallback'] ? 'Yes' : 'No') . "\n";
    
    // Print the first template
    if (!empty($result['templates'])) {
        $template = $result['templates'][0];
        echo "\nFirst template:\n";
        echo "ID: " . $template['id'] . "\n";
        echo "Name: " . $template['name'] . "\n";
        echo "Category: " . $template['category'] . "\n";
        echo "Language: " . $template['language'] . "\n";
    }
    
    // Output full response in JSON
    echo "\nFull response:\n";
    echo json_encode($result, JSON_PRETTY_PRINT);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}