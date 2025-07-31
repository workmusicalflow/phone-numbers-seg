<?php

// CORS Headers
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 3600");
header('Vary: Origin');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Test response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'CORS test successful',
    'headers' => getallheaders(),
    'method' => $_SERVER['REQUEST_METHOD']
]);