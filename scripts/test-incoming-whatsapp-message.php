<?php

require_once __DIR__ . '/../vendor/autoload.php';
$entityManager = require_once __DIR__ . '/../src/bootstrap-doctrine.php';

use DI\ContainerBuilder;
use App\Entities\WhatsApp\WhatsAppMessageHistory;

// Créer le conteneur DI
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(require __DIR__ . '/../src/config/di.php');
$container = $containerBuilder->build();

// Récupérer les repositories
$userRepo = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);
$messageRepo = $container->get(\App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface::class);

// Trouver un utilisateur pour associer le message
$user = $userRepo->findByEmail('admin@example.com');
if (!$user) {
    die("Erreur : Utilisateur admin@example.com non trouvé\n");
}

// Créer un message entrant de test
$incomingMessage = new WhatsAppMessageHistory();
$incomingMessage->setOracleUser($user);
$incomingMessage->setWabaMessageId('wamid.' . uniqid());
$incomingMessage->setPhoneNumber('+2250777104936'); // Votre numéro
$incomingMessage->setDirection('INCOMING');
$incomingMessage->setType('text');
$incomingMessage->setContent('Bonjour, ceci est un message test depuis WhatsApp!');
$incomingMessage->setStatus('received');
$incomingMessage->setTimestamp(new \DateTime());
$incomingMessage->setMetadata([
    'profile' => [
        'name' => 'Utilisateur Test'
    ],
    'type' => 'text',
    'text' => [
        'body' => 'Bonjour, ceci est un message test depuis WhatsApp!'
    ]
]);

// Sauvegarder le message
try {
    $messageRepo->save($incomingMessage);
    echo "Message entrant créé avec succès!\n";
    echo "ID: " . $incomingMessage->getId() . "\n";
    echo "WABA ID: " . $incomingMessage->getWabaMessageId() . "\n";
    echo "De: " . $incomingMessage->getPhoneNumber() . "\n";
    echo "Contenu: " . $incomingMessage->getContent() . "\n";
    echo "Créé à: " . $incomingMessage->getCreatedAt()->format('Y-m-d H:i:s') . "\n";
} catch (\Exception $e) {
    echo "Erreur lors de la sauvegarde : " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

// Créer aussi un message sortant récent pour comparaison
$outgoingMessage = new WhatsAppMessageHistory();
$outgoingMessage->setOracleUser($user);
$outgoingMessage->setWabaMessageId('wamid.' . uniqid());
$outgoingMessage->setPhoneNumber('+2250777104936');
$outgoingMessage->setDirection('OUTGOING');
$outgoingMessage->setType('text');
$outgoingMessage->setContent('Message de test sortant pour vérifier l\'affichage');
$outgoingMessage->setStatus('delivered');
$outgoingMessage->setTimestamp(new \DateTime('-1 hour')); // Il y a 1 heure

try {
    $messageRepo->save($outgoingMessage);
    echo "\nMessage sortant créé avec succès!\n";
    echo "ID: " . $outgoingMessage->getId() . "\n";
} catch (\Exception $e) {
    echo "Erreur lors de la sauvegarde du message sortant : " . $e->getMessage() . "\n";
}