<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\WhatsApp\WhatsAppMessageHistory;
use Doctrine\ORM\EntityManager;

try {
    // Utiliser l'entity manager depuis le bootstrap
    $em = require __DIR__ . '/../src/bootstrap-doctrine.php';
    
    // Compter tous les messages
    $totalCount = $em->getRepository(WhatsAppMessageHistory::class)->count([]);
    echo "Total des messages WhatsApp dans la base: " . $totalCount . "\n";
    
    // Récupérer quelques messages pour debug
    $messages = $em->getRepository(WhatsAppMessageHistory::class)->findBy([], ['createdAt' => 'DESC'], 5);
    
    if (empty($messages)) {
        echo "Aucun message trouvé dans la table whatsapp_message_history\n";
    } else {
        echo "\nDerniers messages:\n";
        foreach ($messages as $message) {
            echo sprintf(
                "- ID: %d, Numéro: %s, Direction: %s, Type: %s, Status: %s, Date: %s\n",
                $message->getId(),
                $message->getPhoneNumber(),
                $message->getDirection(),
                $message->getType(),
                $message->getStatus(),
                $message->getCreatedAt()->format('Y-m-d H:i:s')
            );
        }
    }
    
    // Vérifier la structure de la table
    $schemaManager = $em->getConnection()->getSchemaManager();
    $columns = $schemaManager->listTableColumns('whatsapp_message_history');
    
    echo "\nColonnes de la table whatsapp_message_history:\n";
    foreach ($columns as $column) {
        echo "- " . $column->getName() . " (" . $column->getType()->getName() . ")\n";
    }
    
} catch (\Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}