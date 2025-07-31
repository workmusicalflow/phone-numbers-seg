<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Configuration
$endpoint = 'http://localhost:8000/graphql.php';
$username = 'admin';
$password = 'admin123';

// Fonction pour faire une requête GraphQL
function graphqlRequest($endpoint, $query, $variables = [], $cookies = null) {
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'query' => $query,
        'variables' => $variables
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    // Gestion des cookies
    $cookieFile = __DIR__ . '/debug-cookies.txt';
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'response' => $response,
        'data' => json_decode($response, true)
    ];
}

// 1. Se connecter
echo "1. Connexion avec l'utilisateur admin...\n";
$loginMutation = 'mutation Login($username: String!, $password: String!) {
    login(username: $username, password: $password)
}';

$result = graphqlRequest($endpoint, $loginMutation, [
    'username' => $username,
    'password' => $password
]);
echo "Login result: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n\n";

// 2. Envoyer un message WhatsApp
echo "2. Envoi d'un message WhatsApp...\n";
$sendMessageMutation = 'mutation SendWhatsAppMessage($message: WhatsAppMessageInput!) {
    sendWhatsAppMessage(message: $message) {
        id
        phoneNumber
        direction
        type
        content
        status
        createdAt
    }
}';

$result = graphqlRequest($endpoint, $sendMessageMutation, [
    'message' => [
        'recipient' => '+2250777104936',
        'type' => 'text',
        'content' => 'Test final - ' . date('Y-m-d H:i:s')
    ]
]);

echo "Send message result:\n";
if (isset($result['data']['errors'])) {
    echo "ERRORS:\n";
    foreach ($result['data']['errors'] as $error) {
        echo "- " . $error['message'] . "\n";
        if (isset($error['extensions']['debugMessage'])) {
            echo "  Debug: " . $error['extensions']['debugMessage'] . "\n";
            echo "  Path: " . json_encode($error['path']) . "\n";
        }
    }
} else if (isset($result['data']['data']['sendWhatsAppMessage'])) {
    echo "Message envoyé avec succès!\n";
    echo json_encode($result['data']['data']['sendWhatsAppMessage'], JSON_PRETTY_PRINT) . "\n";
} else {
    echo json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
}