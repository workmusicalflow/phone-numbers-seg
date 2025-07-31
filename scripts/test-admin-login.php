<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Test admin credentials
$username = 'admin';
$password = 'password123';

// GraphQL query
$query = <<<'GRAPHQL'
mutation Login($username: String!, $password: String!) {
  login(username: $username, password: $password)
}
GRAPHQL;

// Variables
$variables = [
    'username' => $username,
    'password' => $password
];

// Make the request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/graphql.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'query' => $query,
    'variables' => $variables
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');

$response = curl_exec($ch);
$error = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

// Output results
echo "HTTP Status: " . $info['http_code'] . "\n";

if ($error) {
    echo "cURL Error: " . $error . "\n";
    exit(1);
}

$data = json_decode($response, true);
echo "Response:\n";
echo json_encode($data, JSON_PRETTY_PRINT) . "\n";

// Check if login was successful
if (isset($data['data']['login']) && $data['data']['login'] === true) {
    echo "Login successful!\n";

    // Now try to get user info with the 'me' query
    $meQuery = <<<'GRAPHQL'
query Me {
  me {
    id
    username
    email
    smsCredit
    smsLimit
    isAdmin
    apiKey
    createdAt
    updatedAt
  }
}
GRAPHQL;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/graphql.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'query' => $meQuery
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');

    $meResponse = curl_exec($ch);
    $meError = curl_error($ch);
    $meInfo = curl_getinfo($ch);
    curl_close($ch);

    echo "\nMe Query HTTP Status: " . $meInfo['http_code'] . "\n";

    if ($meError) {
        echo "cURL Error: " . $meError . "\n";
    } else {
        $meData = json_decode($meResponse, true);
        echo "Me Query Response:\n";
        echo json_encode($meData, JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "Login failed!\n";
    
    // Debug information
    if (isset($data['errors'])) {
        echo "\nErrors:\n";
        foreach ($data['errors'] as $error) {
            echo "- " . $error['message'] . "\n";
            if (isset($error['extensions']['trace'])) {
                echo "  Trace: " . json_encode($error['extensions']['trace']) . "\n";
            }
        }
    }
}