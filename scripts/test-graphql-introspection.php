<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Test d'introspection GraphQL
$ch = curl_init('http://localhost:8000/graphql.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');

// Login d'abord
echo "=== Login ===\n";
$loginQuery = [
    'query' => 'mutation {
        login(username: "admin", password: "admin123")
    }'
];

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginQuery));
$loginResponse = curl_exec($ch);
echo "Response: " . $loginResponse . "\n\n";

// Introspection pour voir les champs de WhatsAppUserTemplate
echo "=== Introspection WhatsAppUserTemplate ===\n";
$introspectionQuery = [
    'query' => '
    {
        __type(name: "WhatsAppUserTemplate") {
            name
            fields {
                name
                type {
                    name
                    kind
                }
            }
        }
    }'
];

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($introspectionQuery));
$response = curl_exec($ch);
echo "Response: " . $response . "\n\n";

curl_close($ch);