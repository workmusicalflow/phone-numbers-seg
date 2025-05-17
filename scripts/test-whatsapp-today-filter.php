<?php

// Test spécifique pour le filtrage par la date du jour

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap-doctrine.php';

use App\Repositories\Doctrine\WhatsApp\WhatsAppMessageHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;

// Obtenir le repository
$config = require __DIR__ . '/../src/config/di.php';
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';
$repository = $entityManager->getRepository(\App\Entities\WhatsApp\WhatsAppMessageHistory::class);

// Obtenir la date d'aujourd'hui
$today = date('Y-m-d');
$todayStart = new DateTime($today . ' 00:00:00');
$todayEnd = new DateTime($today . ' 23:59:59');

echo "Test du filtrage par date du jour\n";
echo "================================\n";
echo "Date du jour: $today\n";
echo "Début: " . $todayStart->format('Y-m-d H:i:s') . "\n";
echo "Fin: " . $todayEnd->format('Y-m-d H:i:s') . "\n\n";

// Test 1: Récupérer tous les messages pour voir leurs dates
echo "1. Récupération de tous les messages récents pour voir les dates:\n";
$allMessages = $repository->findBy([], ['createdAt' => 'DESC'], 10);
foreach ($allMessages as $message) {
    echo "  ID: " . $message->getId() . 
         " - Créé le: " . $message->getCreatedAt()->format('Y-m-d H:i:s') . 
         " - Téléphone: " . $message->getPhoneNumber() . "\n";
}

echo "\n2. Test du filtrage par date du jour:\n";
$dateFilters = [
    'startDate' => $todayStart,
    'endDate' => $todayEnd
];

$todayMessages = $repository->findByWithDateRange([], $dateFilters);
echo "Nombre de messages trouvés pour aujourd'hui: " . count($todayMessages) . "\n";

foreach ($todayMessages as $message) {
    echo "  ID: " . $message->getId() . 
         " - Créé le: " . $message->getCreatedAt()->format('Y-m-d H:i:s') . 
         " - Téléphone: " . $message->getPhoneNumber() . "\n";
}

// Test 3: Tester avec une requête directe SQL
echo "\n3. Test avec requête SQL directe:\n";
$sql = "SELECT COUNT(*) as count FROM whatsapp_message_history WHERE DATE(created_at) = ?";
$stmt = $entityManager->getConnection()->prepare($sql);
$stmt->execute([$today]);
$result = $stmt->fetch();
echo "Nombre de messages aujourd'hui (SQL direct): " . $result['count'] . "\n";

// Test 4: Vérifier le fuseau horaire
echo "\n4. Vérification du fuseau horaire:\n";
echo "PHP timezone: " . date_default_timezone_get() . "\n";
echo "Date/heure actuelles: " . date('Y-m-d H:i:s') . "\n";

// Test 5: Obtenir un message et vérifier sa date/heure exacte
if (!empty($allMessages)) {
    $firstMessage = $allMessages[0];
    echo "\n5. Détails du premier message:\n";
    echo "ID: " . $firstMessage->getId() . "\n";
    echo "Created At (DateTime): " . $firstMessage->getCreatedAt()->format('Y-m-d H:i:s T') . "\n";
    echo "Timestamp: " . $firstMessage->getTimestamp()->format('Y-m-d H:i:s T') . "\n";
}