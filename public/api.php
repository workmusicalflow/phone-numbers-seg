<?php

// CORS Headers
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 3600");


// Add CORS headers
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 3600');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit();
}


/**
 * Phone Numbers Segmentation Web Application
 * 
 * API endpoints for phone number operations
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define application root
define('APP_ROOT', dirname(__DIR__));

// Require Composer autoloader
require APP_ROOT . '/vendor/autoload.php';

// Set content type to JSON
header('Content-Type: application/json');

// Debug logging function
function logDebug($message, $data = null)
{
    $logFile = APP_ROOT . '/logs/debug.log';
    $logDir = dirname($logFile);

    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";

    if ($data !== null) {
        $logMessage .= " - Data: " . print_r($data, true);
    }

    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
}

/**
 * Récupère l'utilisateur actuel à partir de l'authentification
 * 
 * @return \App\Entities\User|null L'utilisateur authentifié ou null si aucun utilisateur n'est authentifié
 */
function getCurrentUser()
{
    // Récupérer le token des headers
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    // Vérifier si le header est au format "Bearer <token>"
    if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
        return null;
    }
    
    $token = $matches[1];
    
    // Obtenir une instance du conteneur
    $container = new \App\GraphQL\DIContainer();
    
    try {
        // Obtenir le service d'authentification du conteneur
        $authService = $container->get(\App\Services\Interfaces\Auth\AuthServiceInterface::class);
        
        // Vérifier le token et obtenir l'utilisateur correspondant
        return $authService->getUserFromToken($token);
    } catch (\Exception $e) {
        logDebug('Erreur lors de la récupération de l\'utilisateur', [
            'error' => $e->getMessage(),
            'token' => $token
        ]);
        return null;
    }
}

// Ensure proper JSON encoding
function jsonEncode($data)
{
    // Ensure the data is properly encoded as JSON
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Check for JSON encoding errors
    if ($json === false) {
        $errorMsg = 'JSON encoding error: ' . json_last_error_msg();
        error_log($errorMsg);
        logDebug($errorMsg, $data);
        return '{"error":"JSON encoding failed"}';
    }

    return $json;
}

// Load environment variables from .env file if not already loaded
if (!isset($_ENV['APP_ENV'])) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..'); // Specify the directory containing .env
    $dotenv->load();
}

// Create DI container
try {
    $container = new \App\GraphQL\DIContainer();

    // Get controllers from container
    $phoneController = $container->get(\App\Controllers\PhoneController::class);
    $smsController = $container->get(\App\Controllers\SMSController::class);
    $importExportController = $container->get(\App\Controllers\ImportExportController::class);
    $whatsAppController = $container->get(\App\Controllers\WhatsAppController::class);
} catch (Exception $e) {
    http_response_code(500);
    echo jsonEncode(['error' => 'Container initialization failed: ' . $e->getMessage()]);
    exit;
}

// Get the request method and endpoint
$method = $_SERVER['REQUEST_METHOD'];

// Check if endpoint is provided in the query string
if (isset($_GET['endpoint'])) {
    $endpoint = $_GET['endpoint'];
} else {
    // Otherwise, get it from the path
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = $pathParts[count($pathParts) - 1];
}

// Log the request
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestEndpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : 'unknown';
$requestBody = file_get_contents('php://input');

logDebug("Received $requestMethod request to endpoint: $requestEndpoint", $requestBody);

// Handle the request
try {
    // Segment a phone number without saving it
    if ($method === 'POST' && $endpoint === 'segment') {
        // Get the request body
        $requestBody = json_decode(file_get_contents('php://input'), true);

        if (!isset($requestBody['number'])) {
            throw new InvalidArgumentException('Phone number is required');
        }

        $result = $phoneController->segment(
            $requestBody['number'],
            $requestBody['civility'] ?? null,
            $requestBody['firstName'] ?? null,
            $requestBody['name'] ?? null,
            $requestBody['company'] ?? null
        );
        echo jsonEncode($result);
    }
    // Batch segment multiple phone numbers without saving them
    elseif ($method === 'POST' && $endpoint === 'batch-segment') {
        // Get the request body
        $requestBody = json_decode(file_get_contents('php://input'), true);

        if (!isset($requestBody['numbers']) || !is_array($requestBody['numbers'])) {
            throw new InvalidArgumentException('Array of phone numbers is required');
        }

        $result = $phoneController->batchSegment($requestBody['numbers']);
        echo jsonEncode($result);
    }
    // Batch create multiple phone numbers
    elseif ($method === 'POST' && $endpoint === 'batch-phones') {
        // Get the request body
        $requestBody = json_decode(file_get_contents('php://input'), true);

        if (!isset($requestBody['numbers']) || !is_array($requestBody['numbers'])) {
            throw new InvalidArgumentException('Array of phone numbers is required');
        }

        $result = $phoneController->batchCreate($requestBody['numbers']);
        http_response_code(201);
        echo jsonEncode($result);
    }
    // Get all custom segments
    elseif ($method === 'GET' && $endpoint === 'segments') {
        $result = $phoneController->getCustomSegments();
        echo jsonEncode($result);
    }
    // Get a specific custom segment
    elseif ($method === 'GET' && preg_match('/^segments\/(\d+)$/', $endpoint, $matches)) {
        $segmentId = (int) $matches[1];
        $result = $phoneController->getCustomSegment($segmentId);

        if ($result === null) {
            http_response_code(404);
            echo jsonEncode(['error' => 'Segment not found']);
        } else {
            echo jsonEncode($result);
        }
    }
    // Create a custom segment
    elseif ($method === 'POST' && $endpoint === 'segments') {
        // Get the request body
        $requestBody = json_decode(file_get_contents('php://input'), true);

        if (!isset($requestBody['name'])) {
            throw new InvalidArgumentException('Segment name is required');
        }

        $result = $phoneController->createCustomSegment(
            $requestBody['name'],
            $requestBody['description'] ?? null
        );
        http_response_code(201);
        echo jsonEncode($result);
    }
    // Update a custom segment
    elseif ($method === 'PUT' && preg_match('/^segments\/(\d+)$/', $endpoint, $matches)) {
        $segmentId = (int) $matches[1];

        // Get the request body
        $requestBody = json_decode(file_get_contents('php://input'), true);

        if (!isset($requestBody['name'])) {
            throw new InvalidArgumentException('Segment name is required');
        }

        $result = $phoneController->updateCustomSegment(
            $segmentId,
            $requestBody['name'],
            $requestBody['description'] ?? null
        );

        if ($result === null) {
            http_response_code(404);
            echo jsonEncode(['error' => 'Segment not found']);
        } else {
            echo jsonEncode($result);
        }
    }
    // Delete a custom segment
    elseif ($method === 'DELETE' && preg_match('/^segments\/(\d+)$/', $endpoint, $matches)) {
        $segmentId = (int) $matches[1];
        $result = $phoneController->deleteCustomSegment($segmentId);

        if (!$result) {
            http_response_code(404);
            echo jsonEncode(['error' => 'Segment not found']);
        } else {
            http_response_code(204);
        }
    }
    // Add a phone number to a segment
    elseif ($method === 'POST' && preg_match('/^phones\/(\d+)\/segments\/(\d+)$/', $endpoint, $matches)) {
        $phoneNumberId = (int) $matches[1];
        $segmentId = (int) $matches[2];

        $result = $phoneController->addPhoneNumberToSegment($phoneNumberId, $segmentId);
        http_response_code(204);
    }
    // Remove a phone number from a segment
    elseif ($method === 'DELETE' && preg_match('/^phones\/(\d+)\/segments\/(\d+)$/', $endpoint, $matches)) {
        $phoneNumberId = (int) $matches[1];
        $segmentId = (int) $matches[2];

        $result = $phoneController->removePhoneNumberFromSegment($phoneNumberId, $segmentId);
        http_response_code(204);
    }
    // Get phone numbers by segment
    elseif ($method === 'GET' && preg_match('/^segments\/(\d+)\/phones$/', $endpoint, $matches)) {
        $segmentId = (int) $matches[1];
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;

        $result = $phoneController->findPhoneNumbersBySegment($segmentId, $limit, $offset);
        echo jsonEncode($result);
    }
    // Search phone numbers
    elseif ($method === 'GET' && $endpoint === 'phones/search') {
        if (!isset($_GET['q'])) {
            throw new InvalidArgumentException('Search query is required');
        }

        $query = $_GET['q'];
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;

        $result = $phoneController->searchPhoneNumbers($query, $limit, $offset);
        echo jsonEncode($result);
    }
    // Get segments for SMS
    elseif ($method === 'GET' && $endpoint === 'sms/segments') {
        $result = $smsController->getSegmentsForSMS();
        echo jsonEncode($result);
    }
    // Send SMS to a single number
    elseif ($method === 'POST' && $endpoint === 'sms/send') {
        // Get the request body
        $requestBody = json_decode(file_get_contents('php://input'), true);

        if (!isset($requestBody['number'])) {
            throw new InvalidArgumentException('Phone number is required');
        }
        if (!isset($requestBody['message'])) {
            throw new InvalidArgumentException('Message is required');
        }

        $result = $smsController->sendSMS($requestBody['number'], $requestBody['message']);
        echo jsonEncode($result);
    }
    // Send SMS to multiple numbers
    elseif ($method === 'POST' && $endpoint === 'sms/bulk') {
        // Get the request body
        $requestBody = json_decode(file_get_contents('php://input'), true);

        if (!isset($requestBody['numbers']) || !is_array($requestBody['numbers'])) {
            throw new InvalidArgumentException('Array of phone numbers is required');
        }
        if (!isset($requestBody['message'])) {
            throw new InvalidArgumentException('Message is required');
        }

        $result = $smsController->sendBulkSMS($requestBody['numbers'], $requestBody['message']);
        echo jsonEncode($result);
    }
    // Send SMS to a segment
    elseif ($method === 'POST' && preg_match('/^sms\/segments\/(\d+)\/send$/', $endpoint, $matches)) {
        $segmentId = (int) $matches[1];

        // Get the request body
        $requestBody = json_decode(file_get_contents('php://input'), true);

        if (!isset($requestBody['message'])) {
            throw new InvalidArgumentException('Message is required');
        }

        $result = $smsController->sendSMSToSegment($segmentId, $requestBody['message']);
        echo jsonEncode($result);
    }
    // Import phone numbers from CSV file
    elseif ($method === 'POST' && $endpoint === 'import-csv') {
        $result = $importExportController->importCSV($_POST);
        echo jsonEncode($result);
    }
    // Import phone numbers from text
    elseif ($method === 'POST' && $endpoint === 'import-text') {
        // Get the request body
        $requestBody = json_decode(file_get_contents('php://input'), true);
        $result = $importExportController->importFromText($requestBody);
        echo jsonEncode($result);
    }
    // Export phone numbers to CSV
    elseif ($method === 'GET' && $endpoint === 'export-csv') {
        $result = $importExportController->exportToCSV($_GET);

        if (is_array($result) && isset($result['status']) && $result['status'] === 'error') {
            echo jsonEncode($result);
        } else {
            // Set headers for CSV download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="phone_numbers.csv"');
            echo $result;
        }
    }
    // Export phone numbers to Excel
    elseif ($method === 'GET' && $endpoint === 'export-excel') {
        $result = $importExportController->exportToExcel($_GET);

        if (is_array($result) && isset($result['status']) && $result['status'] === 'error') {
            echo jsonEncode($result);
        } else {
            // Set headers for Excel download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="phone_numbers.xlsx"');
            echo $result;
        }
    }
    // List all phone numbers
    elseif ($method === 'GET' && $endpoint === 'phones') {
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;

        $result = $phoneController->index($limit, $offset);
        echo jsonEncode($result);
    }
    // Get a specific phone number
    elseif ($method === 'GET' && is_numeric($endpoint)) {
        $result = $phoneController->show((int) $endpoint);

        if ($result === null) {
            http_response_code(404);
            echo jsonEncode(['error' => 'Phone number not found']);
        } else {
            echo jsonEncode($result);
        }
    }
    // Create a new phone number
    elseif ($method === 'POST' && $endpoint === 'phones') {
        // Get the request body
        $requestBody = json_decode(file_get_contents('php://input'), true);

        if (!isset($requestBody['number'])) {
            throw new InvalidArgumentException('Phone number is required');
        }

        $result = $phoneController->create($requestBody);
        http_response_code(201);
        echo jsonEncode($result);
    }
    // Update a phone number
    elseif ($method === 'PUT' && is_numeric($endpoint)) {
        // Get the request body
        $requestBody = json_decode(file_get_contents('php://input'), true);

        $result = $phoneController->update((int) $endpoint, $requestBody);

        if ($result === null) {
            http_response_code(404);
            echo jsonEncode(['error' => 'Phone number not found']);
        } else {
            echo jsonEncode($result);
        }
    }
    // Delete a phone number
    elseif ($method === 'DELETE' && is_numeric($endpoint)) {
        $result = $phoneController->delete((int) $endpoint);

        if (!$result) {
            http_response_code(404);
            echo jsonEncode(['error' => 'Phone number not found']);
        } else {
            http_response_code(204);
        }
    }
    // === WhatsApp endpoints ===
    
    // Verify WhatsApp webhook
    elseif ($method === 'GET' && $endpoint === 'whatsapp/webhook') {
        $challenge = $whatsAppController->verifyWebhook($_GET);
        if ($challenge !== null) {
            echo $challenge;
        } else {
            http_response_code(403);
            echo jsonEncode(['error' => 'Vérification du webhook échouée']);
        }
    }
    // Process WhatsApp webhook data
    elseif ($method === 'POST' && $endpoint === 'whatsapp/webhook') {
        $webhookData = json_decode(file_get_contents('php://input'), true);
        $result = $whatsAppController->processWebhook($webhookData);
        echo jsonEncode($result);
    }
    // Get message history
    elseif ($method === 'GET' && $endpoint === 'whatsapp/messages') {
        // Récupérer l'utilisateur actuel (à implémenter selon votre système d'authentification)
        $user = getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo jsonEncode(['error' => 'Utilisateur non authentifié']);
            exit;
        }
        
        $result = $whatsAppController->getMessageHistory($user, $_GET);
        echo jsonEncode($result);
    }
    // Send text message
    elseif ($method === 'POST' && $endpoint === 'whatsapp/messages/text') {
        // Récupérer l'utilisateur actuel
        $user = getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo jsonEncode(['error' => 'Utilisateur non authentifié']);
            exit;
        }
        
        $requestBody = json_decode(file_get_contents('php://input'), true);
        $result = $whatsAppController->sendTextMessage($user, $requestBody);
        echo jsonEncode($result);
    }
    // Send media message
    elseif ($method === 'POST' && $endpoint === 'whatsapp/messages/media') {
        // Récupérer l'utilisateur actuel
        $user = getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo jsonEncode(['error' => 'Utilisateur non authentifié']);
            exit;
        }
        
        $requestBody = json_decode(file_get_contents('php://input'), true);
        $result = $whatsAppController->sendMediaMessage($user, $requestBody);
        echo jsonEncode($result);
    }
    // Send template message
    elseif ($method === 'POST' && $endpoint === 'whatsapp/messages/template') {
        // Récupérer l'utilisateur actuel
        $user = getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo jsonEncode(['error' => 'Utilisateur non authentifié']);
            exit;
        }
        
        $requestBody = json_decode(file_get_contents('php://input'), true);
        $result = $whatsAppController->sendTemplateMessage($user, $requestBody);
        echo jsonEncode($result);
    }
    // Send template message with components
    elseif ($method === 'POST' && $endpoint === 'whatsapp/messages/template/advanced') {
        // Récupérer l'utilisateur actuel
        $user = getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo jsonEncode(['error' => 'Utilisateur non authentifié']);
            exit;
        }
        
        $requestBody = json_decode(file_get_contents('php://input'), true);
        $result = $whatsAppController->sendTemplateMessageWithComponents($user, $requestBody);
        echo jsonEncode($result);
    }
    // Send interactive message
    elseif ($method === 'POST' && $endpoint === 'whatsapp/messages/interactive') {
        // Récupérer l'utilisateur actuel
        $user = getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo jsonEncode(['error' => 'Utilisateur non authentifié']);
            exit;
        }
        
        $requestBody = json_decode(file_get_contents('php://input'), true);
        $result = $whatsAppController->sendInteractiveMessage($user, $requestBody);
        echo jsonEncode($result);
    }
    // Mark message as read
    elseif ($method === 'POST' && $endpoint === 'whatsapp/messages/read') {
        // Récupérer l'utilisateur actuel
        $user = getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo jsonEncode(['error' => 'Utilisateur non authentifié']);
            exit;
        }
        
        $requestBody = json_decode(file_get_contents('php://input'), true);
        $result = $whatsAppController->markMessageAsRead($user, $requestBody);
        echo jsonEncode($result);
    }
    // Upload media
    elseif ($method === 'POST' && $endpoint === 'whatsapp/media/upload') {
        // Récupérer l'utilisateur actuel
        $user = getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo jsonEncode(['error' => 'Utilisateur non authentifié']);
            exit;
        }
        
        $requestBody = json_decode(file_get_contents('php://input'), true);
        $result = $whatsAppController->uploadMedia($user, $requestBody);
        echo jsonEncode($result);
    }
    // Download media
    elseif ($method === 'GET' && $endpoint === 'whatsapp/media/download') {
        // Récupérer l'utilisateur actuel
        $user = getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo jsonEncode(['error' => 'Utilisateur non authentifié']);
            exit;
        }
        
        $result = $whatsAppController->downloadMedia($user, $_GET);
        echo jsonEncode($result);
    }
    // Get media URL
    elseif ($method === 'GET' && $endpoint === 'whatsapp/media/url') {
        // Récupérer l'utilisateur actuel
        $user = getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo jsonEncode(['error' => 'Utilisateur non authentifié']);
            exit;
        }
        
        $result = $whatsAppController->getMediaUrl($user, $_GET);
        echo jsonEncode($result);
    }
    // Get user templates
    elseif ($method === 'GET' && $endpoint === 'whatsapp/templates') {
        // Récupérer l'utilisateur actuel
        $user = getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo jsonEncode(['error' => 'Utilisateur non authentifié']);
            exit;
        }
        
        $result = $whatsAppController->getUserTemplates($user);
        echo jsonEncode($result);
    }
    // Get approved templates with robust error handling
    elseif ($method === 'GET' && $endpoint === 'whatsapp/templates/approved') {
        // Récupérer l'utilisateur actuel
        $user = getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo jsonEncode(['error' => 'Utilisateur non authentifié']);
            exit;
        }
        
        $result = $whatsAppController->getApprovedTemplates($user, $_GET);
        echo jsonEncode($result);
    }
    // Get template by ID
    elseif ($method === 'GET' && preg_match('/^whatsapp\/templates\/(\w+)$/', $endpoint, $matches)) {
        // Récupérer l'utilisateur actuel
        $user = getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo jsonEncode(['error' => 'Utilisateur non authentifié']);
            exit;
        }
        
        $_GET['template_id'] = $matches[1];
        $result = $whatsAppController->getTemplateById($user, $_GET);
        echo jsonEncode($result);
    }
    // Invalid endpoint
    else {
        http_response_code(404);
        echo jsonEncode(['error' => 'Endpoint not found']);
    }
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    $errorCode = 400;

    // Log the error
    logDebug("Error processing request: $errorMsg", [
        'endpoint' => $requestEndpoint,
        'method' => $requestMethod,
        'code' => $errorCode,
        'trace' => $e->getTraceAsString()
    ]);

    http_response_code($errorCode);
    echo jsonEncode(['error' => $errorMsg]);
}

// Log the response
logDebug("Completed request to $requestEndpoint", [
    'status' => http_response_code(),
    'method' => $requestMethod
]);
