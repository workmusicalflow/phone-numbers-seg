<?php

require_once __DIR__ . '/../vendor/autoload.php';
$entityManager = require_once __DIR__ . '/../src/bootstrap-doctrine.php';

use App\Entities\WhatsApp\WhatsAppTemplate;
use App\Entities\WhatsApp\WhatsAppQueue;
use App\Entities\User;

try {
    // Test création d'un template
    $template = new WhatsAppTemplate();
    $template->setName('test_template');
    $template->setLanguage('fr');
    $template->setStatus('pending');
    $template->setIsActive(true);
    
    $entityManager->persist($template);
    
    // Test de récupération d'un utilisateur pour la queue
    $userRepo = $entityManager->getRepository(User::class);
    $user = $userRepo->findOneBy(['email' => 'admin@oracle.local']);
    
    if ($user) {
        // Test création d'un message en queue
        $queueMessage = new WhatsAppQueue();
        $queueMessage->setOracleUser($user);
        $queueMessage->setRecipientPhone('+2250101010101');
        $queueMessage->setMessageType('text');
        $queueMessage->setMessageContent('Test message');
        
        $entityManager->persist($queueMessage);
    }
    
    $entityManager->flush();
    
    echo "✓ Entités persistées avec succès!\n";
    
    // Test de récupération
    $templates = $entityManager->getRepository(WhatsAppTemplate::class)->findAll();
    echo "Templates trouvés: " . count($templates) . "\n";
    
    if ($user) {
        $queueMessages = $entityManager->getRepository(WhatsAppQueue::class)->findAll();
        echo "Messages en queue: " . count($queueMessages) . "\n";
    }
    
} catch (\Exception $e) {
    echo "✗ Erreur: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}