<?php

/**
 * Script pour vérifier tous les templates dans les deux tables
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap-doctrine.php';

// Créer le conteneur DI
$container = new App\GraphQL\DIContainer();
$entityManager = $container->get(Doctrine\ORM\EntityManagerInterface::class);
$connection = $entityManager->getConnection();

echo "Vérification des templates WhatsApp\n";
echo "==================================\n\n";

// 1. Vérifier la table whatsapp_templates
echo "1. Templates approuvés par Meta (whatsapp_templates):\n";
echo "---------------------------------------------------\n";
$result = $connection->executeQuery('
    SELECT id, name, language, category, status, is_active, meta_template_id, body_text 
    FROM whatsapp_templates 
    ORDER BY name, language
');

$templates = $result->fetchAllAssociative();
foreach ($templates as $template) {
    echo "ID: {$template['id']}\n";
    echo "Name: {$template['name']}\n";
    echo "Language: {$template['language']}\n";
    echo "Category: {$template['category']}\n";
    echo "Status: {$template['status']}\n";
    echo "Active: " . ($template['is_active'] ? 'Yes' : 'No') . "\n";
    echo "Meta ID: {$template['meta_template_id']}\n";
    echo "Body: " . substr($template['body_text'], 0, 50) . "...\n";
    echo "---\n";
}
echo "Total: " . count($templates) . " templates\n\n";

// 2. Vérifier la table whatsapp_user_templates
echo "2. Templates utilisateur (whatsapp_user_templates):\n";
echo "-------------------------------------------------\n";
$result = $connection->executeQuery('
    SELECT ut.*, u.username 
    FROM whatsapp_user_templates ut
    JOIN users u ON ut.user_id = u.id
    ORDER BY ut.template_name, ut.language_code
');

$userTemplates = $result->fetchAllAssociative();
foreach ($userTemplates as $template) {
    echo "ID: {$template['id']}\n";
    echo "User: {$template['username']} (ID: {$template['user_id']})\n";
    echo "Template: {$template['template_name']}\n";
    echo "Language: {$template['language_code']}\n";
    echo "Variables: {$template['body_variables_count']}\n";
    echo "Has Media: " . ($template['has_header_media'] ? 'Yes' : 'No') . "\n";
    echo "Special: " . ($template['is_special_template'] ? 'Yes' : 'No') . "\n";
    echo "---\n";
}
echo "Total: " . count($userTemplates) . " user templates\n\n";

// 3. Trouver l'utilisateur admin
echo "3. Utilisateur admin:\n";
echo "--------------------\n";
$result = $connection->executeQuery('
    SELECT id, username, isAdmin 
    FROM users 
    WHERE isAdmin = 1
');

$adminUsers = $result->fetchAllAssociative();
foreach ($adminUsers as $admin) {
    echo "ID: {$admin['id']}, Username: {$admin['username']}\n";
}

// 4. Templates manquants pour l'admin
echo "\n4. Templates manquants pour l'admin:\n";
echo "-----------------------------------\n";
$result = $connection->executeQuery('
    SELECT t.* 
    FROM whatsapp_templates t
    WHERE t.is_active = 1 
    AND t.status = "APPROVED"
    AND NOT EXISTS (
        SELECT 1 
        FROM whatsapp_user_templates ut 
        WHERE ut.template_name = t.name 
        AND ut.language_code = t.language
        AND ut.user_id = (SELECT id FROM users WHERE username = "admin")
    )
');

$missingTemplates = $result->fetchAllAssociative();
foreach ($missingTemplates as $template) {
    echo "- {$template['name']} ({$template['language']})\n";
}
echo "Total manquants: " . count($missingTemplates) . "\n";