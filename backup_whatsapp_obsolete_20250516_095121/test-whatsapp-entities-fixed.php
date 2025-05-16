<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\Entities\WhatsApp\WhatsAppTemplate;
use App\Entities\WhatsApp\WhatsAppQueue;
use App\Entities\User;

// Entity Manager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

echo "=== Test des entités WhatsApp corrigées ===\n\n";

try {
    // 1. Vérifier que les entités sont reconnues par Doctrine
    echo "1. Vérification des métadonnées Doctrine...\n";
    
    $whatsappClasses = [
        WhatsAppMessageHistory::class,
        WhatsAppTemplate::class,
        WhatsAppQueue::class
    ];
    
    foreach ($whatsappClasses as $class) {
        try {
            $metadata = $entityManager->getClassMetadata($class);
            echo "   ✓ " . $class . " - Table: " . $metadata->getTableName() . "\n";
        } catch (Exception $e) {
            echo "   ✗ " . $class . " - Erreur: " . $e->getMessage() . "\n";
        }
    }
    
    // 2. Test de création d'un template
    echo "\n2. Test de création d'un template...\n";
    
    $template = new WhatsAppTemplate();
    $template->setName('test_template_' . time());
    $template->setLanguage('fr');
    $template->setCategory('MARKETING');
    $template->setStatus('APPROVED');
    $template->setComponentsFromArray([
        ['type' => 'BODY', 'text' => 'Test {{1}}']
    ]);
    
    $entityManager->persist($template);
    $entityManager->flush();
    
    echo "   ✓ Template créé avec ID: " . $template->getId() . "\n";
    
    // 3. Test de l'historique des messages
    echo "\n3. Test de création d'un message d'historique...\n";
    
    $user = $entityManager->getRepository(User::class)->find(1);
    
    $message = new WhatsAppMessageHistory();
    $message->setOracleUser($user);
    $message->setWabaMessageId('wamid_test_' . uniqid());
    $message->setPhoneNumber('+22507000001');
    $message->setDirection('OUTBOUND');
    $message->setType('TEXT');
    $message->setStatus('DELIVERED');
    $message->setContent('Message de test');
    $message->setTimestamp(new \DateTime());
    
    $entityManager->persist($message);
    $entityManager->flush();
    
    echo "   ✓ Message créé avec ID: " . $message->getId() . "\n";
    
    // 4. Test de la file d'attente
    echo "\n4. Test de création d'un message en file d'attente...\n";
    
    $queue = new WhatsAppQueue();
    $queue->setOracleUser($user);
    $queue->setRecipientPhone('+22507000002');
    $queue->setMessageType('TEXT');
    $queue->setMessageContentFromArray([
        'text' => 'Message en attente'
    ]);
    $queue->setPriority(2);
    $queue->setStatus('PENDING');
    
    $entityManager->persist($queue);
    $entityManager->flush();
    
    echo "   ✓ Message en file d'attente créé avec ID: " . $queue->getId() . "\n";
    
    // 5. Test de récupération
    echo "\n5. Test de récupération des données...\n";
    
    $templates = $entityManager->getRepository(WhatsAppTemplate::class)->findAll();
    echo "   - Templates trouvés: " . count($templates) . "\n";
    
    $messages = $entityManager->getRepository(WhatsAppMessageHistory::class)->findAll();
    echo "   - Messages d'historique trouvés: " . count($messages) . "\n";
    
    $queueItems = $entityManager->getRepository(WhatsAppQueue::class)->findAll();
    echo "   - Messages en file d'attente trouvés: " . count($queueItems) . "\n";
    
    echo "\n=== Tests réussis ===\n";
    
} catch (Exception $e) {
    echo "\nErreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}