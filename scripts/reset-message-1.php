<?php

/**
 * Script pour réinitialiser le message 1
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\WhatsApp\WhatsAppQueue;

// Charger l'EntityManager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

try {
    $message = $entityManager->find(WhatsAppQueue::class, 1);
    
    if ($message) {
        echo "Message trouvé:\n";
        echo "- ID: " . $message->getId() . "\n";
        echo "- Status: " . $message->getStatus() . "\n";
        echo "- Phone: " . $message->getRecipientPhone() . "\n";
        echo "- Priority: " . $message->getPriority() . "\n";
        
        // Réinitialiser en pending
        $message->setStatus('pending');
        $entityManager->persist($message);
        $entityManager->flush();
        
        echo "✅ Message réinitialisé en 'pending'\n";
    } else {
        echo "Message non trouvé\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}