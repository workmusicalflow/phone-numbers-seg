<?php
/**
 * Webhook simple pour WhatsApp
 */

// Configuration
$WEBHOOK_VERIFY_TOKEN = 'oracle_whatsapp_verify_token_2025';
$LOG_FILE = __DIR__ . '/../../var/logs/whatsapp/webhook_' . date('Y-m-d_H-i-s') . '.json';

// Créer le répertoire de logs s'il n'existe pas
$logDir = dirname($LOG_FILE);
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

// Fonction pour logger
function logData($data, $file) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
}

// Headers
header('Content-Type: application/json');

// Récupérer la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Vérification du webhook (GET)
if ($method === 'GET') {
    $mode = $_GET['hub_mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';
    
    // Logger la vérification
    logData([
        'type' => 'verification',
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => 'GET',
        'params' => $_GET
    ], $LOG_FILE);
    
    if ($mode === 'subscribe' && $token === $WEBHOOK_VERIFY_TOKEN) {
        echo $challenge;
        exit;
    }
    
    http_response_code(403);
    echo json_encode(['error' => 'Verification failed']);
    exit;
}

// Réception des notifications (POST)
if ($method === 'POST') {
    // Récupérer le payload
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Logger toutes les données reçues
    logData([
        'type' => 'notification',
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => 'POST',
        'headers' => getallheaders(),
        'payload' => $data
    ], $LOG_FILE);
    
    // Traiter les messages entrants
    if (isset($data['entry'][0]['changes'][0]['value']['messages'])) {
        $messages = $data['entry'][0]['changes'][0]['value']['messages'];
        foreach ($messages as $message) {
            logData([
                'type' => 'incoming_message',
                'timestamp' => date('Y-m-d H:i:s'),
                'from' => $message['from'] ?? '',
                'message_id' => $message['id'] ?? '',
                'message_type' => $message['type'] ?? '',
                'content' => $message['text']['body'] ?? null
            ], $LOG_FILE);
        }
    }
    
    // Traiter les statuts
    if (isset($data['entry'][0]['changes'][0]['value']['statuses'])) {
        $statuses = $data['entry'][0]['changes'][0]['value']['statuses'];
        foreach ($statuses as $status) {
            logData([
                'type' => 'status_update',
                'timestamp' => date('Y-m-d H:i:s'),
                'message_id' => $status['id'] ?? '',
                'recipient_id' => $status['recipient_id'] ?? '',
                'status' => $status['status'] ?? '',
                'errors' => $status['errors'] ?? null
            ], $LOG_FILE);
        }
    }
    
    // Toujours retourner 200 OK pour éviter que Meta ne retente
    http_response_code(200);
    echo json_encode(['success' => true]);
    exit;
}

// Méthode non supportée
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
exit;