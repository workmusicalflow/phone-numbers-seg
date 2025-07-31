<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Test du filtrage par date dans GraphQL

$url = 'http://localhost:8000/graphql.php';

// Requête GraphQL avec filtrage par date
$query = '
    query GetWhatsAppMessages(
        $limit: Int,
        $offset: Int,
        $startDate: String,
        $endDate: String
    ) {
        getWhatsAppMessages(
            limit: $limit,
            offset: $offset,
            startDate: $startDate,
            endDate: $endDate
        ) {
            messages {
                id
                phoneNumber
                direction
                type
                content
                status
                timestamp
                createdAt
            }
            totalCount
            hasMore
        }
    }
';

// Variables pour tester le filtrage par date (aujourd'hui)
$today = date('Y-m-d');
$variables = [
    'limit' => 10,
    'offset' => 0,
    'startDate' => $today,
    'endDate' => $today
];

$payload = [
    'query' => $query,
    'variables' => $variables
];

// Headers (ajoutez votre token d'authentification si nécessaire)
$headers = [
    'Content-Type: application/json',
    'Accept: application/json',
];

// Envoyer la requête
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

echo "Test du filtrage par date:\n";
echo "Date de test: $today\n";
echo json_encode($variables, JSON_PRETTY_PRINT) . "\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP Code: $httpCode\n";

if (curl_errno($ch)) {
    echo "Erreur cURL: " . curl_error($ch) . "\n";
} else {
    $data = json_decode($response, true);
    
    if (isset($data['errors'])) {
        echo "Erreurs GraphQL:\n";
        foreach ($data['errors'] as $error) {
            echo "- " . $error['message'] . "\n";
            if (isset($error['extensions']['debugMessage'])) {
                echo "  Debug: " . $error['extensions']['debugMessage'] . "\n";
            }
        }
    }
    
    if (isset($data['data']['getWhatsAppMessages'])) {
        $messages = $data['data']['getWhatsAppMessages'];
        echo "\nRésultats:\n";
        echo "- Nombre total de messages: " . $messages['totalCount'] . "\n";
        echo "- Messages reçus: " . count($messages['messages']) . "\n";
        echo "- Plus de résultats: " . ($messages['hasMore'] ? 'Oui' : 'Non') . "\n";
        
        if (count($messages['messages']) > 0) {
            echo "\nPremiers messages:\n";
            foreach (array_slice($messages['messages'], 0, 3) as $message) {
                echo "  - ID: " . $message['id'] . "\n";
                echo "    Téléphone: " . $message['phoneNumber'] . "\n";
                echo "    Créé le: " . $message['createdAt'] . "\n";
                echo "    Timestamp: " . $message['timestamp'] . "\n";
                echo "    Statut: " . $message['status'] . "\n";
                echo "\n";
            }
        }
    }
}

curl_close($ch);

// Test avec une plage de dates
echo "\n\n" . str_repeat('=', 50) . "\n";
echo "Test avec une plage de dates (dernière semaine):\n";

$endDate = date('Y-m-d');
$startDate = date('Y-m-d', strtotime('-7 days'));

$variables2 = [
    'limit' => 10,
    'offset' => 0,
    'startDate' => $startDate,
    'endDate' => $endDate
];

$payload2 = [
    'query' => $query,
    'variables' => $variables2
];

echo "Période: du $startDate au $endDate\n";

$ch2 = curl_init($url);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($payload2));
curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);

$response2 = curl_exec($ch2);
$data2 = json_decode($response2, true);

if (isset($data2['data']['getWhatsAppMessages'])) {
    $messages2 = $data2['data']['getWhatsAppMessages'];
    echo "\nRésultats:\n";
    echo "- Nombre total de messages: " . $messages2['totalCount'] . "\n";
    echo "- Messages reçus: " . count($messages2['messages']) . "\n";
}

curl_close($ch2);