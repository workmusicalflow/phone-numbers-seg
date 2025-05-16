<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Configuration
$endpoint = 'http://localhost:8000/graphql.php';

// Fonction pour faire une requête GraphQL
function graphqlRequest($endpoint, $query, $variables = []) {
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
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'response' => $response,
        'error' => $error,
        'data' => json_decode($response, true)
    ];
}

// Test avec différents mots de passe
$passwords = [
    'admin123',                  // Nouveau mot de passe
    'MotDePasseSecure2024!!',    // Ancien mot de passe
    'admin@2024',                // Autre possibilité
    'admin'                      // Simple
];

$loginMutation = 'mutation Login($username: String!, $password: String!) {
    login(username: $username, password: $password)
}';

foreach ($passwords as $password) {
    echo "\nTest avec mot de passe: $password\n";
    $result = graphqlRequest($endpoint, $loginMutation, [
        'username' => 'admin',
        'password' => $password
    ]);
    
    echo "HTTP Code: " . $result['code'] . "\n";
    if ($result['error']) {
        echo "cURL Error: " . $result['error'] . "\n";
    }
    
    if ($result['data']) {
        echo "Response: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
        
        if (isset($result['data']['data']['login']) && $result['data']['data']['login'] === true) {
            echo "✓ Login réussi avec ce mot de passe!\n";
            break;
        }
    } else {
        echo "Raw response: " . $result['response'] . "\n";
    }
}