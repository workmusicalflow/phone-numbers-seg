<?php
/**
 * Script de correction d'urgence pour les templates WhatsApp utilisateur
 */

// Inclure l'autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Récupérer l'entityManager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

echo "Analyse et correction des templates WhatsApp utilisateur...\n";

// Utilisateur cible (testuser)
$userId = 2;

// 1. Vérifier les données existantes
echo "1. Vérification des templates existants pour l'utilisateur ID: $userId\n";

$conn = $entityManager->getConnection();
$existingTemplates = $conn->fetchAllAssociative('SELECT * FROM whatsapp_user_templates WHERE user_id = ?', [$userId]);

echo "   Templates trouvés: " . count($existingTemplates) . "\n";
foreach ($existingTemplates as $i => $template) {
    echo "   #" . ($i+1) . ": " . $template['template_name'] . " (ID: " . $template['id'] . ")\n";
}

// 2. Vérifier l'existence de templates standards
echo "\n2. Vérification des templates standards WhatsApp\n";
$standardTemplates = $conn->fetchAllAssociative('SELECT * FROM whatsapp_templates LIMIT 5');
echo "   Templates standards trouvés: " . count($standardTemplates) . "\n";

// 3. Ajouter un nouveau template de test si nécessaire
echo "\n3. Création d'un nouveau template de test\n";
$newTemplateName = "emergency_template_" . date('YmdHis');

try {
    $conn->executeStatement('
        INSERT INTO whatsapp_user_templates 
        (user_id, template_name, language_code, body_variables_count, has_header_media, is_special_template, created_at, updated_at) 
        VALUES 
        (?, ?, ?, ?, ?, ?, ?, ?)
    ', [
        $userId,
        $newTemplateName,
        'fr',
        1,
        0,
        0,
        date('Y-m-d H:i:s'),
        date('Y-m-d H:i:s')
    ]);
    
    echo "   Template créé avec succès: $newTemplateName\n";
} catch (\Exception $e) {
    echo "   Erreur lors de la création du template: " . $e->getMessage() . "\n";
}

// 4. Modification du fichier de fallback pour assurer qu'il fonctionne correctement
echo "\n4. Préparation du fichier de fallback...\n";

$fallbackPath = __DIR__ . '/../public/fallback-whatsapp-templates.php';
if (!file_exists($fallbackPath)) {
    echo "   ERREUR: Fichier fallback introuvable à $fallbackPath\n";
} else {
    echo "   Fichier fallback trouvé\n";
    
    // Sauvegarde du fichier original
    $originalContent = file_get_contents($fallbackPath);
    file_put_contents($fallbackPath . '.backup', $originalContent);
    echo "   Sauvegarde du fichier original créée\n";
    
    // Modifier le fichier pour inclure des journalisations avancées
    $modifiedContent = str_replace(
        'header("Content-Type: application/json");',
        'header("Content-Type: application/json");

// Ajout d\'une journalisation avancée pour débogage
$debugFile = __DIR__ . "/../var/logs/fallback-debug.log";
file_put_contents($debugFile, date("Y-m-d H:i:s") . " - Accès au fallback: " . $_SERVER["REMOTE_ADDR"] . ", UserID: " . ($_GET["userId"] ?? "non défini") . "\n", FILE_APPEND);',
        $originalContent
    );
    
    // Assurer que la requête est journalisée avant traitement
    $modifiedContent = str_replace(
        'error_log("Fallback WhatsApp Templates API - Request: userId=$userId, limit=$limit, offset=$offset, timestamp=$timestamp");',
        'error_log("Fallback WhatsApp Templates API - Request: userId=$userId, limit=$limit, offset=$offset, timestamp=$timestamp");
file_put_contents($debugFile, date("Y-m-d H:i:s") . " - Requête traitée: userId=$userId, limit=$limit, offset=$offset, timestamp=$timestamp\n", FILE_APPEND);',
        $modifiedContent
    );
    
    // Ajouter notre template d'urgence
    $modifiedContent = preg_replace(
        '/\$fallbackTemplates = \[\s*\[/',
        '$fallbackTemplates = [
    [
        \'id\' => \'999\',
        \'userId\' => (string)$userId,
        \'templateName\' => \'' . $newTemplateName . '\',
        \'languageCode\' => \'fr\',
        \'bodyVariablesCount\' => 1,
        \'hasHeaderMedia\' => false,
        \'isSpecialTemplate\' => false,
        \'headerMediaUrl\' => null,
        \'createdAt\' => date(\'Y-m-d H:i:s\', strtotime("-1 hour")),
        \'updatedAt\' => date(\'Y-m-d H:i:s\', strtotime("-1 hour"))
    ],
    [',
        $modifiedContent
    );
    
    // Sauvegarder le fichier modifié
    file_put_contents($fallbackPath, $modifiedContent);
    echo "   Fichier fallback mis à jour avec le template d'urgence\n";
}

// 5. Créer un fichier de test direct
echo "\n5. Création d'un fichier de test direct pour les templates...\n";

$testFilePath = __DIR__ . '/../public/emergency-whatsapp-templates.php';
$testFileContent = '<?php
/**
 * Point d\'API d\'urgence pour les templates WhatsApp
 * 
 * Ce script fournit une API spéciale qui garantit de retourner des données
 * valides pour l\'interface Contacts et WhatsApp Templates.
 */

// Configuration d\'accès CORS pour permettre les requêtes du frontend
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json");

// Entêtes pour empêcher la mise en cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Journalisation
$logFile = __DIR__ . "/../var/logs/emergency-templates.log";
file_put_contents($logFile, date("Y-m-d H:i:s") . " - Accès à emergency-templates: " . $_SERVER["REMOTE_ADDR"] . ", UserID: " . ($_GET["userId"] ?? "non défini") . "\n", FILE_APPEND);

// Analyser les paramètres
$userId = isset($_GET["userId"]) ? $_GET["userId"] : "2";
$limit = isset($_GET["limit"]) ? (int)$_GET["limit"] : 10;
$offset = isset($_GET["offset"]) ? (int)$_GET["offset"] : 0;
$timestamp = isset($_GET["_"]) ? $_GET["_"] : "";

// Journaliser les paramètres
file_put_contents($logFile, date("Y-m-d H:i:s") . " - Paramètres: userId=$userId, limit=$limit, offset=$offset, timestamp=$timestamp\n", FILE_APPEND);

// Templates d\'urgence garantis de fonctionner
$emergencyTemplates = [
    [
        "id" => "e1",
        "userId" => $userId,
        "templateName" => "emergency_template_' . date('YmdHis') . '",
        "languageCode" => "fr",
        "bodyVariablesCount" => 1,
        "hasHeaderMedia" => false,
        "isSpecialTemplate" => false,
        "headerMediaUrl" => null,
        "createdAt" => date("Y-m-d H:i:s", strtotime("-1 hour")),
        "updatedAt" => date("Y-m-d H:i:s", strtotime("-1 hour"))
    ],
    [
        "id" => "e2",
        "userId" => $userId,
        "templateName" => "connection_check",
        "languageCode" => "fr",
        "bodyVariablesCount" => 0,
        "hasHeaderMedia" => false,
        "isSpecialTemplate" => false,
        "headerMediaUrl" => null,
        "createdAt" => date("Y-m-d H:i:s", strtotime("-2 days")),
        "updatedAt" => date("Y-m-d H:i:s", strtotime("-2 days"))
    ]
];

// Construction de la réponse au format attendu
$response = [
    "data" => [
        "whatsappUserTemplates" => $emergencyTemplates
    ]
];

// Journaliser la réponse
file_put_contents($logFile, date("Y-m-d H:i:s") . " - Réponse: " . count($emergencyTemplates) . " templates\n", FILE_APPEND);

// Retourner la réponse au format JSON
echo json_encode($response);
';

file_put_contents($testFilePath, $testFileContent);
echo "   Fichier d'urgence créé: $testFilePath\n";

// 6. Instructions pour l'utilisateur
echo "\nINSTRUCTIONS IMPORTANTES POUR RÉSOUDRE LE PROBLÈME:\n";
echo "-----------------------------------------------------\n";
echo "1. Ouvrez le fichier frontend/src/stores/whatsappUserTemplateStore.ts\n";
echo "2. Modifiez la méthode fetchTemplates, remplacez l'URL fallback par:\n";
echo "   /emergency-whatsapp-templates.php?userId=\${userId}&limit=\${limit}&offset=\${offset}&_=\${timestamp}\n";
echo "3. Accédez à http://localhost:5173/contacts puis cliquez sur un contact\n";
echo "4. Le bouton 'Envoyer un template WhatsApp' devrait maintenant fonctionner\n";
echo "\nNOTE: Cette solution est temporaire et permet de débloquer l'interface\n";
echo "      pendant que vous continuez à résoudre le problème sous-jacent.\n";
echo "-----------------------------------------------------\n";

echo "\nOpération terminée.\n";