<?php

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate'); // Prevent caching
header('Pragma: no-cache');
header('Expires: 0');

$response = [
    'status' => 'OK',
    'timestamp' => date('c'),
    'database' => 'OK', // Assume OK initially
];
$httpStatusCode = 200;

// Database Check
$dbPath = __DIR__ . '/../var/database.sqlite';
$pdo = null; // Initialize PDO variable

try {
    if (!file_exists($dbPath)) {
        throw new \Exception("Database file not found at: " . $dbPath);
    }
    // Attempt to create a PDO instance
    $pdo = new \PDO('sqlite:' . $dbPath);
    // Set error mode to exception to catch connection errors
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    // Optional: Perform a simple query to ensure the connection is truly working
    $pdo->query('SELECT 1');
} catch (\PDOException $e) {
    $response['database'] = 'Error';
    $response['database_error'] = 'PDO Connection Error: ' . $e->getMessage();
    $httpStatusCode = 503; // Service Unavailable
    error_log("Health Check PDO Error: " . $e->getMessage());
} catch (\Exception $e) {
    $response['database'] = 'Error';
    $response['database_error'] = 'General Error: ' . $e->getMessage();
    $httpStatusCode = 503; // Service Unavailable
    error_log("Health Check General Error: " . $e->getMessage());
} finally {
    // Ensure the connection is closed
    $pdo = null;
}

// Set HTTP status code
http_response_code($httpStatusCode);

// Output the JSON response
echo json_encode($response);
