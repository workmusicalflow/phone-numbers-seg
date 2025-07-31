<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Simuler une requête GraphQL d'envoi de template WhatsApp
$query = <<<'GRAPHQL'
mutation SendWhatsAppTemplate($input: SendTemplateInput!) {
  sendWhatsAppTemplateV2(input: $input) {
    success
    messageId
    error
  }
}
GRAPHQL;

// Variables de la requête
$variables = [
    'input' => [
        'recipientPhoneNumber' => '2250712345678',
        'templateName' => 'hello_world',
        'templateLanguage' => 'en_US',
        'bodyVariables' => ['Test User'],
        'headerMediaUrl' => null
    ]
];

// Créer les cookies pour l'authentification
$cookieData = 'session_token=test_session';
file_put_contents(__DIR__ . '/../cookie.txt', $cookieData);

// Exécuter la requête GraphQL
$ch = curl_init('http://localhost:8000/graphql.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'query' => $query,
    'variables' => $variables
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/../cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/../cookie.txt');

// Récupérer la réponse
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Afficher la réponse
echo "HTTP Code: $httpCode\n";
echo "Response:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT);
echo "\n";