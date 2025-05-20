<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Test GraphQL pour les templates WhatsApp

$endpoint = 'http://localhost:8000/graphql.php';

// Faire un login d'abord
$loginQuery = [
    'query' => 'mutation Login($username: String!, $password: String!) {
        login(username: $username, password: $password)
    }',
    'variables' => [
        'username' => 'admin@example.com',
        'password' => 'admin123'
    ]
];

// Login
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginQuery));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$loginResponse = curl_exec($ch);
if ($loginResponse === false) {
    echo "Erreur cURL: " . curl_error($ch) . "\n";
    exit(1);
}

$loginData = json_decode($loginResponse, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Erreur JSON: " . json_last_error_msg() . "\n";
    echo "Réponse brute:\n" . $loginResponse . "\n";
    exit(1);
}

// Après le login, on doit récupérer le token via une autre query
if ($loginData['data']['login'] !== true) {
    echo "Erreur de connexion:\n";
    print_r($loginData);
    echo "\nRéponse complète:\n" . $loginResponse . "\n";
    exit(1);
}

echo "Login successful\n";

// Maintenant on doit récupérer le token de l'utilisateur actuel
$meQuery = [
    'query' => 'query Me {
        me {
            id
            email
            firstname
            isAdmin
            token
        }
    }'
];

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($meQuery));
$meResponse = curl_exec($ch);
$meData = json_decode($meResponse, true);

if (!isset($meData['data']['me']['token'])) {
    echo "Erreur récupération utilisateur:\n";
    print_r($meData);
    exit(1);
}

$token = $meData['data']['me']['token'];
echo "Connecté avec succès. Token: " . substr($token, 0, 20) . "...\n\n";

// Récupérer les templates
$templatesQuery = [
    'query' => 'query GetUserWhatsAppTemplates {
        getUserWhatsAppTemplates {
            id
            name
            language
            category
            status
            bodyText
            headerFormat
            headerText
            footerText
            bodyVariablesCount
            hasHeaderMedia
            isActive
            isGlobal
            metaTemplateId
        }
    }'
];

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($templatesQuery));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code HTTP: $httpCode\n";
echo "Réponse:\n";

$data = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    if (isset($data['errors'])) {
        echo "Erreurs GraphQL:\n";
        foreach ($data['errors'] as $error) {
            echo "- " . $error['message'] . "\n";
            if (isset($error['trace'])) {
                echo "  Trace: " . json_encode($error['trace']) . "\n";
            }
        }
    }
    
    if (isset($data['data']['getUserWhatsAppTemplates'])) {
        echo "\nTemplates WhatsApp:\n";
        foreach ($data['data']['getUserWhatsAppTemplates'] as $template) {
            echo "\n- " . $template['name'] . " (" . $template['language'] . ")\n";
            echo "  Catégorie: " . $template['category'] . "\n";
            echo "  Statut: " . $template['status'] . "\n";
            echo "  Global: " . ($template['isGlobal'] ? 'Oui' : 'Non') . "\n";
            echo "  Body: " . substr($template['bodyText'], 0, 60) . "...\n";
        }
    } else {
        echo "Aucun template trouvé ou erreur de structure.\n";
        print_r($data);
    }
} else {
    echo "Erreur JSON:\n";
    echo $response . "\n";
}