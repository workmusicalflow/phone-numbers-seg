<?php
/**
 * Test simple de l'API GraphQL via cURL
 */

// Query GraphQL simple
$query = <<<'GRAPHQL'
query {
  fetchApprovedWhatsAppTemplates {
    id
    name
    category
    language
    status
  }
}
GRAPHQL;

// Préparer la requête
$data = [
    'query' => $query
];

// URL de l'API GraphQL (ajustez si nécessaire)
$url = 'http://localhost:8000/graphql.php';

// Initialiser cURL
$ch = curl_init($url);

// Configurer cURL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

// Exécuter la requête
echo "Envoi de la requête GraphQL à $url...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Vérifier les erreurs
if (curl_errno($ch)) {
    echo "Erreur cURL: " . curl_error($ch) . "\n";
    exit(1);
}

// Fermer cURL
curl_close($ch);

// Afficher les résultats
echo "Code HTTP: $httpCode\n\n";
$responseData = json_decode($response, true);

// Afficher la réponse complète
echo "Réponse complète:\n";
echo json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Vérifier les erreurs GraphQL
if (isset($responseData['errors'])) {
    echo "ERREURS GraphQL DÉTECTÉES:\n";
    foreach ($responseData['errors'] as $error) {
        echo "- " . $error['message'] . "\n";
        if (isset($error['extensions']['debugMessage'])) {
            echo "  Debug: " . $error['extensions']['debugMessage'] . "\n";
        }
    }
    exit(1);
}

// Vérifier les données
if (isset($responseData['data']['fetchApprovedWhatsAppTemplates'])) {
    $templates = $responseData['data']['fetchApprovedWhatsAppTemplates'];
    echo "SUCCÈS: " . count($templates) . " templates récupérés.\n";
    
    // Afficher quelques détails sur les templates
    if (count($templates) > 0) {
        echo "\nVoici les premiers templates:\n";
        $limit = min(3, count($templates));
        for ($i = 0; $i < $limit; $i++) {
            echo "- {$templates[$i]['name']} ({$templates[$i]['id']}): {$templates[$i]['category']}, {$templates[$i]['language']}\n";
        }
    } else {
        echo "Aucun template retourné (tableau vide).\n";
    }
} else {
    echo "ERREUR: Pas de données 'fetchApprovedWhatsAppTemplates' dans la réponse.\n";
}