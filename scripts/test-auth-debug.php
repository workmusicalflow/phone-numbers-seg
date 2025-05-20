<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Test avec session debugging
session_start();

echo "=== Test direct de l'API GraphQL avec session debugging ===\n\n";

// D'abord, testons avec une requête simplifiée
$ch = curl_init('http://localhost:8000/graphql.php');

// Configuration de base
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_COOKIEJAR => __DIR__ . '/debug-cookies.txt',
    CURLOPT_COOKIEFILE => __DIR__ . '/debug-cookies.txt',
    CURLOPT_VERBOSE => true,
    CURLOPT_HEADER => true // Pour voir les headers de réponse
]);

// Test 1: Login
echo "=== Test 1: Login ===\n";
$loginData = [
    'query' => 'mutation { login(username: "admin@example.com", password: "admin123") }'
];

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
$loginResponse = curl_exec($ch);

// Parser la réponse avec headers
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($loginResponse, 0, $headerSize);
$body = substr($loginResponse, $headerSize);

echo "Headers:\n$headers\n";
echo "Body:\n$body\n\n";

// Test 2: Query me avec la session établie
echo "=== Test 2: Query me ===\n";
curl_setopt($ch, CURLOPT_HEADER, false); // On n'a plus besoin des headers

$meData = [
    'query' => 'query { me { id email isAdmin } }'
];

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($meData));
$meResponse = curl_exec($ch);
echo "Response:\n$meResponse\n\n";

// Test 3: Templates WhatsApp
echo "=== Test 3: Templates WhatsApp ===\n";
$templatesData = [
    'query' => 'query { getWhatsAppUserTemplates { id name language category status bodyText isActive isGlobal } }'
];

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($templatesData));
$templatesResponse = curl_exec($ch);
echo "Response:\n$templatesResponse\n\n";

curl_close($ch);

// Afficher le contenu du fichier cookies
if (file_exists(__DIR__ . '/debug-cookies.txt')) {
    echo "=== Contenu du fichier cookies ===\n";
    echo file_get_contents(__DIR__ . '/debug-cookies.txt');
}