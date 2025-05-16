<?php
/**
 * Script de débogage pour l'enregistrement WhatsApp
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use App\Entities\User;
use App\Entities\WhatsApp\WhatsAppMessageHistory;

echo "=== Test d'enregistrement WhatsApp ===\n\n";

// Configuration
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../src/config/di.php');
$container = $containerBuilder->build();

$entityManager = $container->get(\Doctrine\ORM\EntityManagerInterface::class);
$userRepo = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);
$whatsappRepo = $container->get(\App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface::class);

// 1. Obtenir l'utilisateur admin
$user = $userRepo->findById(1);
echo "Utilisateur trouvé : " . $user->getUsername() . "\n\n";

// 2. Créer une entité WhatsApp
echo "Création de l'entité WhatsApp...\n";
$messageHistory = new WhatsAppMessageHistory();
$messageHistory->setOracleUser($user);
$messageHistory->setWabaMessageId('TEST_' . time());
$messageHistory->setPhoneNumber('+2250777104936');
$messageHistory->setDirection('OUTGOING');
$messageHistory->setType('text');
$messageHistory->setContent('Test de débogage');
$messageHistory->setStatus('sent');
$messageHistory->setTimestamp(new DateTime());

echo "Valeurs avant enregistrement :\n";
echo "- ID : " . ($messageHistory->getId() ? $messageHistory->getId() : 'NULL') . "\n";
echo "- Phone : " . $messageHistory->getPhoneNumber() . "\n";
echo "- WABA ID : " . $messageHistory->getWabaMessageId() . "\n\n";

// 3. Enregistrer
echo "Enregistrement...\n";
try {
    $savedEntity = $whatsappRepo->save($messageHistory);
    
    echo "Valeurs après enregistrement :\n";
    echo "- ID : " . ($savedEntity->getId() ? $savedEntity->getId() : 'NULL') . "\n";
    echo "- Phone : " . $savedEntity->getPhoneNumber() . "\n";
    echo "- WABA ID : " . $savedEntity->getWabaMessageId() . "\n\n";
    
    // 4. Récupérer depuis la DB
    echo "Recherche dans la base de données...\n";
    $foundEntity = $whatsappRepo->findByWabaMessageId($savedEntity->getWabaMessageId());
    
    if ($foundEntity) {
        echo "Entité trouvée dans la DB :\n";
        echo "- ID : " . $foundEntity->getId() . "\n";
        echo "- Phone : " . $foundEntity->getPhoneNumber() . "\n";
        echo "- WABA ID : " . $foundEntity->getWabaMessageId() . "\n";
        echo "\n✅ Enregistrement et récupération réussis!\n";
    } else {
        echo "❌ Entité non trouvée dans la DB!\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    echo "Trace : " . $e->getTraceAsString() . "\n";
}

// 5. Vérifier le nombre total d'enregistrements
$count = $whatsappRepo->count(['oracleUser' => $user]);
echo "\nNombre total d'enregistrements : " . $count . "\n";