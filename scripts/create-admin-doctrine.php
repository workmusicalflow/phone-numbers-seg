<?php

/**
 * Script to create an admin user using Doctrine ORM
 * 
 * This script creates an admin user with the specified credentials.
 * It uses Doctrine ORM to create the user entity and persist it to the database.
 * 
 * Usage: php scripts/create-admin-doctrine.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Get the EntityManager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Create a new user
$user = new \App\Entities\User();
$user->setUsername('admin');
$user->setPassword(password_hash('password123', PASSWORD_DEFAULT));
$user->setEmail('admin@example.com');
$user->setIsAdmin(true);
$user->setSmsCredit(100);
$user->generateApiKey();

try {
    // Persist the user to the database
    $entityManager->persist($user);
    $entityManager->flush();
    
    echo "Admin user created successfully with ID: " . $user->getId() . "\n";
    echo "Username: admin\n";
    echo "Password: password123\n";
    echo "Email: admin@example.com\n";
    echo "API Key: " . $user->getApiKey() . "\n";
    echo "SMS Credit: " . $user->getSmsCredit() . "\n";
    echo "Is Admin: " . ($user->isAdmin() ? 'Yes' : 'No') . "\n";
} catch (\Exception $e) {
    echo "Error creating admin user: " . $e->getMessage() . "\n";
    exit(1);
}