<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

echo "=== Préparation de la migration WhatsApp ===\n\n";

try {
    // Connexion à la base de données
    $connectionParams = [
        'driver' => 'pdo_sqlite',
        'path' => __DIR__ . '/../../var/database.sqlite',
    ];
    
    $connection = DriverManager::getConnection($connectionParams);
    
    // 1. Vérifier les utilisateurs disponibles
    echo "1. Utilisateurs disponibles dans la base:\n";
    $usersQuery = "SELECT id, email FROM users";
    $users = $connection->fetchAllAssociative($usersQuery);
    
    foreach ($users as $user) {
        echo "   - ID: {$user['id']}, Email: {$user['email']}\n";
    }
    
    // 2. Analyser les numéros de téléphone uniques dans whatsapp_messages
    echo "\n2. Analyse des numéros de téléphone dans whatsapp_messages:\n";
    $phoneNumbersQuery = "
        SELECT DISTINCT sender as phone_number FROM whatsapp_messages WHERE sender IS NOT NULL
        UNION
        SELECT DISTINCT recipient as phone_number FROM whatsapp_messages WHERE recipient IS NOT NULL
    ";
    $phoneNumbers = $connection->fetchAllAssociative($phoneNumbersQuery);
    
    echo "   Nombre de numéros uniques: " . count($phoneNumbers) . "\n";
    echo "   Exemples:\n";
    $i = 0;
    foreach ($phoneNumbers as $phone) {
        echo "   - {$phone['phone_number']}\n";
        if (++$i >= 5) break; // Afficher seulement 5 exemples
    }
    
    // 3. Vérifier les contacts existants
    echo "\n3. Contacts existants dans la base:\n";
    $contactsQuery = "SELECT COUNT(*) as count FROM contacts";
    $contactCount = $connection->fetchOne($contactsQuery);
    echo "   Nombre total de contacts: $contactCount\n";
    
    // Exemples de contacts
    $sampleContactsQuery = "SELECT id, phone_number, name, user_id FROM contacts LIMIT 5";
    $sampleContacts = $connection->fetchAllAssociative($sampleContactsQuery);
    
    if (!empty($sampleContacts)) {
        echo "   Exemples de contacts:\n";
        foreach ($sampleContacts as $contact) {
            echo "   - ID: {$contact['id']}, Phone: {$contact['phone_number']}, Name: {$contact['name']}, User ID: {$contact['user_id']}\n";
        }
    }
    
    // 4. Proposer une stratégie de mapping
    echo "\n4. Stratégie de mapping proposée:\n";
    echo "   - Utiliser l'utilisateur admin (ID: 1) par défaut pour oracle_user_id\n";
    echo "   - Tenter de matcher les numéros de téléphone avec les contacts existants\n";
    echo "   - Créer des nouveaux contacts si nécessaire\n";
    
    // 5. Créer un fichier de configuration pour la migration
    $config = [
        'default_user_id' => 1, // ID de l'utilisateur admin
        'create_missing_contacts' => true,
        'phone_number_patterns' => [
            'whatsapp_business' => ['/^225\d{10}$/'], // Patterns pour identifier les numéros business
        ]
    ];
    
    $configFile = __DIR__ . '/../../var/whatsapp_migration_config.json';
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    echo "\n5. Fichier de configuration créé: $configFile\n";
    
    // 6. Recommandations
    echo "\n=== Recommandations ===\n";
    echo "1. Vérifier le fichier de configuration et ajuster si nécessaire\n";
    echo "2. Faire une sauvegarde complète de la base de données avant la migration\n";
    echo "3. Exécuter le script de migration en mode test d'abord\n";
    echo "4. Vérifier les résultats avant de supprimer l'ancienne table\n";
    
} catch (Exception $e) {
    echo "\nErreur: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== Préparation terminée ===\n";