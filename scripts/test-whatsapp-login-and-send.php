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

// 1. D'abord, récupérer l'utilisateur actuel pour tester l'état de session
echo "1. Vérification de l'utilisateur actuel...\n";
$meQuery = 'query { me { id username } }';
$result = graphqlRequest($endpoint, $meQuery);
echo "Response: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n\n";

// 2. Se connecter
echo "2. Connexion avec l'utilisateur admin...\n";
$loginMutation = 'mutation Login($username: String!, $password: String!) {
    login(username: $username, password: $password)
}';

$result = graphqlRequest($endpoint, $loginMutation, [
    'username' => $username,
    'password' => $password
]);
echo "Login result: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n\n";

if ($result['data']['data']['login'] !== true) {
    // Essayons avec le bon mot de passe qui pourrait être dans la DB
    echo "Essai avec un autre mot de passe...\n";
    $password = 'admin';
    $result = graphqlRequest($endpoint, $loginMutation, [
        'username' => $username,
        'password' => $password
    ]);
    echo "Login result: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n\n";
}

// 3. Vérifier à nouveau l'utilisateur actuel
echo "3. Vérification de l'utilisateur après login...\n";
$result = graphqlRequest($endpoint, $meQuery);
echo "Response: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n\n";

// 4. Si connecté, envoyer un message WhatsApp
if (isset($result['data']['data']['me']['id'])) {
    echo "4. Envoi d'un message WhatsApp...\n";
    $sendMessageMutation = 'mutation SendWhatsAppMessage($message: WhatsAppMessageInput!) {
        sendWhatsAppMessage(message: $message) {
            id
            wabaMessageId
            phoneNumber
            direction
            type
            content
            status
            timestamp
            errorCode
            errorMessage
            createdAt
            updatedAt
        }
    }';

    $result = graphqlRequest($endpoint, $sendMessageMutation, [
        'message' => [
            'recipient' => '+2250777104936',
            'type' => 'text',
            'content' => 'Test depuis GraphQL - ' . date('Y-m-d H:i:s')
        ]
    ]);
    
    echo "Send message result:\n";
    echo json_encode($result['data'], JSON_PRETTY_PRINT) . "\n\n";
    
    if (isset($result['data']['errors'])) {
        echo "ERRORS:\n";
        foreach ($result['data']['errors'] as $error) {
            echo "- " . $error['message'] . "\n";
            if (isset($error['extensions']['debugMessage'])) {
                echo "  Debug: " . $error['extensions']['debugMessage'] . "\n";
            }
        }
    } else if (isset($result['data']['data']['sendWhatsAppMessage'])) {
        echo "Message envoyé avec succès!\n";
        echo json_encode($result['data']['data']['sendWhatsAppMessage'], JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "Impossible de se connecter. Vérifiez les identifiants.\n";
}