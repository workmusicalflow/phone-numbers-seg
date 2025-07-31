<?php

// Test spécifique pour le filtrage par la date du jour

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap-doctrine.php';

// Obtenir l'EntityManager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Obtenir la date d'aujourd'hui
$today = date('Y-m-d');
$todayStart = new DateTime($today . ' 00:00:00');
$todayEnd = new DateTime($today . ' 23:59:59');

echo "Test du filtrage par date du jour\n";
echo "================================\n";
echo "Date du jour: $today\n";
echo "Début: " . $todayStart->format('Y-m-d H:i:s') . "\n";
echo "Fin: " . $todayEnd->format('Y-m-d H:i:s') . "\n\n";

// Test 1: Requête directe avec QueryBuilder
echo "1. Test avec QueryBuilder pour la date du jour:\n";
$qb = $entityManager->createQueryBuilder();
$qb->select('m')
   ->from('App\Entities\WhatsApp\WhatsAppMessageHistory', 'm')
   ->where('m.createdAt >= :startDate')
   ->andWhere('m.createdAt <= :endDate')
   ->setParameter('startDate', $todayStart)
   ->setParameter('endDate', $todayEnd)
   ->orderBy('m.createdAt', 'DESC');

$query = $qb->getQuery();
echo "SQL généré: " . $query->getSQL() . "\n";
echo "Paramètres: " . json_encode($query->getParameters()->toArray()) . "\n\n";

$messages = $query->getResult();
echo "Nombre de messages trouvés: " . count($messages) . "\n";

foreach ($messages as $message) {
    echo "  ID: " . $message->getId() . 
         " - Créé le: " . $message->getCreatedAt()->format('Y-m-d H:i:s') . 
         " - Téléphone: " . $message->getPhoneNumber() . "\n";
}

// Test 2: Vérifier avec une requête SQL native
echo "\n2. Test avec requête SQL native:\n";
$sql = "SELECT id, created_at, phone_number FROM whatsapp_message_history 
        WHERE created_at >= ? AND created_at <= ?
        ORDER BY created_at DESC";
$stmt = $entityManager->getConnection()->prepare($sql);
$stmt->execute([$today . ' 00:00:00', $today . ' 23:59:59']);
$results = $stmt->fetchAll();

echo "Nombre de messages (SQL natif): " . count($results) . "\n";
foreach ($results as $result) {
    echo "  ID: " . $result['id'] . 
         " - Créé le: " . $result['created_at'] . 
         " - Téléphone: " . $result['phone_number'] . "\n";
}

// Test 3: Vérifier avec une condition DATE() SQL
echo "\n3. Test avec DATE() SQL:\n";
$sql2 = "SELECT id, created_at, phone_number FROM whatsapp_message_history 
         WHERE DATE(created_at) = ?
         ORDER BY created_at DESC";
$stmt2 = $entityManager->getConnection()->prepare($sql2);
$stmt2->execute([$today]);
$results2 = $stmt2->fetchAll();

echo "Nombre de messages (avec DATE()): " . count($results2) . "\n";
foreach ($results2 as $result) {
    echo "  ID: " . $result['id'] . 
         " - Créé le: " . $result['created_at'] . 
         " - Téléphone: " . $result['phone_number'] . "\n";
}

// Test 4: Vérifier le fuseau horaire
echo "\n4. Vérification du fuseau horaire:\n";
echo "PHP timezone: " . date_default_timezone_get() . "\n";
echo "Date/heure actuelles: " . date('Y-m-d H:i:s') . "\n";
$dbTimezone = $entityManager->getConnection()->executeQuery("SELECT datetime('now', 'localtime') as now")->fetchOne();
echo "SQLite datetime locale: " . $dbTimezone . "\n";