<?php
/**
 * MOCK ENDPOINT - NE PAS UTILISER EN PRODUCTION
 * 
 * Cet endpoint simule l'envoi de messages WhatsApp sans vraie intégration.
 * Utilisé uniquement pour les tests locaux.
 * 
 * @deprecated Utiliser send-template-v2.php pour la production
 */

// Activer CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Si c'est une requête OPTIONS, on répond juste OK
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Méthode non autorisée'
    ]);
    exit;
}

// Récupérer les données JSON du corps de la requête
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Fichier de log simplifié
$logFile = __DIR__ . '/../../../logs/whatsapp-simple-api.log';
$timestamp = date('Y-m-d H:i:s');
$logData = "[$timestamp] Requête reçue: " . json_encode($input, JSON_UNESCAPED_UNICODE) . "\n";
file_put_contents($logFile, $logData, FILE_APPEND);

// Vérifier que les données sont valides
if (!$input || !isset($input['templateName']) || !isset($input['recipientPhoneNumber']) || !isset($input['templateLanguage'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Données invalides ou incomplètes',
        'received' => $input
    ]);
    exit;
}

// Simuler l'envoi d'un message (version simplifiée pour le test)
header('Content-Type: application/json');

// Générer un ID de message aléatoire
$messageId = 'wamid.' . bin2hex(random_bytes(8));

// Retourner une réponse positive
echo json_encode([
    'success' => true,
    'messageId' => $messageId,
    'timestamp' => date('c'),
    'recipientPhoneNumber' => $input['recipientPhoneNumber'],
    'templateName' => $input['templateName'],
    'templateLanguage' => $input['templateLanguage'],
    'message' => 'Cette réponse est simulée pour tester l\'API sans dépendances complexes.'
]);