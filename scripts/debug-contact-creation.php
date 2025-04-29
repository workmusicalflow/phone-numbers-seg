<?php

/**
 * Debug script for contact creation issue
 * 
 * This script attempts to create a contact directly using the repository
 * to help diagnose issues with contact creation.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Get the EntityManager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Set up logging
$logFile = __DIR__ . '/../var/logs/debug.log';
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Starting contact creation debug\n", FILE_APPEND);

try {
    // Create a new contact entity
    $contact = new \App\Entities\Contact();
    $contact->setUserId(1); // Admin user ID
    $contact->setName('Test Contact');
    $contact->setPhoneNumber('+22501234567');
    $contact->setEmail('test@example.com');
    $contact->setNotes('Test notes');

    // Log that we're about to persist
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] About to persist contact\n", FILE_APPEND);
    
    // Persist the contact
    $entityManager->persist($contact);
    $entityManager->flush();
    
    // Log success
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Contact created successfully with ID: " . $contact->getId() . "\n", FILE_APPEND);
    
    echo "Contact created successfully with ID: " . $contact->getId() . "\n";
    echo "Name: " . $contact->getName() . "\n";
    echo "Phone Number: " . $contact->getPhoneNumber() . "\n";
    echo "User ID: " . $contact->getUserId() . "\n";
} catch (\Exception $e) {
    // Log the error
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Error creating contact: " . $e->getMessage() . "\n", FILE_APPEND);
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Stack trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
    
    echo "Error creating contact: " . $e->getMessage() . "\n";
    echo "Check " . $logFile . " for more details.\n";
}

// Now try to retrieve all contacts to verify database access
try {
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Trying to fetch all contacts\n", FILE_APPEND);
    
    $contactRepository = $entityManager->getRepository(\App\Entities\Contact::class);
    $contacts = $contactRepository->findAll();
    
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Found " . count($contacts) . " contacts\n", FILE_APPEND);
    
    echo "\nFound " . count($contacts) . " contacts in the database:\n";
    foreach ($contacts as $c) {
        echo "- ID: " . $c->getId() . ", Name: " . $c->getName() . ", Phone: " . $c->getPhoneNumber() . "\n";
    }
} catch (\Exception $e) {
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Error fetching contacts: " . $e->getMessage() . "\n", FILE_APPEND);
    echo "Error fetching contacts: " . $e->getMessage() . "\n";
}