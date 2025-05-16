<?php
/**
 * Script de test pour vérifier le stockage automatique des messages WhatsApp
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;

// Créer le conteneur DI
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../src/config/di.php');
$container = $containerBuilder->build();

// Récupérer les services
$messageRepo = $container->get(\App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface::class);
$userRepo = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);

echo "=== Test du stockage des messages WhatsApp ===\n\n";

// 1. Vérifier les messages récents
echo "1. Messages récents dans la base de données :\n";
echo str_repeat('-', 50) . "\n";

$user = $userRepo->find(1); // Admin
if (!$user) {
    echo "Erreur : utilisateur admin non trouvé\n";
    exit(1);
}

$recentMessages = $messageRepo->findByUser($user, 10, 0);

if (empty($recentMessages)) {
    echo "Aucun message trouvé dans la base de données.\n";
} else {
    echo "Nombre de messages trouvés : " . count($recentMessages) . "\n\n";
    
    foreach ($recentMessages as $message) {
        echo "ID: " . $message->getId() . "\n";
        echo "WABA ID: " . $message->getWabaMessageId() . "\n";
        echo "Direction: " . $message->getDirection() . "\n";
        echo "Type: " . $message->getType() . "\n";
        echo "Phone: " . $message->getPhoneNumber() . "\n";
        echo "Status: " . $message->getStatus() . "\n";
        echo "Content: " . substr($message->getContent() ?? '', 0, 100) . "\n";
        echo "Created: " . $message->getCreatedAt()->format('Y-m-d H:i:s') . "\n";
        
        $metadata = $message->getMetadata();
        if ($metadata) {
            echo "Metadata: " . json_encode($metadata, JSON_PRETTY_PRINT) . "\n";
        }
        
        echo str_repeat('-', 30) . "\n";
    }
}

// 2. Vérifier les messages par téléphone
echo "\n2. Messages par numéro de téléphone :\n";
echo str_repeat('-', 50) . "\n";

$phoneNumber = '+2250777104936';
$messagesByPhone = $messageRepo->findByPhoneNumber($phoneNumber, $user, 5, 0);

if (empty($messagesByPhone)) {
    echo "Aucun message trouvé pour le numéro $phoneNumber\n";
} else {
    echo "Nombre de messages trouvés pour $phoneNumber : " . count($messagesByPhone) . "\n\n";
    
    foreach ($messagesByPhone as $message) {
        echo "[" . $message->getDirection() . "] ";
        echo $message->getCreatedAt()->format('Y-m-d H:i:s') . " - ";
        echo $message->getType() . " - ";
        echo $message->getContent() . "\n";
    }
}

// 3. Statistiques
echo "\n3. Statistiques des messages :\n";
echo str_repeat('-', 50) . "\n";

$stats = $messageRepo->getStatistics($user);
echo "Total messages : " . $stats['total'] . "\n";
echo "Par direction :\n";
foreach ($stats['by_direction'] as $direction => $count) {
    echo "  - $direction : $count\n";
}
echo "Par statut :\n";
foreach ($stats['by_status'] as $status => $count) {
    echo "  - $status : $count\n";
}
echo "Par type :\n";
foreach ($stats['by_type'] as $type => $count) {
    echo "  - $type : $count\n";
}

echo "\n=== Fin du test ===\n";