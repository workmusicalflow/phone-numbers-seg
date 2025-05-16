<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

echo "=== Test des tables WhatsApp existantes ===\n\n";

try {
    // Connexion à la base de données
    $connectionParams = [
        'driver' => 'pdo_sqlite',
        'path' => __DIR__ . '/../var/database.sqlite',
    ];
    
    $connection = DriverManager::getConnection($connectionParams);
    
    // 1. Tester whatsapp_templates
    echo "1. Test table whatsapp_templates:\n";
    
    $insertTemplate = "INSERT INTO whatsapp_templates (name, language, category, status, components, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, ?, datetime('now'), datetime('now'))";
    
    $connection->executeStatement($insertTemplate, [
        'welcome_fr_' . time(),
        'fr',
        'MARKETING',
        'APPROVED',
        json_encode([['type' => 'BODY', 'text' => 'Bienvenue {{1}}!']])
    ]);
    
    echo "   ✓ Template inséré\n";
    
    // 2. Tester whatsapp_message_history
    echo "\n2. Test table whatsapp_message_history:\n";
    
    $insertHistory = "INSERT INTO whatsapp_message_history 
                     (oracle_user_id, phone_number, direction, type, status, waba_message_id, timestamp, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'), datetime('now'))";
    
    $connection->executeStatement($insertHistory, [
        1, // oracle_user_id
        '+22507000001',
        'OUTBOUND',
        'TEXT',
        'DELIVERED',
        'wamid_' . uniqid()
    ]);
    
    echo "   ✓ Message historique inséré\n";
    
    // 3. Tester whatsapp_queue
    echo "\n3. Test table whatsapp_queue:\n";
    
    $insertQueue = "INSERT INTO whatsapp_queue 
                   (oracle_user_id, recipient_phone, message_type, message_content, 
                    priority, status, attempts, maxAttempts, scheduled_at, createdAt, updatedAt)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'), datetime('now'))";
    
    $connection->executeStatement($insertQueue, [
        1, // oracle_user_id
        '+22507000002',
        'TEXT',
        json_encode(['text' => 'Message de test']),
        2, // priority
        'PENDING',
        0, // attempts
        3  // maxAttempts
    ]);
    
    echo "   ✓ Message en file d'attente inséré\n";
    
    // 4. Afficher les statistiques
    echo "\n=== Statistiques ===\n";
    
    $stats = [];
    $tables = ['whatsapp_templates', 'whatsapp_message_history', 'whatsapp_queue', 'whatsapp_user_templates'];
    
    foreach ($tables as $table) {
        $count = $connection->fetchOne("SELECT COUNT(*) FROM $table");
        echo "- $table: $count enregistrements\n";
    }
    
    // 5. Afficher quelques données de test
    echo "\n=== Données de test créées ===\n";
    
    echo "\nTemplates:\n";
    $templates = $connection->fetchAllAssociative("SELECT * FROM whatsapp_templates LIMIT 3");
    foreach ($templates as $template) {
        echo "- {$template['name']} ({$template['language']}) - Status: {$template['status']}\n";
    }
    
    echo "\nHistorique des messages:\n";
    $history = $connection->fetchAllAssociative("SELECT * FROM whatsapp_message_history LIMIT 3");
    foreach ($history as $msg) {
        echo "- {$msg['direction']} - {$msg['phone_number']} - Status: {$msg['status']}\n";
    }
    
    echo "\nFile d'attente:\n";
    $queue = $connection->fetchAllAssociative("SELECT * FROM whatsapp_queue WHERE status = 'PENDING' LIMIT 3");
    foreach ($queue as $item) {
        echo "- {$item['recipient_phone']} - Type: {$item['message_type']} - Priority: {$item['priority']}\n";
    }
    
} catch (Exception $e) {
    echo "\nErreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test terminé ===\n";