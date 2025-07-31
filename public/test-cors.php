<?php

// Test CORS simple
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 3600");

// Handle OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Test response
header('Content-Type: application/json');
echo json_encode([
    'cors' => 'ok',
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'origin' => $_SERVER['HTTP_ORIGIN'] ?? 'none'
]);