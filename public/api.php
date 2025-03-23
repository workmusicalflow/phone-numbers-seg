<?php

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

// Connect to the database
try {
    $dbFile = APP_ROOT . '/src/database/database.sqlite';
    if (!file_exists($dbFile)) {
        throw new Exception('Database file not found. Please run the database initialization script first.');
    }

    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Initialize the controllers
$phoneController = new App\Controllers\PhoneController($pdo);
$smsController = new App\Controllers\SMSController($pdo);

// Get the request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
$endpoint = $pathParts[count($pathParts) - 1];

// Handle the request
try {
    // Segment a phone number without saving it
    if ($method === 'POST' && $endpoint === 'segment') {
        // Get the request body
        $requestBody = json_decode(file_get_contents('php://input'), true);

        if (!isset($requestBody['number'])) {
            throw new InvalidArgumentException('Phone number is required');
        }

        $result = $phoneController->segment($requestBody['number']);
        echo json_encode($result);
    }
    // Batch segment multiple phone numbers without saving them
    elseif ($method === 'POST' && $endpoint === 'batch-segment') {
        // Get the request body
        $requestBody = json_decode(file_get_contents('php://input'), true);

        if (!isset($requestBody['numbers']) || !is_array($requestBody['numbers'])) {
            throw new InvalidArgumentException('Array of phone numbers is required');
        }

        $result = $phoneController->batchSegment($requestBody['numbers']);
        echo json_encode($result);
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
        echo json_encode($result);
    }
    // Get all custom segments
    elseif ($method === 'GET' && $endpoint === 'segments') {
        $result = $phoneController->getCustomSegments();
        echo json_encode($result);
    }
    // Get a specific custom segment
    elseif ($method === 'GET' && preg_match('/^segments\/(\d+)$/', $endpoint, $matches)) {
        $segmentId = (int) $matches[1];
        $result = $phoneController->getCustomSegment($segmentId);

        if ($result === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Segment not found']);
        } else {
            echo json_encode($result);
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
        echo json_encode($result);
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
            echo json_encode(['error' => 'Segment not found']);
        } else {
            echo json_encode($result);
        }
    }
    // Delete a custom segment
    elseif ($method === 'DELETE' && preg_match('/^segments\/(\d+)$/', $endpoint, $matches)) {
        $segmentId = (int) $matches[1];
        $result = $phoneController->deleteCustomSegment($segmentId);

        if (!$result) {
            http_response_code(404);
            echo json_encode(['error' => 'Segment not found']);
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
        echo json_encode($result);
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
        echo json_encode($result);
    }
    // Get segments for SMS
    elseif ($method === 'GET' && $endpoint === 'sms/segments') {
        $result = $smsController->getSegmentsForSMS();
        echo json_encode($result);
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
        echo json_encode($result);
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
        echo json_encode($result);
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
        echo json_encode($result);
    }
    // List all phone numbers
    elseif ($method === 'GET' && $endpoint === 'phones') {
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;

        $result = $phoneController->index($limit, $offset);
        echo json_encode($result);
    }
    // Get a specific phone number
    elseif ($method === 'GET' && is_numeric($endpoint)) {
        $result = $phoneController->show((int) $endpoint);

        if ($result === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Phone number not found']);
        } else {
            echo json_encode($result);
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
        echo json_encode($result);
    }
    // Update a phone number
    elseif ($method === 'PUT' && is_numeric($endpoint)) {
        // Get the request body
        $requestBody = json_decode(file_get_contents('php://input'), true);

        $result = $phoneController->update((int) $endpoint, $requestBody);

        if ($result === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Phone number not found']);
        } else {
            echo json_encode($result);
        }
    }
    // Delete a phone number
    elseif ($method === 'DELETE' && is_numeric($endpoint)) {
        $result = $phoneController->delete((int) $endpoint);

        if (!$result) {
            http_response_code(404);
            echo json_encode(['error' => 'Phone number not found']);
        } else {
            http_response_code(204);
        }
    }
    // Invalid endpoint
    else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
