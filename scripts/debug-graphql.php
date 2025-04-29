<?php

/**
 * Debug script for GraphQL endpoint
 * 
 * This script tests sending a GraphQL mutation to create a contact.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// First, let's get a valid session token by logging in
$loginMutation = <<<'GRAPHQL'
mutation {
  login(username: "admin", password: "password123")
}
GRAPHQL;

// Execute login query
$ch = curl_init('http://localhost:8000/graphql.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'query' => $loginMutation
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/debug-cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/debug-cookies.txt');

$loginResponse = curl_exec($ch);
$loginInfo = curl_getinfo($ch);
curl_close($ch);

echo "Login HTTP Status: " . $loginInfo['http_code'] . "\n";
echo "Login Response: " . $loginResponse . "\n\n";

// Now try to create a contact
$createContactMutation = <<<'GRAPHQL'
mutation {
  createContact(
    name: "Test Debug Contact",
    phoneNumber: "+22507123456",
    email: "test@example.com",
    notes: "Created via debug script"
  ) {
    id
    name
    phoneNumber
    email
    notes
  }
}
GRAPHQL;

// Execute create contact mutation
$ch = curl_init('http://localhost:8000/graphql.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'query' => $createContactMutation
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/debug-cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/debug-cookies.txt');
curl_setopt($ch, CURLOPT_VERBOSE, true);

$createResponse = curl_exec($ch);
$createInfo = curl_getinfo($ch);
curl_close($ch);

echo "Create Contact HTTP Status: " . $createInfo['http_code'] . "\n";
echo "Create Contact Response: " . $createResponse . "\n";

// Parse response to check for errors
$result = json_decode($createResponse, true);
if (isset($result['errors'])) {
    echo "\nErrors encountered:\n";
    foreach ($result['errors'] as $error) {
        echo "- " . $error['message'] . "\n";
        if (isset($error['extensions']['trace'])) {
            echo "  Trace: " . json_encode($error['extensions']['trace']) . "\n";
        }
    }
} elseif (isset($result['data']['createContact'])) {
    echo "\nContact created successfully with ID: " . $result['data']['createContact']['id'] . "\n";
} else {
    echo "\nUnexpected response format\n";
}