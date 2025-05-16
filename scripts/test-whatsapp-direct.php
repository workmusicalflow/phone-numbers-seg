<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap-doctrine.php';

use App\GraphQL\DIContainer;
use App\Entities\User;

try {
    $container = new DIContainer();
    $entityManager = $container->get(\Doctrine\ORM\EntityManagerInterface::class);
    
    // Récupérer l'utilisateur admin
    $userRepo = $entityManager->getRepository(User::class);
    $user = $userRepo->findOneBy(['username' => 'admin']);
    
    if (!$user) {
        echo "Utilisateur admin non trouvé\n";
        exit(1);
    }
    
    echo "Utilisateur trouvé: " . $user->getUsername() . " (ID: " . $user->getId() . ")\n";
    
    // Récupérer le service WhatsApp
    $whatsappService = $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface::class);
    
    echo "Service WhatsApp récupéré\n";
    
    // Envoyer un message
    $recipient = '+2250777104936';
    $type = 'text';
    $content = 'Test direct PHP - ' . date('Y-m-d H:i:s');
    
    echo "Envoi du message à $recipient...\n";
    
    $messageHistory = $whatsappService->sendMessage(
        $user,
        $recipient,
        $type,
        $content
    );
    
    echo "Message envoyé!\n";
    echo "ID: " . $messageHistory->getId() . "\n";
    echo "WABA ID: " . $messageHistory->getWabaMessageId() . "\n";
    echo "Phone Number: " . $messageHistory->getPhoneNumber() . "\n";
    echo "Direction: " . $messageHistory->getDirection() . "\n";
    echo "Type: " . $messageHistory->getType() . "\n";
    echo "Content: " . $messageHistory->getContent() . "\n";
    echo "Status: " . $messageHistory->getStatus() . "\n";
    
    // Vérifier dans la base de données
    $sql = "SELECT * FROM whatsapp_message_history WHERE id = ?";
    $stmt = $entityManager->getConnection()->prepare($sql);
    $stmt->bindValue(1, $messageHistory->getId());
    $result = $stmt->executeQuery();
    $row = $result->fetchAssociative();
    
    echo "\nDonnées en base de données:\n";
    print_r($row);
    
} catch (\Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}