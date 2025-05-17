<?php

// Simple test to check the repository compatibility issue

require_once __DIR__ . '/../vendor/autoload.php';

use App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface;
use App\Repositories\Doctrine\WhatsApp\WhatsAppMessageHistoryRepository;
use App\Entities\WhatsApp\WhatsAppMessageHistory;
use Doctrine\DBAL\LockMode;

// Test the interface declaration
echo "Testing interface compatibility...\n";

// Create reflection classes
$interfaceReflection = new ReflectionClass(WhatsAppMessageHistoryRepositoryInterface::class);
$findMethod = $interfaceReflection->getMethod('find');

echo "Interface find method:\n";
echo "Parameters:\n";
foreach ($findMethod->getParameters() as $param) {
    echo "  - {$param->getName()}: ";
    if ($param->hasType()) {
        $type = $param->getType();
        echo $type;
        if ($type->allowsNull()) {
            echo " (nullable)";
        }
    } else {
        echo "mixed";
    }
    if ($param->isDefaultValueAvailable()) {
        echo " = " . var_export($param->getDefaultValue(), true);
    }
    echo "\n";
}

echo "Return type: ";
if ($findMethod->hasReturnType()) {
    $returnType = $findMethod->getReturnType();
    echo $returnType;
    if ($returnType->allowsNull()) {
        echo " (nullable)";
    }
} else {
    echo "mixed";
}
echo "\n\n";

// Check Doctrine's EntityRepository
if (class_exists('Doctrine\ORM\EntityRepository')) {
    $entityRepoReflection = new ReflectionClass('Doctrine\ORM\EntityRepository');
    if ($entityRepoReflection->hasMethod('find')) {
        $parentFindMethod = $entityRepoReflection->getMethod('find');
        
        echo "Parent (EntityRepository) find method:\n";
        echo "Parameters:\n";
        foreach ($parentFindMethod->getParameters() as $param) {
            echo "  - {$param->getName()}: ";
            if ($param->hasType()) {
                $type = $param->getType();
                echo $type;
                if ($type->allowsNull()) {
                    echo " (nullable)";
                }
            } else {
                echo "mixed";
            }
            if ($param->isDefaultValueAvailable()) {
                echo " = " . var_export($param->getDefaultValue(), true);
            }
            echo "\n";
        }
        
        echo "Return type: ";
        if ($parentFindMethod->hasReturnType()) {
            $returnType = $parentFindMethod->getReturnType();
            echo $returnType;
            if ($returnType->allowsNull()) {
                echo " (nullable)";
            }
        } else {
            echo "mixed";
        }
        echo "\n\n";
    }
}

echo "✓ Test completed\n";