<?php

/**
 * Script de test pour créer des messages dans la queue WhatsApp
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\WhatsApp\WhatsAppQueue;
// WhatsAppBulkBatch class removed - using batch ID instead
use App\Entities\User;
use DateTime;

// Charger l'EntityManager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

try {
    echo "Création d'un batch de test...\n";
    
    // Récupérer l'utilisateur admin
    $userRepository = $entityManager->getRepository(User::class);
    $adminUser = $userRepository->findOneBy(['username' => 'admin']);
    
    if (!$adminUser) {
        throw new Exception("Utilisateur admin non trouvé");
    }
    
    // Créer un batch ID unique
    $batchId = uniqid('batch_', true);
    echo "Batch ID généré: $batchId\n";
    
    // Créer quelques messages dans la queue
    $recipients = [
        '+22507000001',
        '+22507000002',
        '+22507000003'
    ];
    
    foreach ($recipients as $index => $recipient) {
        $message = new WhatsAppQueue();
        $message->setOracleUser($adminUser);
        $message->setRecipientPhone($recipient);
        $message->setTemplateName('hello_world');
        $message->setTemplateLanguage('en_US');
        $message->setMessageType('template');
        $message->setMessageContent(json_encode([
            'template_id' => 'hello_world',
            'batch_id' => $batchId,
            'parameters' => []
        ]));
        $message->setPriority($index === 0 ? 10 : 5); // Premier message haute priorité
        $message->setStatus('pending');
        $message->setScheduledAt(new DateTime()); // Envoyer immédiatement
        $message->setAttempts(0);
        $message->setMaxAttempts(3);
        
        $entityManager->persist($message);
        echo "Message créé pour: $recipient\n";
    }
    
    $entityManager->flush();
    
    echo "\n✅ 3 messages créés dans la queue\n";
    
    // Vérifier la queue
    $count = $entityManager->createQueryBuilder()
        ->select('COUNT(q.id)')
        ->from(WhatsAppQueue::class, 'q')
        ->where('q.status = :status')
        ->setParameter('status', 'pending')
        ->getQuery()
        ->getSingleScalarResult();
    
    echo "Total de messages en attente: $count\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}