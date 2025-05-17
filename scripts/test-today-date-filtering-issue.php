<?php

// Test pour identifier le problème du filtrage de la date du jour

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap-doctrine.php';

use App\Repositories\Doctrine\WhatsApp\WhatsAppMessageHistoryRepository;
use App\Entities\WhatsApp\WhatsAppMessageHistory;

$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Afficher la date actuelle selon différents fuseaux horaires
echo "Configuration des dates et fuseaux horaires\n";
echo "=========================================\n";
echo "PHP timezone: " . date_default_timezone_get() . "\n";
echo "Date actuelle (PHP): " . date('Y-m-d H:i:s') . "\n";
echo "DateTime now: " . (new DateTime())->format('Y-m-d H:i:s') . "\n";
echo "DateTime now UTC: " . (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s') . "\n";
echo "\n";

// Créer des messages de test avec différentes heures aujourd'hui
echo "Création de messages de test pour aujourd'hui\n";
echo "============================================\n";
$today = date('Y-m-d');
$testTimes = [
    ' 00:00:01',  // Début de journée
    ' 06:30:00',  // Matin
    ' 12:00:00',  // Midi
    ' 18:30:00',  // Soir
    ' 23:59:59'   // Fin de journée
];

foreach ($testTimes as $time) {
    $message = new WhatsAppMessageHistory();
    $message->setWabaMessageId('test-' . uniqid());
    $message->setPhoneNumber('+22500000' . rand(1000, 9999));
    $message->setDirection('OUTGOING');
    $message->setType('text');
    $message->setContent('Test message pour ' . $today . $time);
    $message->setStatus('sent');
    
    // Forcer la date de création
    $createdAt = new DateTime($today . $time);
    $message->setCreatedAt($createdAt);
    $message->setTimestamp($createdAt);
    
    try {
        $entityManager->persist($message);
        echo "Message créé pour: " . $createdAt->format('Y-m-d H:i:s') . "\n";
    } catch (Exception $e) {
        echo "Erreur création: " . $e->getMessage() . "\n";
    }
}

try {
    $entityManager->flush();
    echo "\nMessages de test créés avec succès!\n\n";
} catch (Exception $e) {
    echo "\nErreur lors de la sauvegarde: " . $e->getMessage() . "\n\n";
}

// Tester différentes méthodes de filtrage
echo "Test de filtrage par date\n";
echo "========================\n";

// 1. Test avec dates exactes 00:00:00 et 23:59:59
$startDate1 = new DateTime($today . ' 00:00:00');
$endDate1 = new DateTime($today . ' 23:59:59');

$qb1 = $entityManager->createQueryBuilder();
$qb1->select('m')
    ->from('App\Entities\WhatsApp\WhatsAppMessageHistory', 'm')
    ->where('m.createdAt >= :start')
    ->andWhere('m.createdAt <= :end')
    ->setParameter('start', $startDate1)
    ->setParameter('end', $endDate1);

$results1 = $qb1->getQuery()->getResult();
echo "1. Filtrage 00:00:00 - 23:59:59: " . count($results1) . " messages\n";

// 2. Test avec DateTime sans heure spécifiée
$startDate2 = new DateTime($today);
$endDate2 = clone $startDate2;
$endDate2->setTime(23, 59, 59);

$qb2 = $entityManager->createQueryBuilder();
$qb2->select('m')
    ->from('App\Entities\WhatsApp\WhatsAppMessageHistory', 'm')
    ->where('m.createdAt >= :start')
    ->andWhere('m.createdAt <= :end')
    ->setParameter('start', $startDate2)
    ->setParameter('end', $endDate2);

$results2 = $qb2->getQuery()->getResult();
echo "2. Filtrage DateTime basique: " . count($results2) . " messages\n";

// 3. Test avec BETWEEN
$qb3 = $entityManager->createQueryBuilder();
$qb3->select('m')
    ->from('App\Entities\WhatsApp\WhatsAppMessageHistory', 'm')
    ->where('m.createdAt BETWEEN :start AND :end')
    ->setParameter('start', $startDate1)
    ->setParameter('end', $endDate1);

$results3 = $qb3->getQuery()->getResult();
echo "3. Filtrage avec BETWEEN: " . count($results3) . " messages\n";

// 4. Afficher quelques résultats pour vérifier
echo "\nExemples de messages trouvés:\n";
$count = 0;
foreach ($results1 as $msg) {
    echo sprintf("  - ID: %d, Créé: %s, Tel: %s\n", 
        $msg->getId(),
        $msg->getCreatedAt()->format('Y-m-d H:i:s'),
        $msg->getPhoneNumber()
    );
    if (++$count >= 5) break;
}

// 5. Test avec repository custom method
echo "\n5. Test avec WhatsAppMessageHistoryRepository:\n";
$repository = new WhatsAppMessageHistoryRepository($entityManager);
$dateFilters = [
    'startDate' => new DateTime($today . ' 00:00:00'),
    'endDate' => new DateTime($today . ' 23:59:59')
];

try {
    $repoResults = $repository->findByWithDateRange([], $dateFilters);
    echo "Repository custom method: " . count($repoResults) . " messages\n";
} catch (Exception $e) {
    echo "Erreur repository: " . $e->getMessage() . "\n";
}

// Nettoyage des messages de test
echo "\nNettoyage des messages de test...\n";
foreach ($results1 as $msg) {
    if (strpos($msg->getWabaMessageId(), 'test-') === 0) {
        $entityManager->remove($msg);
    }
}
$entityManager->flush();
echo "Terminé!\n";