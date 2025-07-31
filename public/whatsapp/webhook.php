<?php
/**
 * Webhook WhatsApp avec stockage automatique des messages
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use DI\ContainerBuilder;
use App\Services\WhatsApp\WhatsAppWebhookService;
use Psr\Log\LoggerInterface;

// Configuration - Utiliser la configuration centralisée
$config = require __DIR__ . '/../../src/config/whatsapp.php';
$WEBHOOK_VERIFY_TOKEN = $config['webhook_verify_token'];

// Créer le conteneur DI
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../../src/config/di.php');
$container = $containerBuilder->build();

// Récupérer les services
$webhookService = $container->get(WhatsAppWebhookService::class);
$logger = $container->get(LoggerInterface::class);

// Headers
header('Content-Type: application/json');

// Récupérer la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Vérification du webhook (GET)
if ($method === 'GET') {
    $mode = $_GET['hub_mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';
    
    if ($mode === 'subscribe' && $token === $WEBHOOK_VERIFY_TOKEN) {
        $logger->info('WhatsApp webhook verification successful');
        echo $challenge;
        exit;
    }
    
    $logger->warning('WhatsApp webhook verification failed', [
        'mode' => $mode,
        'token_match' => ($token === $WEBHOOK_VERIFY_TOKEN)
    ]);
    
    http_response_code(403);
    echo json_encode(['error' => 'Verification failed']);
    exit;
}

// Réception des notifications (POST)
if ($method === 'POST') {
    try {
        // Récupérer le payload
        $input = file_get_contents('php://input');
        
        // Vérifier la signature
        $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
        if (!$webhookService->verifyWebhookSignature($input, $signature)) {
            $logger->warning('Invalid WhatsApp webhook signature');
            http_response_code(401);
            echo json_encode(['error' => 'Invalid signature']);
            exit;
        }
        
        // Parser le payload
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $logger->error('Invalid JSON in WhatsApp webhook payload');
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            exit;
        }
        
        // Logger le payload brut pour debug
        $logger->debug('WhatsApp webhook received', [
            'headers' => getallheaders(),
            'payload' => $data
        ]);
        
        // Traiter le webhook
        $webhookService->processWebhook($data);
        
        // Toujours retourner 200 OK pour éviter que Meta ne retente
        http_response_code(200);
        echo json_encode(['success' => true]);
        exit;
        
    } catch (\Exception $e) {
        $logger->error('Error processing WhatsApp webhook', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Retourner 200 OK même en cas d'erreur pour éviter les retries
        http_response_code(200);
        echo json_encode(['success' => false, 'error' => 'Internal error']);
        exit;
    }
}

// Méthode non supportée
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
exit;