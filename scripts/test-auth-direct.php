<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Test direct de l'authentification
$ch = curl_init('http://localhost:8000/graphql.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');

// Login
echo "=== Test de login ===\n";
$loginQuery = [
    'query' => 'mutation {
        login(username: "admin", password: "admin123")
    }'
];

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginQuery));
$loginResponse = curl_exec($ch);
echo "Response: " . $loginResponse . "\n\n";

// Essayons de récupérer les infos de l'utilisateur connecté
echo "=== Test me query ===\n";
$meQuery = [
    'query' => 'query {
        me {
            id
            email
            isAdmin
        }
    }'
];

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($meQuery));
$meResponse = curl_exec($ch);
echo "Response: " . $meResponse . "\n\n";

// Essayons de récupérer les templates WhatsApp - utilisons les vrais champs
echo "=== Test getWhatsAppUserTemplates ===\n";
$templatesQuery = [
    'query' => 'query {
        getWhatsAppUserTemplates {
            id
            template_id
            name
            language
            status
        }
    }'
];

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($templatesQuery));
$templatesResponse = curl_exec($ch);
echo "Response: " . $templatesResponse . "\n\n";

curl_close($ch);