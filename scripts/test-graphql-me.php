<?php

/**
 * This script tests the GraphQL 'me' query to check if the user session is working correctly.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// First, login to get a session cookie
echo "Logging in...\n";

$loginQuery = <<<'GRAPHQL'
mutation Login($username: String!, $password: String!) {
  login(username: $username, password: $password)
}
GRAPHQL;

$loginVariables = [
    'username' => 'AfricaQSHE',
    'password' => 'Qualitas@2024'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/graphql.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'query' => $loginQuery,
    'variables' => $loginVariables
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');

$loginResponse = curl_exec($ch);
$loginError = curl_error($ch);
$loginInfo = curl_getinfo($ch);
curl_close($ch);

echo "Login HTTP Status: " . $loginInfo['http_code'] . "\n";

if ($loginError) {
    echo "cURL Error: " . $loginError . "\n";
    exit(1);
}

$loginData = json_decode($loginResponse, true);
echo "Login Response:\n";
echo json_encode($loginData, JSON_PRETTY_PRINT) . "\n";

// Check if login was successful
if (isset($loginData['data']['login']) && $loginData['data']['login'] === true) {
    echo "Login successful!\n";

    // Now try to get user info with the 'me' query
    echo "\nFetching user info with 'me' query...\n";

    $meQuery = <<<'GRAPHQL'
query Me {
  me {
    id
    username
    email
    smsCredit
    smsLimit
    isAdmin
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

    echo "Me Query HTTP Status: " . $meInfo['http_code'] . "\n";

    if ($meError) {
        echo "cURL Error: " . $meError . "\n";
    } else {
        $meData = json_decode($meResponse, true);
        echo "Me Query Response:\n";
        echo json_encode($meData, JSON_PRETTY_PRINT) . "\n";

        // Check for errors in the response
        if (isset($meData['errors'])) {
            echo "\nErrors found in the response:\n";
            foreach ($meData['errors'] as $error) {
                echo "- " . $error['message'] . "\n";
                if (isset($error['extensions']['exception']['trace'])) {
                    echo "  Trace: " . json_encode($error['extensions']['exception']['trace']) . "\n";
                }
            }
        }
    }
} else {
    echo "Login failed!\n";
}
