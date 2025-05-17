<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\Entities\User;

try {
    // Utiliser l'entity manager depuis le bootstrap
    $em = require __DIR__ . '/../src/bootstrap-doctrine.php';
    
    // Trouver l'utilisateur admin
    $adminUser = $em->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);
    
    if (!$adminUser) {
        echo "Utilisateur admin non trouvé!\n";
        exit(1);
    }
    
    echo "Utilisateur admin trouvé: ID=" . $adminUser->getId() . ", Email=" . $adminUser->getEmail() . "\n\n";
    
    // Compter les messages de cet utilisateur
    $userMessageCount = $em->getRepository(WhatsAppMessageHistory::class)->count(['oracleUser' => $adminUser]);
    echo "Messages WhatsApp de l'utilisateur admin: " . $userMessageCount . "\n\n";
    
    // Si aucun message, associer quelques messages à l'utilisateur admin pour tester
    if ($userMessageCount === 0) {
        echo "Aucun message trouvé pour l'utilisateur admin. Association de quelques messages...\n";
        
        // Récupérer quelques messages sans utilisateur
        $unassignedMessages = $em->getRepository(WhatsAppMessageHistory::class)->findBy([], [], 5);
        
        foreach ($unassignedMessages as $message) {
            // Vérifier si la propriété oracleUser existe
            try {
                // Utiliser la réflexion pour vérifier la propriété
                $reflection = new ReflectionClass($message);
                
                if ($reflection->hasProperty('oracleUser')) {
                    $property = $reflection->getProperty('oracleUser');
                    $property->setAccessible(true);
                    
                    // Associer le message à l'utilisateur admin
                    $property->setValue($message, $adminUser);
                    $em->persist($message);
                    echo "Message ID " . $message->getId() . " associé à l'utilisateur admin\n";
                } else {
                    echo "La propriété oracleUser n'existe pas sur l'entité WhatsAppMessageHistory\n";
                    
                    // Lister toutes les propriétés
                    echo "Propriétés disponibles:\n";
                    foreach ($reflection->getProperties() as $prop) {
                        echo "- " . $prop->getName() . "\n";
                    }
                    break;
                }
            } catch (\Exception $e) {
                echo "Erreur lors de l'association: " . $e->getMessage() . "\n";
            }
        }
        
        $em->flush();
        echo "\nAssociation terminée.\n";
        
        // Recompter
        $userMessageCount = $em->getRepository(WhatsAppMessageHistory::class)->count(['oracleUser' => $adminUser]);
        echo "Nouveaux messages WhatsApp de l'utilisateur admin: " . $userMessageCount . "\n";
    } else {
        // Afficher quelques messages de l'utilisateur
        $userMessages = $em->getRepository(WhatsAppMessageHistory::class)->findBy(
            ['oracleUser' => $adminUser],
            ['createdAt' => 'DESC'],
            3
        );
        
        echo "Exemples de messages de l'utilisateur:\n";
        foreach ($userMessages as $message) {
            echo sprintf(
                "- ID: %d, Numéro: %s, Direction: %s, Type: %s, Status: %s\n",
                $message->getId(),
                $message->getPhoneNumber(),
                $message->getDirection(),
                $message->getType(),
                $message->getStatus()
            );
        }
    }
    
} catch (\Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}