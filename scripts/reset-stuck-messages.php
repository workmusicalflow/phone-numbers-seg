<?php

/**
 * Script pour réinitialiser les messages bloqués en "processing"
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\WhatsApp\WhatsAppQueue;

// Charger l'EntityManager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

try {
    echo "Recherche des messages bloqués en 'processing'...\n";
    
    // Trouver les messages bloqués
    $stuckMessages = $entityManager->getRepository(WhatsAppQueue::class)
        ->createQueryBuilder('q')
        ->where('q.status = :status')
        ->andWhere('q.updatedAt < :threshold OR q.updatedAt IS NULL')
        ->setParameter('status', 'processing')
        ->setParameter('threshold', new \DateTime('-5 minutes'))
        ->getQuery()
        ->getResult();
    
    $count = count($stuckMessages);
    echo "Trouvé $count message(s) bloqué(s)\n";
    
    foreach ($stuckMessages as $message) {
        echo sprintf(
            "- Réinitialisation du message ID: %d pour %s\n",
            $message->getId(),
            $message->getRecipientPhone()
        );
        
        $message->setStatus('pending');
        $entityManager->persist($message);
    }
    
    if ($count > 0) {
        $entityManager->flush();
        echo "✅ Messages réinitialisés\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}