<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\ORM\EntityManagerInterface;
use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\Entities\WhatsApp\WhatsAppTemplate;
use App\Entities\WhatsApp\WhatsAppQueue;
use App\Entities\User;

// Entity Manager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

echo "=== Création de données de test WhatsApp ===\n\n";

try {
    // 1. Obtenir l'utilisateur admin
    $userRepo = $entityManager->getRepository(User::class);
    $adminUser = $userRepo->find(1);
    
    if (!$adminUser) {
        throw new Exception("Utilisateur admin introuvable");
    }
    
    echo "1. Utilisateur admin trouvé: " . $adminUser->getEmail() . "\n\n";
    
    // 2. Créer des templates de test
    echo "2. Création de templates WhatsApp...\n";
    
    $templates = [
        [
            'name' => 'welcome_message',
            'language' => 'fr',
            'category' => 'MARKETING',
            'components' => [
                ['type' => 'BODY', 'text' => 'Bonjour {{1}}, bienvenue sur Oracle!']
            ]
        ],
        [
            'name' => 'order_confirmation',
            'language' => 'fr',
            'category' => 'TRANSACTIONAL',
            'components' => [
                ['type' => 'BODY', 'text' => 'Votre commande #{{1}} a été confirmée']
            ]
        ],
        [
            'name' => 'otp_code',
            'language' => 'fr',
            'category' => 'OTP',
            'components' => [
                ['type' => 'BODY', 'text' => 'Votre code de vérification est: {{1}}']
            ]
        ]
    ];
    
    foreach ($templates as $templateData) {
        $template = new WhatsAppTemplate();
        $template->setName($templateData['name']);
        $template->setLanguage($templateData['language']);
        $template->setCategory($templateData['category']);
        $template->setStatus(WhatsAppTemplate::STATUS_APPROVED);
        $template->setComponents($templateData['components']);
        
        $entityManager->persist($template);
        echo "   - Template créé: " . $template->getName() . "\n";
    }
    
    $entityManager->flush();
    echo "   ✓ Templates créés avec succès\n\n";
    
    // 3. Créer des messages d'historique de test
    echo "3. Création d'historique de messages...\n";
    
    $messages = [
        [
            'phone' => '+22507000001',
            'direction' => WhatsAppMessageHistory::DIRECTION_OUTBOUND,
            'type' => WhatsAppMessageHistory::TYPE_TEMPLATE,
            'status' => WhatsAppMessageHistory::STATUS_DELIVERED,
            'templateName' => 'welcome_message',
            'content' => 'Bonjour Jean, bienvenue sur Oracle!'
        ],
        [
            'phone' => '+22507000002',
            'direction' => WhatsAppMessageHistory::DIRECTION_INBOUND,
            'type' => WhatsAppMessageHistory::TYPE_TEXT,
            'status' => WhatsAppMessageHistory::STATUS_RECEIVED,
            'content' => 'Merci pour votre message!'
        ],
        [
            'phone' => '+22507000001',
            'direction' => WhatsAppMessageHistory::DIRECTION_OUTBOUND,
            'type' => WhatsAppMessageHistory::TYPE_IMAGE,
            'status' => WhatsAppMessageHistory::STATUS_READ,
            'mediaUrl' => 'https://example.com/image.jpg',
            'content' => '[Image]'
        ]
    ];
    
    foreach ($messages as $i => $messageData) {
        $message = new WhatsAppMessageHistory();
        $message->setOracleUser($adminUser);
        $message->setPhoneNumber($messageData['phone']);
        $message->setDirection($messageData['direction']);
        $message->setType($messageData['type']);
        $message->setStatus($messageData['status']);
        $message->setWabaMessageId('wamid.' . uniqid());
        $message->setTimestamp(new \DateTime());
        $message->setMessageContent($messageData['content']);
        
        if (isset($messageData['templateName'])) {
            $message->setTemplateName($messageData['templateName']);
        }
        
        if (isset($messageData['mediaUrl'])) {
            $message->setMediaUrl($messageData['mediaUrl']);
        }
        
        $entityManager->persist($message);
        echo "   - Message créé: " . $message->getDirection() . " - " . $message->getType() . "\n";
    }
    
    $entityManager->flush();
    echo "   ✓ Historique créé avec succès\n\n";
    
    // 4. Créer des messages en file d'attente
    echo "4. Création de messages en file d'attente...\n";
    
    $queueMessages = [
        [
            'phone' => '+22507000003',
            'type' => 'TEXT',
            'payload' => ['text' => 'Votre commande est en cours de livraison'],
            'priority' => WhatsAppQueue::PRIORITY_HIGH
        ],
        [
            'phone' => '+22507000004',
            'type' => 'TEMPLATE',
            'payload' => [
                'template' => 'order_confirmation',
                'variables' => ['12345']
            ],
            'priority' => WhatsAppQueue::PRIORITY_NORMAL
        ]
    ];
    
    foreach ($queueMessages as $queueData) {
        $queueItem = new WhatsAppQueue();
        $queueItem->setOracleUser($adminUser);
        $queueItem->setPhoneNumber($queueData['phone']);
        $queueItem->setMessageType($queueData['type']);
        $queueItem->setPayload($queueData['payload']);
        $queueItem->setPriority($queueData['priority']);
        
        $entityManager->persist($queueItem);
        echo "   - Message en file d'attente: " . $queueItem->getPhoneNumber() . " - " . $queueItem->getMessageType() . "\n";
    }
    
    $entityManager->flush();
    echo "   ✓ File d'attente créée avec succès\n\n";
    
    // 5. Afficher le résumé
    echo "=== Résumé des données créées ===\n";
    
    $templateCount = $entityManager->getRepository(WhatsAppTemplate::class)->count([]);
    $historyCount = $entityManager->getRepository(WhatsAppMessageHistory::class)->count([]);
    $queueCount = $entityManager->getRepository(WhatsAppQueue::class)->count([]);
    
    echo "Templates: $templateCount\n";
    echo "Messages d'historique: $historyCount\n";
    echo "Messages en file d'attente: $queueCount\n";
    
} catch (Exception $e) {
    echo "\nErreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test terminé ===\n";