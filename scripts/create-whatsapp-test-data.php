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

// Créer plusieurs messages pour aujourd'hui
$today = new \DateTime();
$messagesData = [
    // Messages entrants
    [
        'direction' => 'INCOMING',
        'phoneNumber' => '+2250101010101',
        'content' => 'Bonjour, j\'ai une question concernant vos services',
        'status' => 'received',
        'timestamp' => (clone $today)->modify('-3 hours')
    ],
    [
        'direction' => 'INCOMING',
        'phoneNumber' => '+2250202020202',
        'content' => 'Merci pour votre réponse rapide!',
        'status' => 'received',
        'timestamp' => (clone $today)->modify('-2 hours')
    ],
    [
        'direction' => 'INCOMING',
        'phoneNumber' => '+2250303030303',
        'content' => 'Comment puis-je souscrire à votre offre?',
        'status' => 'received',
        'timestamp' => (clone $today)->modify('-1 hour')
    ],
    // Messages sortants
    [
        'direction' => 'OUTGOING',
        'phoneNumber' => '+2250101010101',
        'content' => 'Bonjour, bien sûr! Comment puis-je vous aider?',
        'status' => 'delivered',
        'timestamp' => (clone $today)->modify('-3 hours')->modify('+10 minutes')
    ],
    [
        'direction' => 'OUTGOING',
        'phoneNumber' => '+2250202020202',
        'content' => 'De rien! N\'hésitez pas si vous avez d\'autres questions.',
        'status' => 'read',
        'timestamp' => (clone $today)->modify('-2 hours')->modify('+5 minutes')
    ],
    [
        'direction' => 'OUTGOING',
        'phoneNumber' => '+2250303030303',
        'content' => 'Pour souscrire, veuillez cliquer sur ce lien: www.example.com/subscribe',
        'status' => 'sent',
        'timestamp' => (clone $today)->modify('-30 minutes')
    ],
    [
        'direction' => 'OUTGOING',
        'phoneNumber' => '+2250404040404',
        'content' => 'Rappel: Votre rendez-vous est demain à 14h00',
        'status' => 'delivered',
        'timestamp' => (clone $today)->modify('-4 hours')
    ],
];

$count = 0;
foreach ($messagesData as $data) {
    $message = new WhatsAppMessageHistory();
    $message->setOracleUser($user);
    $message->setWabaMessageId('wamid.' . uniqid());
    $message->setPhoneNumber($data['phoneNumber']);
    $message->setDirection($data['direction']);
    $message->setType('text');
    $message->setContent($data['content']);
    $message->setStatus($data['status']);
    $message->setTimestamp($data['timestamp']);
    $message->setMetadata([
        'type' => 'text',
        'text' => ['body' => $data['content']]
    ]);
    
    try {
        $messageRepo->save($message);
        $count++;
        echo "Message " . $count . " créé: " . $data['direction'] . " - " . $data['phoneNumber'] . "\n";
    } catch (\Exception $e) {
        echo "Erreur: " . $e->getMessage() . "\n";
    }
}

echo "\nTotal: " . $count . " messages créés avec succès!\n";