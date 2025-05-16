<?php
/**
 * Script de migration des données WhatsApp existantes vers la nouvelle structure
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use Doctrine\ORM\EntityManagerInterface;
use App\Entities\WhatsApp\WhatsAppMessage;
use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\Entities\User;

echo "=== Migration des données WhatsApp ===\n\n";

try {
    // Construction du conteneur d'injection de dépendances
    $containerBuilder = new ContainerBuilder();
    $containerBuilder->addDefinitions(__DIR__ . '/../src/config/di.php');
    $container = $containerBuilder->build();

    // Récupération de l'entity manager
    $entityManager = $container->get(EntityManagerInterface::class);
    
    // Récupérer tous les messages existants
    $oldMessages = $entityManager->getRepository(WhatsAppMessage::class)->findAll();
    
    echo "Messages à migrer : " . count($oldMessages) . "\n\n";
    
    // Obtenir le premier utilisateur admin (par défaut pour la migration)
    $defaultUser = $entityManager->getRepository(User::class)
        ->findOneBy(['isAdmin' => true]);
    
    if (!$defaultUser) {
        // Si pas d'admin, prendre le premier utilisateur
        $defaultUser = $entityManager->getRepository(User::class)->findOneBy([]);
    }
    
    if (!$defaultUser) {
        throw new \Exception("Aucun utilisateur trouvé pour associer les messages");
    }
    
    echo "Utilisateur par défaut pour la migration : " . $defaultUser->getEmail() . "\n\n";
    
    $migrated = 0;
    $errors = 0;
    
    foreach ($oldMessages as $oldMessage) {
        try {
            // Vérifier si le message n'a pas déjà été migré
            $existing = $entityManager->getRepository(WhatsAppMessageHistory::class)
                ->findOneBy(['wabaMessageId' => $oldMessage->getMessageId()]);
            
            if ($existing) {
                echo "Message déjà migré : " . $oldMessage->getMessageId() . "\n";
                continue;
            }
            
            // Créer le nouveau message
            $newMessage = new WhatsAppMessageHistory();
            $newMessage->setWabaMessageId($oldMessage->getMessageId());
            $newMessage->setOracleUser($defaultUser);
            
            // Déterminer la direction basée sur sender/recipient
            if ($oldMessage->getRecipient()) {
                $newMessage->setDirection(WhatsAppMessageHistory::DIRECTION_OUTBOUND);
                $newMessage->setPhoneNumber($oldMessage->getRecipient());
            } else {
                $newMessage->setDirection(WhatsAppMessageHistory::DIRECTION_INBOUND);
                $newMessage->setPhoneNumber($oldMessage->getSender());
            }
            
            // Mapper le type
            $typeMap = [
                'text' => WhatsAppMessageHistory::TYPE_TEXT,
                'image' => WhatsAppMessageHistory::TYPE_IMAGE,
                'video' => WhatsAppMessageHistory::TYPE_VIDEO,
                'audio' => WhatsAppMessageHistory::TYPE_AUDIO,
                'document' => WhatsAppMessageHistory::TYPE_DOCUMENT,
                'template' => WhatsAppMessageHistory::TYPE_TEMPLATE,
                'interactive' => WhatsAppMessageHistory::TYPE_INTERACTIVE,
            ];
            
            $type = $typeMap[strtolower($oldMessage->getType())] ?? WhatsAppMessageHistory::TYPE_TEXT;
            $newMessage->setType($type);
            
            // Contenu
            if ($oldMessage->getContent()) {
                $content = [
                    'body' => $oldMessage->getContent(),
                    'media_url' => $oldMessage->getMediaUrl(),
                    'media_type' => $oldMessage->getMediaType(),
                    'raw_data' => $oldMessage->getRawData()
                ];
                $newMessage->setContentFromArray($content);
            }
            
            // Statut
            $statusMap = [
                'sent' => WhatsAppMessageHistory::STATUS_SENT,
                'delivered' => WhatsAppMessageHistory::STATUS_DELIVERED,
                'read' => WhatsAppMessageHistory::STATUS_READ,
                'failed' => WhatsAppMessageHistory::STATUS_FAILED,
                'received' => WhatsAppMessageHistory::STATUS_RECEIVED,
            ];
            
            $status = $oldMessage->getStatus() ? 
                ($statusMap[strtolower($oldMessage->getStatus())] ?? WhatsAppMessageHistory::STATUS_RECEIVED) :
                WhatsAppMessageHistory::STATUS_RECEIVED;
                
            $newMessage->setStatus($status);
            
            // Timestamp
            $newMessage->setTimestampFromUnix($oldMessage->getTimestamp());
            
            // Persister
            $entityManager->persist($newMessage);
            $migrated++;
            
            if ($migrated % 100 === 0) {
                $entityManager->flush();
                echo "Migré : $migrated messages...\n";
            }
            
        } catch (\Exception $e) {
            echo "Erreur migration message {$oldMessage->getMessageId()}: " . $e->getMessage() . "\n";
            $errors++;
        }
    }
    
    // Flush final
    $entityManager->flush();
    
    echo "\n=== Résumé de la migration ===\n";
    echo "✅ Messages migrés : $migrated\n";
    echo "❌ Erreurs : $errors\n";
    echo "Total traité : " . count($oldMessages) . "\n";
    
    // Option pour supprimer l'ancienne table
    echo "\nVoulez-vous supprimer l'ancienne table whatsapp_messages ? (y/N) : ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    
    if (trim($line) === 'y' || trim($line) === 'Y') {
        $connection = $entityManager->getConnection();
        $connection->executeStatement('DROP TABLE IF EXISTS whatsapp_messages');
        echo "✅ Ancienne table supprimée\n";
    } else {
        echo "Table conservée\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    echo "Trace : " . $e->getTraceAsString() . "\n";
    exit(1);
}