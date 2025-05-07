<?php
/**
 * Point d'entrée pour les webhooks de l'API WhatsApp Business de Meta
 * 
 * Ce fichier gère:
 * 1. La vérification initiale du webhook (requête GET)
 * 2. La réception des notifications de messages (requête POST)
 */

// Inclusion du chargeur automatique et de la configuration
require_once __DIR__ . '/../../vendor/autoload.php';

// Afficher toutes les erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use DI\ContainerBuilder;

// Construction du conteneur d'injection de dépendances
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../../src/config/di.php');
$container = $containerBuilder->build();

// Récupération de la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Différentes manipulations selon le type de requête
    if ($method === 'GET') {
        // Vérification du webhook par Meta - appel du contrôleur approprié
        $controller = $container->get('App\\GraphQL\\Controllers\\WhatsApp\\WebhookController');
        $response = $controller->verifyWebhook(
            $_GET['hub_mode'] ?? '',
            $_GET['hub_verify_token'] ?? '',
            $_GET['hub_challenge'] ?? ''
        );
        
        echo $response;
    } elseif ($method === 'POST') {
        // Réception d'une notification - traitement du message entrant
        $controller = $container->get('App\\GraphQL\\Controllers\\WhatsApp\\WebhookController');
        
        // Récupération du payload JSON
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);
        
        // Stockage des logs pour débogage en développement
        // Toujours enregistrer les logs pendant les tests initiaux
        file_put_contents(
            __DIR__ . '/../../var/logs/whatsapp/webhook_' . date('Y-m-d_H-i-s') . '.json',
            $payload
        );
        
        // Traitement du payload
        $response = $controller->processWebhook($data);
        
        // Réponse à Meta - 200 OK suffit pour confirmer la réception
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
    } else {
        // Méthode non autorisée
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    // Log de l'erreur
    error_log('WhatsApp Webhook Error: ' . $e->getMessage());
    
    // Réponse d'erreur
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Internal Server Error']);
}