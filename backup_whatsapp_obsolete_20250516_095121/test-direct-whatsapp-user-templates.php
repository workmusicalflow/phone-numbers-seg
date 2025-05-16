<?php

// Insérons un template de test directement dans la base de données pour l'utilisateur 2
// Cela aidera à vérifier si le problème vient de l'insertion ou de la récupération

// Inclure les dépendances
require_once __DIR__ . '/../vendor/autoload.php';
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Insérer un nouveau template pour l'utilisateur 2 (testuser)
echo "Insertion d'un nouveau template de test pour l'utilisateur 2...\n";

// Vérifier d'abord s'il existe déjà un template similaire
$templateName = 'test_template_' . date('YmdHis');
$conn = $entityManager->getConnection();

$conn->executeStatement('
    INSERT INTO whatsapp_user_templates 
    (user_id, template_name, language_code, body_variables_count, has_header_media, is_special_template, created_at, updated_at) 
    VALUES 
    (?, ?, ?, ?, ?, ?, ?, ?)
', [
    2, // user_id pour testuser
    $templateName,
    'fr',
    1, // body_variables_count
    0, // has_header_media
    0, // is_special_template
    date('Y-m-d H:i:s'),
    date('Y-m-d H:i:s')
]);

echo "Template inséré avec succès: $templateName\n";

// Vérifier tous les templates de l'utilisateur 2
echo "\nTous les templates de l'utilisateur 2:\n";
$stmt = $conn->prepare('
    SELECT * FROM whatsapp_user_templates 
    WHERE user_id = 2
');
$stmt->execute();
$templates = $stmt->fetchAllAssociative();

foreach ($templates as $template) {
    echo "- {$template['template_name']} (ID: {$template['id']})\n";
}

echo "\nNombre total de templates pour l'utilisateur 2: " . count($templates) . "\n";

// Créer un lien symbolique vers le fichier fallback pour permettre de tester facilement
echo "\nCréation d'un fichier de log pour observer les requêtes fallback...\n";

$fallbackLogFile = __DIR__ . '/../var/logs/fallback-templates.log';
file_put_contents($fallbackLogFile, "Log de l'API fallback - " . date('Y-m-d H:i:s') . "\n");

// Modifications temporaires pour debug
$fallbackFilePath = __DIR__ . '/../public/fallback-whatsapp-templates.php';
$originalContent = file_get_contents($fallbackFilePath);
$newContent = str_replace(
    'error_log("Fallback WhatsApp Templates API - Request: userId=$userId, limit=$limit, offset=$offset, timestamp=$timestamp");',
    'file_put_contents("' . $fallbackLogFile . '", "Requête reçue: userId=$userId, limit=$limit, offset=$offset, timestamp=$timestamp à " . date("Y-m-d H:i:s") . "\n", FILE_APPEND);',
    $originalContent
);

// Écriture du fichier modifié
file_put_contents($fallbackFilePath, $newContent);

echo "Le fichier fallback a été modifié pour journaliser les requêtes dans: $fallbackLogFile\n";
echo "N'oubliez pas de tester l'URL: http://localhost/fallback-whatsapp-templates.php?userId=2&_=" . time() . "\n";

echo "\nTest terminé. Vérifiez les requêtes GraphQL dans le navigateur (Network tab) et le fichier de log.\n";