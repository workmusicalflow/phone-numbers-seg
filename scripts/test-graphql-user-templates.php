<?php

/**
 * Test des templates WhatsApp via GraphQL
 */

require_once __DIR__ . '/../vendor/autoload.php';

$baseUrl = 'http://localhost:8000';
$graphqlUrl = $baseUrl . '/graphql.php';

echo "Test des templates WhatsApp via GraphQL\n";
echo "======================================\n\n";

// 1. Connexion
echo "1. Connexion en tant qu'admin...\n";
$loginQuery = [
    'query' => '
        mutation {
            login(username: "admin", password: "admin123")
        }
    '
];

$ch = curl_init($graphqlUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginQuery));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$result = json_decode($response, true);

if (isset($result['errors']) || !isset($result['data']['login'])) {
    echo "Erreur de connexion\n";
    var_dump($result);
    exit(1);
}

// Si login retourne un booléen ou un token directement
$loginResult = $result['data']['login'];
if (is_bool($loginResult)) {
    // Si c'est un booléen, nous devons récupérer le token différemment
    // Utilisons une session ou un cookie
    $token = 'test-token'; // À remplacer par la vraie méthode
} else {
    $token = $loginResult;
}
echo "✓ Connecté avec token\n\n";

// 2. Récupération des templates
echo "2. Récupération des templates utilisateur...\n";
$templatesQuery = [
    'query' => '
        query {
            getWhatsAppUserTemplates {
                id
                name
                language
                category
                status
                bodyText
                headerFormat
                isActive
            }
        }
    '
];

$ch = curl_init($graphqlUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($templatesQuery));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$result = json_decode($response, true);

if (isset($result['errors'])) {
    echo "Erreur GraphQL:\n";
    foreach ($result['errors'] as $error) {
        echo "- " . $error['message'] . "\n";
    }
} else if (isset($result['data']['getWhatsAppUserTemplates'])) {
    $templates = $result['data']['getWhatsAppUserTemplates'];
    echo "✓ " . count($templates) . " templates trouvés:\n";
    
    foreach ($templates as $template) {
        echo "\n- {$template['name']} ({$template['language']})\n";
        echo "  Catégorie: {$template['category']}\n";
        echo "  Statut: {$template['status']}\n";
        echo "  Format header: {$template['headerFormat']}\n";
        echo "  Body: " . substr($template['bodyText'], 0, 50) . "...\n";
    }
} else {
    echo "Réponse inattendue:\n";
    var_dump($result);
}

echo "\nTest terminé.\n";