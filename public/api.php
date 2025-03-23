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

// Initialize the controller
$phoneController = new App\Controllers\PhoneController($pdo);

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
    // List all phone numbers
    elseif ($method === 'GET' && $endpoint === 'phones') {
        $result = $phoneController->index();
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

        $result = $phoneController->create($requestBody['number']);
        http_response_code(201);
        echo json_encode($result);
    }
    // Update a phone number
    elseif ($method === 'PUT' && is_numeric($endpoint)) {
        // Get the request body
        $requestBody = json_decode(file_get_contents('php://input'), true);

        if (!isset($requestBody['number'])) {
            throw new InvalidArgumentException('Phone number is required');
        }

        $result = $phoneController->update((int) $endpoint, $requestBody['number']);

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
