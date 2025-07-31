<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Get the entity manager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Test user credentials
$username = 'AfricaQSHE';
$password = 'Qualitas@2024';

// Use direct SQL query to find the user
$conn = $entityManager->getConnection();
$stmt = $conn->prepare('SELECT * FROM users WHERE username = :username');
$stmt->bindValue('username', $username);
$result = $stmt->executeQuery();
$userData = $result->fetchAssociative();

if (!$userData) {
    echo "User not found: $username\n";
    exit(1);
}

echo "User found: " . $userData['username'] . " (ID: " . $userData['id'] . ")\n";
echo "Email: " . $userData['email'] . "\n";
echo "Is Admin: " . ($userData['is_admin'] ? 'Yes' : 'No') . "\n";
echo "SMS Credit: " . $userData['sms_credit'] . "\n";

// Verify password
if (password_verify($password, $userData['password'])) {
    echo "Password is correct!\n";
} else {
    echo "Password is incorrect!\n";
}

// Test session creation
session_start();
$_SESSION['user_id'] = $userData['id'];
$_SESSION['username'] = $userData['username'];
$_SESSION['is_admin'] = $userData['is_admin'];
$_SESSION['auth_time'] = time();

echo "Session created with user ID: " . $_SESSION['user_id'] . "\n";
