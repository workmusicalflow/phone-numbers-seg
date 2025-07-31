<?php

// Test DI container resolution

try {
    // Load autoloader and DI config
    require_once __DIR__ . '/../vendor/autoload.php';
    
    // Bootstrap Doctrine
    require_once __DIR__ . '/../src/bootstrap-doctrine.php';
    
    // Load DI container
    $container = require __DIR__ . '/../src/config/di.php';
    
    echo "Testing DI container resolution...\n";
    
    // Test resolving the repository interface
    $repository = $container->get('App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface');
    
    echo "✓ Repository interface resolved successfully\n";
    echo "✓ Actual class: " . get_class($repository) . "\n";
    
    // Test if the repository has the required methods
    $methods = ['find', 'findOneBy', 'findByWabaMessageId', 'save'];
    foreach ($methods as $method) {
        if (method_exists($repository, $method)) {
            echo "✓ Method '$method' exists\n";
        } else {
            echo "✗ Method '$method' NOT found\n";
        }
    }
    
    echo "\n✓ DI container resolution test passed\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}