<?php

// Define application root and include necessary files
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/vendor/autoload.php';

echo "=== Test de la refactorisation WhatsAppService ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Create DI container
$container = new \App\GraphQL\DIContainer();

try {
    // Get the services from DI
    $whatsAppService = $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface::class);
    $templateService = $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface::class);
    
    // Verify that the services are of the expected type
    echo "Vérification des types de services:\n";
    echo "- WhatsAppService: " . get_class($whatsAppService) . "\n";
    echo "- TemplateService: " . get_class($templateService) . "\n\n";
    
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
    
    // Test the direct template service method
    echo "Test de TemplateService->fetchApprovedTemplatesFromMeta():\n";
    try {
        $templates = $templateService->fetchApprovedTemplatesFromMeta();
        echo "✅ SUCCÈS: " . count($templates) . " templates récupérés directement\n";
        echo "Premier template: " . ($templates[0]['name'] ?? 'N/A') . "\n\n";
    } catch (\Exception $e) {
        echo "❌ ÉCHEC: " . $e->getMessage() . "\n\n";
    }
    
    // Test the WhatsAppService method that should now use the injected templateService
    echo "Test de WhatsAppService->getApprovedTemplates():\n";
    try {
        $filters = [
            'forceRefresh' => true,
            'useCache' => false,
            'debug' => true
        ];
        $templates = $whatsAppService->getApprovedTemplates($user, $filters);
        echo "✅ SUCCÈS: " . count($templates) . " templates récupérés via WhatsAppService\n";
        echo "Premier template: " . ($templates[0]['name'] ?? 'N/A') . "\n\n";
    } catch (\Exception $e) {
        echo "❌ ÉCHEC: " . $e->getMessage() . "\n\n";
    }
    
    echo "Tests terminés avec succès!\n";
    
} catch (\Exception $e) {
    echo "❌ ERREUR CRITIQUE: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}