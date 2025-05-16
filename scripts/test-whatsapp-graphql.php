<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Configuration
$endpoint = 'http://localhost:8000/graphql.php';
$username = 'admin';
$password = 'MotDePasseSecure2024!!';

// D'abord, nous devons nous connecter
$loginQuery = [
    'query' => 'mutation Login($username: String!, $password: String!) {
        login(username: $username, password: $password)
    }',
    'variables' => [
        'username' => $username,
        'password' => $password
    ]
];

// Faire la requête de login
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginQuery));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "Login response code: $httpCode\n";
echo "Login response: $response\n\n";

$loginData = json_decode($response, true);
if (!isset($loginData['data']['login']) || $loginData['data']['login'] !== true) {
    echo "Login failed. Cannot proceed with message sending.\n";
    exit(1);
}

// Récupérer le cookie de session
$cookies = curl_getinfo($ch, CURLINFO_COOKIELIST);
echo "Cookies after login: \n";
print_r($cookies);
echo "\n";

// Maintenant, envoyons un message WhatsApp
$sendMessageMutation = [
    'query' => 'mutation SendWhatsAppMessage($message: WhatsAppMessageInput!) {
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
    }',
    'variables' => [
        'message' => [
            'recipient' => '+2250777104936',
            'type' => 'text',
            'content' => 'Test depuis GraphQL PHP'
        ]
    ]
];

echo "Sending WhatsApp message mutation...\n";
echo json_encode($sendMessageMutation, JSON_PRETTY_PRINT) . "\n\n";

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sendMessageMutation));
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "Send message response code: $httpCode\n";
echo "Send message response: $response\n\n";

// Analyser la réponse
$responseData = json_decode($response, true);
if (isset($responseData['errors'])) {
    echo "ERRORS FOUND:\n";
    foreach ($responseData['errors'] as $error) {
        echo "- " . $error['message'] . "\n";
        if (isset($error['extensions']['debugMessage'])) {
            echo "  Debug: " . $error['extensions']['debugMessage'] . "\n";
        }
    }
} else if (isset($responseData['data']['sendWhatsAppMessage'])) {
    echo "MESSAGE SENT SUCCESSFULLY:\n";
    echo json_encode($responseData['data']['sendWhatsAppMessage'], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "UNEXPECTED RESPONSE FORMAT\n";
}

curl_close($ch);