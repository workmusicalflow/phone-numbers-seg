<?php
/**
 * Test d'envoi de message texte WhatsApp en utilisant le conteneur DI
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;

// Configuration du conteneur
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../src/config/di.php');
$container = $containerBuilder->build();

// Récupérer les services
$userRepo = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);
$whatsappService = $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface::class);
$config = $container->get('whatsapp.config');

// Debug configuration
echo "=== Test d'envoi de message texte WhatsApp ===\n\n";
echo "Configuration WhatsApp :\n";
echo "API Version : " . ($config['api_version'] ?? 'non défini') . "\n";
echo "Phone Number ID : " . ($config['phone_number_id'] ?? 'non défini') . "\n";
echo "Access Token : " . (isset($config['access_token']) ? (substr($config['access_token'], 0, 10) . '...') : 'non défini') . "\n\n";

// Obtenir l'utilisateur admin
$user = $userRepo->findById(1);
echo "Utilisateur : " . $user->getUsername() . "\n\n";

// Configuration
$phoneNumber = '+2250777104936';
$message = "Merci pour votre message ! Ceci est une réponse automatique envoyée dans la fenêtre de 24 heures.";

try {
    echo "Envoi du message texte à $phoneNumber...\n";
    
    $result = $whatsappService->sendMessage(
        $user,
        $phoneNumber,
        'text',
        $message
    );
    
    echo "\n✅ Message envoyé avec succès !\n";
    echo "ID : " . ($result->getId() ?? 'NULL') . "\n";
    echo "WABA ID : " . ($result->getWabaMessageId() ?? 'NULL') . "\n";
    echo "Status : " . ($result->getStatus() ?? 'NULL') . "\n";
    echo "Type : " . ($result->getType() ?? 'NULL') . "\n";
    echo "Direction : " . ($result->getDirection() ?? 'NULL') . "\n";
    echo "Content : " . ($result->getContent() ?? 'NULL') . "\n";
    
} catch (\Exception $e) {
    echo "\n❌ Erreur : " . $e->getMessage() . "\n";
    
    if (strpos($e->getMessage(), 'API error') !== false || strpos($e->getMessage(), '24-hour') !== false) {
        echo "\nDétails de l'erreur :\n";
        echo "Cette erreur peut survenir si :\n";
        echo "1. Aucune conversation n'a été initiée par l'utilisateur\n";
        echo "2. La fenêtre de 24 heures est expirée\n";
        echo "3. Le numéro n'est pas enregistré sur WhatsApp\n";
        echo "\nSolution :\n";
        echo "1. Envoyez d'abord un message depuis WhatsApp vers le numéro de votre Business\n";
        echo "2. Attendez que le webhook reçoive le message\n";
        echo "3. Relancez ce script pour répondre\n";
    }
    
    if (strpos($e->getMessage(), 'not exist') !== false || strpos($e->getMessage(), 'permissions') !== false) {
        echo "\nProblème de configuration détecté :\n";
        echo "1. Vérifiez que le Phone Number ID est correct\n";
        echo "2. Vérifiez que l'Access Token a les bonnes permissions\n";
        echo "3. Vérifiez que l'app a accès à l'API WhatsApp Business\n";
    }
    
    echo "\nTrace :\n";
    echo $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== Fin du test ===\n";