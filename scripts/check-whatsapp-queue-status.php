<?php

/**
 * Script pour vérifier le statut de la queue WhatsApp
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\WhatsApp\WhatsAppQueue;

// Charger l'EntityManager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

try {
    echo "=== Statut de la Queue WhatsApp ===\n\n";
    
    echo "Messages dans la queue:\n";
    
    // Compter par statut
    $qb = $entityManager->createQueryBuilder();
    $statusCounts = $qb->select('q.status, COUNT(q.id) as count')
        ->from(WhatsAppQueue::class, 'q')
        ->groupBy('q.status')
        ->getQuery()
        ->getResult();
    
    foreach ($statusCounts as $row) {
        echo sprintf("- %s: %d messages\n", $row['status'], $row['count']);
    }
    
    // Afficher les derniers messages
    echo "\nDerniers messages:\n";
    $messages = $entityManager->getRepository(WhatsAppQueue::class)
        ->createQueryBuilder('q')
        ->orderBy('q.createdAt', 'DESC')
        ->setMaxResults(5)
        ->getQuery()
        ->getResult();
    
    foreach ($messages as $message) {
        echo sprintf(
            "- ID: %d, Destinataire: %s, Status: %s, Créé: %s, Envoyé: %s\n",
            $message->getId(),
            $message->getRecipientPhone(),
            $message->getStatus(),
            $message->getCreatedAt()->format('Y-m-d H:i:s'),
            $message->getSentAt() ? $message->getSentAt()->format('Y-m-d H:i:s') : 'Non envoyé'
        );
    }
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}