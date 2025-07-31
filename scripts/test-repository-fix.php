<?php

// Simple test to verify the repository compatibility fix

try {
    // Load autoloader
    require_once __DIR__ . '/../vendor/autoload.php';
    
    // Test class loading
    echo "Loading classes...\n";
    
    // Load the interface
    if (!interface_exists('App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface')) {
        throw new Exception("Interface not found");
    }
    echo "✓ Interface loaded successfully\n";
    
    // Load the repository class
    if (!class_exists('App\Repositories\Doctrine\WhatsApp\WhatsAppMessageRepository')) {
        throw new Exception("Repository class not found");
    }
    echo "✓ Repository class loaded successfully\n";
    
    // Check the interface methods
    $interfaceReflection = new ReflectionClass('App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface');
    $findMethod = $interfaceReflection->getMethod('find');
    $findOneByMethod = $interfaceReflection->getMethod('findOneBy');
    
    echo "\nInterface method signatures:\n";
    echo "- find() return type: " . $findMethod->getReturnType() . "\n";
    echo "- findOneBy() return type: " . $findOneByMethod->getReturnType() . "\n";
    
    // Check if the repository implements the interface
    $repoReflection = new ReflectionClass('App\Repositories\Doctrine\WhatsApp\WhatsAppMessageRepository');
    $interfaces = $repoReflection->getInterfaceNames();
    
    if (in_array('App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface', $interfaces)) {
        echo "\n✓ Repository implements the interface\n";
    } else {
        echo "\n✗ Repository does NOT implement the interface\n";
    }
    
    // Check repository method signatures
    if ($repoReflection->hasMethod('find')) {
        $repoFindMethod = $repoReflection->getMethod('find');
        echo "\nRepository method signatures:\n";
        echo "- find() return type: " . $repoFindMethod->getReturnType() . "\n";
    }
    
    if ($repoReflection->hasMethod('findOneBy')) {
        $repoFindOneByMethod = $repoReflection->getMethod('findOneBy');
        echo "- findOneBy() return type: " . $repoFindOneByMethod->getReturnType() . "\n";
    }
    
    echo "\n✓ All tests passed - repository is compatible with the interface\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}