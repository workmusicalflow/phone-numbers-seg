<?php

/**
 * Script pour synchroniser les templates Meta avec l'utilisateur admin
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap-doctrine.php';

// Créer le conteneur DI
$container = new App\GraphQL\DIContainer();
$entityManager = $container->get(Doctrine\ORM\EntityManagerInterface::class);
$connection = $entityManager->getConnection();
$userRepo = $container->get(App\Repositories\Interfaces\UserRepositoryInterface::class);

echo "Synchronisation des templates pour l'admin\n";
echo "=========================================\n\n";

// 1. Trouver l'utilisateur admin
$adminUser = $userRepo->findOneBy(['username' => 'admin']);
if (!$adminUser) {
    throw new \Exception("Utilisateur admin non trouvé");
}

echo "Utilisateur admin trouvé: {$adminUser->getUsername()} (ID: {$adminUser->getId()})\n\n";

// 2. Récupérer tous les templates approuvés
$result = $connection->executeQuery('
    SELECT id, name, language, body_text, header_format
    FROM whatsapp_templates 
    WHERE is_active = 1 
    AND status = "APPROVED"
');

$templates = $result->fetchAllAssociative();
echo "Templates approuvés à synchroniser: " . count($templates) . "\n\n";

// 3. Créer les associations user_templates
$synced = 0;
foreach ($templates as $template) {
    // Vérifier si l'association existe déjà
    $result = $connection->executeQuery('
        SELECT COUNT(*) as count
        FROM whatsapp_user_templates 
        WHERE template_name = ? 
        AND language_code = ?
        AND user_id = ?
    ', [$template['name'], $template['language'], $adminUser->getId()]);
    
    $exists = $result->fetchOne() > 0;
    
    if (!$exists) {
        // Compter les variables dans le body_text
        preg_match_all('/\{\{(\d+)\}\}/', $template['body_text'], $matches);
        $variableCount = count(array_unique($matches[1]));
        
        // Déterminer si le template a un média dans l'en-tête
        $hasHeaderMedia = in_array($template['header_format'], ['IMAGE', 'VIDEO', 'DOCUMENT']);
        
        // Insérer la nouvelle association
        $connection->executeStatement('
            INSERT INTO whatsapp_user_templates 
            (template_name, language_code, body_variables_count, has_header_media, is_special_template, user_id, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, datetime("now"), datetime("now"))
        ', [
            $template['name'],
            $template['language'],
            $variableCount,
            $hasHeaderMedia ? 1 : 0,
            0, // is_special_template
            $adminUser->getId()
        ]);
        
        echo "✓ Synchronisé: {$template['name']} ({$template['language']})\n";
        $synced++;
    } else {
        echo "- Déjà existant: {$template['name']} ({$template['language']})\n";
    }
}

echo "\n{$synced} templates synchronisés avec succès.\n\n";

// 4. Vérifier les templates de l'admin
echo "Templates disponibles pour l'admin:\n";
echo "----------------------------------\n";
$result = $connection->executeQuery('
    SELECT ut.*, t.category, t.status, t.body_text
    FROM whatsapp_user_templates ut
    JOIN whatsapp_templates t ON ut.template_name = t.name AND ut.language_code = t.language
    WHERE ut.user_id = ?
    ORDER BY ut.template_name, ut.language_code
', [$adminUser->getId()]);

$userTemplates = $result->fetchAllAssociative();
foreach ($userTemplates as $template) {
    echo "- {$template['template_name']} ({$template['language_code']}) - {$template['category']}\n";
    echo "  Variables: {$template['body_variables_count']}\n";
    echo "  Body: " . substr($template['body_text'], 0, 50) . "...\n\n";
}

echo "Total: " . count($userTemplates) . " templates disponibles pour l'admin.\n";