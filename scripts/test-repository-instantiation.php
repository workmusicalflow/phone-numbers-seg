<?php

// Test repository instantiation to see the actual error

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/config/di.php';

use App\Repositories\Doctrine\WhatsApp\WhatsAppMessageHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;

try {
    // Get the DI container
    $container = require __DIR__ . '/../src/config/di.php';
    
    // Get the EntityManager
    $entityManager = $container->get(EntityManagerInterface::class);
    
    // Try to instantiate the repository
    echo "Attempting to instantiate WhatsAppMessageHistoryRepository...\n";
    $repository = new WhatsAppMessageHistoryRepository($entityManager, 'App\Entities\WhatsApp\WhatsAppMessageHistory');
    
    echo "✓ Repository instantiated successfully!\n";
    
    // Check if it implements the interface
    if ($repository instanceof \App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface) {
        echo "✓ Repository implements WhatsAppMessageHistoryRepositoryInterface\n";
    } else {
        echo "✗ Repository does NOT implement WhatsAppMessageHistoryRepositoryInterface\n";
    }
    
} catch (\Throwable $e) {
    echo "Error: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}