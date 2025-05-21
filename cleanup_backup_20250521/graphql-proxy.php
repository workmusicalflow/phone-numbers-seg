<?php
/**
 * Proxy d'urgence pour l'API GraphQL
 * 
 * Ce fichier intercepte les requêtes GraphQL, détecte celles qui concernent fetchApprovedWhatsAppTemplates
 * et fournit une réponse de secours pour éviter les erreurs.
 */

// Vérifier si c'est une requête CORS OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Gérer les en-têtes CORS
    header("Access-Control-Allow-Origin: http://localhost:5173");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Max-Age: 3600");
    header('HTTP/1.1 204 No Content');
    exit;
}

// Uniquement pour les requêtes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: POST, OPTIONS');
    header('Content-Type: application/json');
    echo json_encode(['errors' => [['message' => 'Method not allowed. Use POST for queries and mutations.']]]);
    exit;
}

// Lire l'entrée
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

// Vérifier si c'est une requête fetchApprovedWhatsAppTemplates
$isFetchTemplatesQuery = false;
if (isset($input['query'])) {
    $query = $input['query'];
    // Recherche basique pour détecter cette requête spécifique
    if (strpos($query, 'fetchApprovedWhatsAppTemplates') !== false) {
        $isFetchTemplatesQuery = true;
    }
}

// Si c'est la requête problématique, retourner une réponse de secours
if ($isFetchTemplatesQuery) {
    header('Content-Type: application/json');
    
    // Construire une réponse qui correspond au schéma GraphQL attendu
    // avec un tableau vide mais valide
    $response = [
        'data' => [
            'fetchApprovedWhatsAppTemplates' => []
        ]
    ];
    
    echo json_encode($response);
    exit;
}

// Sinon, transmettre la requête au vrai graphql.php
include __DIR__ . '/../public/graphql.php';