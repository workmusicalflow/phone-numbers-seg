<?php
/**
 * Script de débogage avancé pour les templates WhatsApp avec GraphQL
 */

// Inclure les dépendances
require_once __DIR__ . '/../vendor/autoload.php';
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Configurer le logger
$logFile = __DIR__ . '/../var/logs/graphql-debug.log';
$logger = new \Monolog\Logger('graphql-debug');
$logger->pushHandler(new \Monolog\Handler\StreamHandler($logFile, \Monolog\Logger::DEBUG));

// Utilisateur cible
$userId = 2;
$logger->info("Débogage des templates WhatsApp pour l'utilisateur $userId");

// 1. Vérifier l'utilisateur
try {
    $user = $entityManager->find(\App\Entities\User::class, $userId);
    if ($user) {
        $logger->info("Utilisateur trouvé", [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail()
        ]);
    } else {
        $logger->error("Utilisateur non trouvé dans la base Doctrine");
        
        // Vérification directe
        $conn = $entityManager->getConnection();
        $userExists = $conn->fetchOne('SELECT COUNT(*) FROM users WHERE id = ?', [$userId]);
        $logger->info("Vérification directe SQL pour l'utilisateur: " . ($userExists ? "Trouvé" : "Non trouvé"));
    }
} catch (\Exception $e) {
    $logger->error("Erreur lors de la récupération de l'utilisateur", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

// 2. Vérifier les templates directement dans la base
try {
    $conn = $entityManager->getConnection();
    $templates = $conn->fetchAllAssociative('SELECT * FROM whatsapp_user_templates WHERE user_id = ?', [$userId]);
    $logger->info("Templates dans la base SQL", [
        'count' => count($templates),
        'templates' => $templates
    ]);
} catch (\Exception $e) {
    $logger->error("Erreur lors de la vérification des templates en SQL", [
        'error' => $e->getMessage()
    ]);
}

// 3. Test du repository avec debug
try {
    // Créer manuellement le repository
    $repo = new \App\Repositories\Doctrine\WhatsApp\WhatsAppUserTemplateRepository($entityManager);
    
    // Tester la méthode findByUser
    $logger->info("Test de \App\Repositories\Doctrine\WhatsApp\WhatsAppUserTemplateRepository::findByUser");
    $templates = $repo->findByUser($userId);
    
    if (empty($templates)) {
        $logger->warning("Le repository n'a retourné aucun template");
    } else {
        $logger->info("Le repository a retourné des templates", [
            'count' => count($templates)
        ]);
        
        // Afficher le premier template
        if (isset($templates[0])) {
            $template = $templates[0];
            $logger->info("Premier template", [
                'id' => $template->getId(),
                'templateName' => $template->getTemplateName(),
                'languageCode' => $template->getLanguageCode(),
                'class' => get_class($template)
            ]);
        }
    }
} catch (\Exception $e) {
    $logger->error("Erreur avec le repository", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

// 4. Test de l'autre implémentation du repository
try {
    // Créer manuellement l'autre repository
    $otherRepo = new \App\Repositories\Doctrine\WhatsAppUserTemplateRepository($entityManager);
    
    // Tester la méthode findByUser
    $logger->info("Test de \App\Repositories\Doctrine\WhatsAppUserTemplateRepository::findByUser");
    $templates = $otherRepo->findByUser($userId);
    
    if (empty($templates)) {
        $logger->warning("L'autre repository n'a retourné aucun template");
    } else {
        $logger->info("L'autre repository a retourné des templates", [
            'count' => count($templates)
        ]);
    }
} catch (\Exception $e) {
    $logger->error("Erreur avec l'autre repository", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

// 5. Instancier et tester le resolver directement
try {
    $logger->info("Test du WhatsAppUserTemplateResolver");
    
    // Créer les dépendances
    $formatter = new \App\GraphQL\Formatters\GraphQLFormatterService($logger);
    
    // Créer le resolver
    $resolver = new \App\GraphQL\Resolvers\WhatsAppUserTemplateResolver(
        $repo,
        $formatter,
        $logger
    );
    
    // Appeler la méthode de résolution
    $result = $resolver->resolveWhatsappUserTemplates($userId);
    
    if (empty($result)) {
        $logger->warning("Le resolver n'a retourné aucun template");
    } else {
        $logger->info("Le resolver a retourné des templates", [
            'count' => count($result),
            'first' => isset($result[0]) ? $result[0] : null
        ]);
    }
} catch (\Exception $e) {
    $logger->error("Erreur avec le resolver", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

// 6. Créer une requête GraphQL directe
try {
    $logger->info("Test de requête GraphQL directe");
    
    // Construire la requête
    $query = '{
        whatsappUserTemplates(userId: "' . $userId . '", limit: 10, offset: 0) {
            id
            userId
            templateName
            languageCode
            bodyVariablesCount
            hasHeaderMedia
            isSpecialTemplate
            headerMediaUrl
            createdAt
            updatedAt
        }
    }';
    
    // Chemin vers le fichier graphql.php
    $graphqlUrl = 'http://localhost/graphql.php';
    
    // Configuration de la requête cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $graphqlUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    // Exécuter la requête
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Analyser la réponse
    $logger->info("Réponse GraphQL", [
        'httpCode' => $httpCode,
        'response' => $response ? json_decode($response, true) : null
    ]);
} catch (\Exception $e) {
    $logger->error("Erreur lors de la requête GraphQL directe", [
        'error' => $e->getMessage()
    ]);
}

// 7. Modifier temporairement le DiContainer pour forcer l'utilisation du fallback
try {
    $fallbackPath = __DIR__ . '/../public/fallback-whatsapp-templates.php';
    $originalContent = file_get_contents($fallbackPath);
    
    $logger->info("Test final - Vérification du fichier de fallback", [
        'path' => $fallbackPath,
        'exists' => file_exists($fallbackPath)
    ]);
    
    // Modifier le fichier pour produire des données de test spécifiques pour l'utilisateur 2
    $newContent = preg_replace(
        '/\$fallbackTemplates = \[.*?\];/s',
        '$fallbackTemplates = [
            [
                \'id\' => \'999\',
                \'userId\' => (string)$userId,
                \'templateName\' => \'debug_template\',
                \'languageCode\' => \'fr\',
                \'bodyVariablesCount\' => 1,
                \'hasHeaderMedia\' => false,
                \'isSpecialTemplate\' => true,
                \'headerMediaUrl\' => null,
                \'createdAt\' => date(\'Y-m-d H:i:s\'),
                \'updatedAt\' => date(\'Y-m-d H:i:s\')
            ]
        ];',
        $originalContent
    );
    
    // Sauvegarder une copie du fichier original
    file_put_contents($fallbackPath . '.backup', $originalContent);
    
    // Appliquer les modifications
    file_put_contents($fallbackPath, $newContent);
    $logger->info("Fichier de fallback modifié pour le test");
    
    // Faire un appel direct au fichier fallback
    $fallbackUrl = 'http://localhost/fallback-whatsapp-templates.php?userId=' . $userId . '&_=' . time();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fallbackUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $fallbackResponse = curl_exec($ch);
    $fallbackHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $logger->info("Réponse de l'API fallback", [
        'httpCode' => $fallbackHttpCode,
        'response' => $fallbackResponse ? json_decode($fallbackResponse, true) : null
    ]);
    
    // Restaurer le fichier original
    file_put_contents($fallbackPath, $originalContent);
    $logger->info("Fichier de fallback restauré");
} catch (\Exception $e) {
    $logger->error("Erreur lors du test du fallback", [
        'error' => $e->getMessage()
    ]);
    
    // Tenter de restaurer le fichier original en cas d'erreur
    if (isset($fallbackPath) && isset($originalContent)) {
        file_put_contents($fallbackPath, $originalContent);
        $logger->info("Fichier de fallback restauré après erreur");
    }
}

// 8. Créer un template de test directement via SQL
try {
    $logger->info("Création d'un template de test via SQL directe");
    
    // Construire un nom unique pour éviter les doublons
    $templateName = 'debug_template_' . time();
    
    // Insérer le template
    $conn = $entityManager->getConnection();
    $conn->executeStatement('
        INSERT INTO whatsapp_user_templates 
        (user_id, template_name, language_code, body_variables_count, has_header_media, is_special_template, created_at, updated_at) 
        VALUES 
        (?, ?, ?, ?, ?, ?, ?, ?)
    ', [
        $userId,
        $templateName,
        'fr',
        1,
        0,
        0,
        date('Y-m-d H:i:s'),
        date('Y-m-d H:i:s')
    ]);
    
    $logger->info("Template de test inséré avec succès", [
        'templateName' => $templateName
    ]);
    
    // Vérifier les templates à nouveau
    $templates = $conn->fetchAllAssociative('SELECT * FROM whatsapp_user_templates WHERE user_id = ?', [$userId]);
    $logger->info("Templates après insertion", [
        'count' => count($templates),
        'templates' => $templates
    ]);
} catch (\Exception $e) {
    $logger->error("Erreur lors de la création du template de test", [
        'error' => $e->getMessage()
    ]);
}

$logger->info("Débogage terminé, consultez le fichier de log à {$logFile}");
echo "Débogage terminé, consultez le fichier de log à {$logFile}\n";