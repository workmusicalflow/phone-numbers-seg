<?php
/**
 * Test d'envoi du template hello_world WhatsApp
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;

// Configuration
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../src/config/di.php');
$container = $containerBuilder->build();

// Services
$userRepo = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);
$whatsappService = $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface::class);

// Obtenir l'utilisateur admin
$user = $userRepo->findById(1);
echo "=== Test du template hello_world ===\n\n";
echo "Utilisateur : " . $user->getUsername() . "\n\n";

try {
    // hello_world est un template par défaut fourni par Meta
    echo "Envoi du template hello_world...\n";
    
    $result = $whatsappService->sendTemplateMessage(
        $user,
        '+2250777104936', // Votre numéro
        'hello_world',    // Template par défaut de Meta
        'en_US',          // Langue anglaise
        null,             // Pas d'image d'en-tête
        []                // Pas de paramètres pour hello_world
    );
    
    echo "\n✅ Template hello_world envoyé avec succès !\n";
    echo "ID : " . ($result->getId() ?? 'NULL') . "\n";
    echo "WABA ID : " . ($result->getWabaMessageId() ?? 'NULL') . "\n";
    echo "Status : " . ($result->getStatus() ?? 'NULL') . "\n";
    echo "Template : " . ($result->getTemplateName() ?? 'NULL') . "\n";
    echo "Langue : " . ($result->getTemplateLanguage() ?? 'NULL') . "\n";
    
    // Le template hello_world envoie généralement : "Hello World!"
    echo "\nLe message envoyé devrait être : 'Hello World!'\n";
    
} catch (\Exception $e) {
    echo "\n❌ Erreur : " . $e->getMessage() . "\n";
    echo "Fichier : " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    // Si hello_world n'existe pas, Meta devrait le signaler
    if (strpos($e->getMessage(), 'template') !== false || strpos($e->getMessage(), 'Template') !== false) {
        echo "\nIl semble que le template hello_world n'est pas disponible.\n";
        echo "Vérifiez dans le WhatsApp Business Manager que ce template existe.\n";
    }
    
    echo "\nTrace :\n" . $e->getTraceAsString() . "\n";
}