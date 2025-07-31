<?php

// Test simple de la requête GraphQL pour récupérer les messages WhatsApp

require_once __DIR__ . '/../vendor/autoload.php';

// Configuration de la requête GraphQL
$query = '
    query GetWhatsAppMessages {
        getWhatsAppMessages(limit: 10, offset: 0) {
            messages {
                id
                wabaMessageId
                phoneNumber
                direction
                type
                content
                status
                timestamp
            }
            totalCount
            hasMore
        }
    }
';

$data = [
    'query' => $query,
    'variables' => []
];

// Adresse du serveur GraphQL (ajustez selon votre configuration)
$url = 'http://localhost:8000/graphql.php';

// Headers (ajustez le token selon votre authentification)
$headers = [
    'Content-Type: application/json',
    'Accept: application/json',
    // Si vous avez besoin d'un token d'authentification, ajoutez-le ici
    // 'Authorization: Bearer YOUR_TOKEN'
];

// Envoyer la requête
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_VERBOSE, true);

echo "Envoi de la requête GraphQL...\n";
echo "URL: $url\n";
echo "Query: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

echo "HTTP Code: $httpCode\n";
echo "Content-Type: $contentType\n\n";

if (curl_errno($ch)) {
    echo "Erreur cURL: " . curl_error($ch) . "\n";
} else {
    echo "Réponse:\n";
    
    // Si la réponse est JSON
    if (strpos($contentType, 'application/json') !== false) {
        $decodedResponse = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo json_encode($decodedResponse, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "Erreur JSON: " . json_last_error_msg() . "\n";
            echo "Réponse brute: $response\n";
        }
    } else {
        // Si la réponse est HTML (probablement une erreur)
        echo "Réponse non-JSON reçue. Type de contenu: $contentType\n";
        echo "Réponse brute (premiers 1000 caractères):\n";
        echo substr($response, 0, 1000) . "\n";
        
        // Essayer d'extraire le message d'erreur si c'est du HTML
        if (preg_match('/<pre[^>]*>(.*?)<\/pre>/s', $response, $matches)) {
            echo "\nMessage d'erreur extrait:\n";
            echo strip_tags($matches[1]) . "\n";
        }
    }
}

curl_close($ch);