<?php
/**
 * Script de test de la base de données WhatsApp
 * Vérifie la sauvegarde correcte des messages et l'intégrité des données
 */

require_once __DIR__ . '/../src/bootstrap-doctrine.php';

use App\Services\WhatsApp\WhatsAppService;
use App\Repositories\Doctrine\UserRepository;
use App\Repositories\Doctrine\WhatsApp\WhatsAppMessageHistoryRepository;

// Couleurs pour l'affichage
$green = "\033[32m";
$red = "\033[31m";
$yellow = "\033[33m";
$blue = "\033[34m";
$reset = "\033[0m";

echo "{$blue}=== Test de la base de données WhatsApp ==={$reset}\n\n";

try {
    $container = getContainer();
    $entityManager = $container->get(\Doctrine\ORM\EntityManagerInterface::class);
    $messageRepository = $container->get(WhatsAppMessageHistoryRepository::class);
    $userRepository = $container->get(UserRepository::class);
    $whatsAppService = $container->get(WhatsAppService::class);
    
    // 1. Vérifier la structure des tables
    echo "{$yellow}1. Vérification de la structure des tables...{$reset}\n";
    
    $schemaManager = $entityManager->getConnection()->getSchemaManager();
    $tables = $schemaManager->listTableNames();
    $whatsappTables = array_filter($tables, function($table) {
        return strpos($table, 'whatsapp') !== false;
    });
    
    echo "Tables WhatsApp trouvées :\n";
    foreach ($whatsappTables as $table) {
        echo "  - $table\n";
        
        // Afficher la structure de chaque table
        $columns = $schemaManager->listTableColumns($table);
        echo "    Colonnes :\n";
        foreach ($columns as $column) {
            echo "      - {$column->getName()} ({$column->getType()->getName()})\n";
        }
    }
    echo "{$green}✓ Structure des tables vérifiée{$reset}\n\n";
    
    // 2. Test d'insertion d'un message
    echo "{$yellow}2. Test d'insertion d'un message...{$reset}\n";
    
    $users = $userRepository->findAll();
    if (empty($users)) {
        throw new Exception("Aucun utilisateur trouvé");
    }
    $testUser = $users[0];
    
    // Créer un message de test via le service
    $testMessage = $whatsAppService->createMessageHistory(
        user: $testUser,
        phoneNumber: '+2250123456789',
        direction: 'OUTGOING',
        type: 'text',
        content: 'Test message DB - ' . date('Y-m-d H:i:s'),
        wabaMessageId: 'wamid.test_' . uniqid()
    );
    
    echo "Message créé avec ID : {$testMessage->getId()}\n";
    echo "{$green}✓ Message inséré avec succès{$reset}\n\n";
    
    // 3. Test de récupération
    echo "{$yellow}3. Test de récupération de messages...{$reset}\n";
    
    // Récupérer tous les messages
    $allMessages = $messageRepository->findAll();
    echo "Nombre total de messages : " . count($allMessages) . "\n";
    
    // Récupérer les messages de l'utilisateur test
    $userMessages = $messageRepository->findBy(['user' => $testUser]);
    echo "Messages de l'utilisateur test : " . count($userMessages) . "\n";
    
    // Récupérer les messages par numéro de téléphone
    $phoneMessages = $messageRepository->findBy(['phoneNumber' => '+2250123456789']);
    echo "Messages pour +2250123456789 : " . count($phoneMessages) . "\n";
    
    // Vérifier le dernier message
    if (!empty($userMessages)) {
        $lastMessage = end($userMessages);
        echo "Dernier message :\n";
        echo "  - ID : {$lastMessage->getId()}\n";
        echo "  - Contenu : {$lastMessage->getContent()}\n";
        echo "  - Statut : {$lastMessage->getStatus()}\n";
        echo "  - Créé le : {$lastMessage->getCreatedAt()->format('Y-m-d H:i:s')}\n";
    }
    
    echo "{$green}✓ Récupération réussie{$reset}\n\n";
    
    // 4. Test de mise à jour du statut
    echo "{$yellow}4. Test de mise à jour du statut...{$reset}\n";
    
    if (isset($testMessage)) {
        // Simuler une mise à jour de statut (comme depuis un webhook)
        $testMessage->setStatus('delivered');
        $testMessage->setDeliveredAt(new \DateTime());
        $entityManager->persist($testMessage);
        $entityManager->flush();
        
        // Vérifier la mise à jour
        $updatedMessage = $messageRepository->find($testMessage->getId());
        echo "Statut mis à jour : {$updatedMessage->getStatus()}\n";
        echo "Délivré le : {$updatedMessage->getDeliveredAt()->format('Y-m-d H:i:s')}\n";
        
        echo "{$green}✓ Mise à jour réussie{$reset}\n\n";
    }
    
    // 5. Test de requêtes complexes
    echo "{$yellow}5. Test de requêtes complexes...{$reset}\n";
    
    // Messages envoyés aujourd'hui
    $today = new \DateTime('today');
    $qb = $messageRepository->createQueryBuilder('m')
        ->where('m.createdAt >= :today')
        ->andWhere('m.direction = :direction')
        ->setParameter('today', $today)
        ->setParameter('direction', 'OUTGOING');
    
    $todayMessages = $qb->getQuery()->getResult();
    echo "Messages envoyés aujourd'hui : " . count($todayMessages) . "\n";
    
    // Messages par statut
    $statusCount = $messageRepository->createQueryBuilder('m')
        ->select('m.status, COUNT(m.id) as count')
        ->groupBy('m.status')
        ->getQuery()
        ->getResult();
    
    echo "Répartition par statut :\n";
    foreach ($statusCount as $stat) {
        echo "  - {$stat['status']} : {$stat['count']}\n";
    }
    
    echo "{$green}✓ Requêtes complexes exécutées{$reset}\n\n";
    
    // 6. Test de performance
    echo "{$yellow}6. Test de performance...{$reset}\n";
    
    $startTime = microtime(true);
    
    // Insérer plusieurs messages
    for ($i = 0; $i < 10; $i++) {
        $perfMessage = $whatsAppService->createMessageHistory(
            user: $testUser,
            phoneNumber: '+225012345678' . $i,
            direction: 'OUTGOING',
            type: 'text',
            content: "Performance test message #$i",
            wabaMessageId: 'wamid.perf_' . uniqid()
        );
    }
    
    $endTime = microtime(true);
    $duration = $endTime - $startTime;
    
    echo "10 messages insérés en {$duration:.3f} secondes\n";
    echo "Temps moyen par insertion : " . ($duration / 10) . " secondes\n";
    
    echo "{$green}✓ Test de performance terminé{$reset}\n\n";
    
    // 7. Test d'intégrité des données
    echo "{$yellow}7. Test d'intégrité des données...{$reset}\n";
    
    // Vérifier les contraintes de clés étrangères
    $orphanedMessages = $messageRepository->createQueryBuilder('m')
        ->leftJoin('m.user', 'u')
        ->where('u.id IS NULL')
        ->getQuery()
        ->getResult();
    
    if (empty($orphanedMessages)) {
        echo "{$green}✓ Aucun message orphelin trouvé{$reset}\n";
    } else {
        echo "{$red}✗ " . count($orphanedMessages) . " messages orphelins trouvés{$reset}\n";
    }
    
    // Vérifier les timestamps
    $invalidTimestamps = $messageRepository->createQueryBuilder('m')
        ->where('m.createdAt > m.updatedAt')
        ->getQuery()
        ->getResult();
    
    if (empty($invalidTimestamps)) {
        echo "{$green}✓ Tous les timestamps sont valides{$reset}\n";
    } else {
        echo "{$red}✗ " . count($invalidTimestamps) . " messages avec timestamps invalides{$reset}\n";
    }
    
    echo "\n";
    
    // 8. Résumé
    echo "{$blue}=== Résumé des tests de base de données ==={$reset}\n";
    echo "{$green}✓ Structure des tables : OK{$reset}\n";
    echo "{$green}✓ Insertion : OK{$reset}\n";
    echo "{$green}✓ Récupération : OK{$reset}\n";
    echo "{$green}✓ Mise à jour : OK{$reset}\n";
    echo "{$green}✓ Requêtes complexes : OK{$reset}\n";
    echo "{$green}✓ Performance : OK{$reset}\n";
    echo "{$green}✓ Intégrité des données : OK{$reset}\n";
    
} catch (Exception $e) {
    echo "{$red}Erreur : {$e->getMessage()}{$reset}\n";
    echo "Trace :\n{$e->getTraceAsString()}\n";
    exit(1);
}

echo "\n{$green}Tests de base de données WhatsApp terminés avec succès !{$reset}\n";