<?php

// Inclure le bootstrap Doctrine
require_once __DIR__ . '/../src/bootstrap-doctrine.php';
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Vérifier les templates WhatsApp utilisateur
echo "Vérification des templates WhatsApp utilisateur...\n";

// Requête directe SQL
$stmt = $entityManager->getConnection()->prepare('
    SELECT * FROM whatsapp_user_templates LIMIT 10
');
$stmt->execute();
$results = $stmt->fetchAllAssociative();

echo "Nombre de templates trouvés : " . count($results) . "\n";

if (count($results) > 0) {
    echo "Premier template :\n";
    print_r($results[0]);
} else {
    echo "Aucun template trouvé\n";
    
    // Vérifier la structure de la table
    echo "\nVérification de la structure de la table :\n";
    $stmt = $entityManager->getConnection()->prepare('
        PRAGMA table_info(whatsapp_user_templates)
    ');
    $stmt->execute();
    $columns = $stmt->fetchAllAssociative();
    print_r($columns);
    
    // Vérifier l'existence d'autres tables liées à WhatsApp
    echo "\nTables liées à WhatsApp :\n";
    $stmt = $entityManager->getConnection()->prepare("
        SELECT name FROM sqlite_master 
        WHERE type='table' AND name LIKE 'whatsapp%'
    ");
    $stmt->execute();
    $tables = $stmt->fetchAllAssociative();
    print_r($tables);
}

// Compter les utilisateurs existants
echo "\nVérification des utilisateurs :\n";
$stmt = $entityManager->getConnection()->prepare('
    SELECT COUNT(*) as count FROM users
');
$stmt->execute();
$userCount = $stmt->fetchAssociative();
echo "Nombre d'utilisateurs : " . $userCount['count'] . "\n";

// Compter les templates WhatsApp
echo "\nCompter les templates standard WhatsApp :\n";
$stmt = $entityManager->getConnection()->prepare('
    SELECT COUNT(*) as count FROM whatsapp_templates
');
$stmt->execute();
$templateCount = $stmt->fetchAssociative();
echo "Nombre de templates standards : " . $templateCount['count'] . "\n";

// Compter les templates pour le userId 2 spécifiquement
echo "\nCompter les templates pour l'utilisateur ID 2 :\n";
$stmt = $entityManager->getConnection()->prepare('
    SELECT COUNT(*) as count FROM whatsapp_user_templates WHERE user_id = 2
');
$stmt->execute();
$templatesForUser2 = $stmt->fetchAssociative();
echo "Templates pour l'utilisateur ID 2 : " . $templatesForUser2['count'] . "\n";

// Vérifier tous les utilisateurs qui ont des templates
echo "\nUtilisateurs ayant des templates :\n";
$stmt = $entityManager->getConnection()->prepare('
    SELECT user_id, COUNT(*) as count FROM whatsapp_user_templates GROUP BY user_id
');
$stmt->execute();
$userTemplates = $stmt->fetchAllAssociative();
print_r($userTemplates);