<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\Contact;
use App\Repositories\Doctrine\ContactRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Get the entity manager directly from bootstrap file
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Create the repository manually
$contactRepository = new ContactRepository($entityManager);

// Create a new contact
$contact = new Contact();
$contact->setUserId(1); // Assuming user ID 1 exists
$contact->setName('John Doe');
$contact->setPhoneNumber('+2250777104936'); // Using accepted format
$contact->setEmail('john.doe@example.com');
$contact->setNotes('Test contact');

// Save the contact
try {
    echo "Creating a new contact...\n";
    $contactRepository->save($contact);
    echo "Contact created with ID: " . $contact->getId() . "\n";
} catch (\Exception $e) {
    echo "Error creating contact: " . $e->getMessage() . "\n";

    // Check if the error is related to missing tables
    if (strpos($e->getMessage(), 'no such table') !== false) {
        echo "It seems the database tables don't exist yet. Let's create them.\n";

        // Get the schema tool
        $schemaManager = $entityManager->getConnection()->createSchemaManager();

        // Create the schema for Contact entity only
        echo "Creating database schema for Contact entity...\n";
        $contactMetadata = $entityManager->getClassMetadata(Contact::class);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);

        try {
            // Try to create just the contacts table
            $schemaTool->createSchema([$contactMetadata]);
        } catch (\Exception $e) {
            // If there's an error, try to update the schema instead
            echo "Error creating schema: " . $e->getMessage() . "\n";
            echo "Trying to update schema instead...\n";
            $schemaTool->updateSchema([$contactMetadata]);
        }

        echo "Schema created successfully. Please run the script again.\n";
        exit(1);
    }

    exit(1);
}

// Find all contacts
echo "\nFinding all contacts...\n";
$contacts = $contactRepository->findAll();
echo "Found " . count($contacts) . " contacts:\n";
foreach ($contacts as $c) {
    echo "- ID: " . $c->getId() . ", Name: " . $c->getName() . ", Phone: " . $c->getPhoneNumber() . "\n";
}

// Find contact by ID
echo "\nFinding contact by ID " . $contact->getId() . "...\n";
$foundContact = $contactRepository->findById($contact->getId());
if ($foundContact) {
    echo "Found contact: ID: " . $foundContact->getId() . ", Name: " . $foundContact->getName() . ", Phone: " . $foundContact->getPhoneNumber() . "\n";
} else {
    echo "Contact not found.\n";
}

// Find contacts by user ID
echo "\nFinding contacts by user ID 1...\n";
$userContacts = $contactRepository->findByUserId(1);
echo "Found " . count($userContacts) . " contacts for user ID 1:\n";
foreach ($userContacts as $c) {
    echo "- ID: " . $c->getId() . ", Name: " . $c->getName() . ", Phone: " . $c->getPhoneNumber() . "\n";
}

// Search contacts
echo "\nSearching for contacts with 'John'...\n";
$searchResults = $contactRepository->search('John');
echo "Found " . count($searchResults) . " contacts matching 'John':\n";
foreach ($searchResults as $c) {
    echo "- ID: " . $c->getId() . ", Name: " . $c->getName() . ", Phone: " . $c->getPhoneNumber() . "\n";
}

// Search contacts by user ID
echo "\nSearching for contacts with 'John' for user ID 1...\n";
$userSearchResults = $contactRepository->searchByUserId('John', 1);
echo "Found " . count($userSearchResults) . " contacts matching 'John' for user ID 1:\n";
foreach ($userSearchResults as $c) {
    echo "- ID: " . $c->getId() . ", Name: " . $c->getName() . ", Phone: " . $c->getPhoneNumber() . "\n";
}

// Count contacts
echo "\nCounting all contacts...\n";
$contactCount = $contactRepository->count();
echo "Total contacts: " . $contactCount . "\n";

// Count contacts by user ID
echo "\nCounting contacts for user ID 1...\n";
$userContactCount = $contactRepository->countByUserId(1);
echo "Total contacts for user ID 1: " . $userContactCount . "\n";

// Update contact
echo "\nUpdating contact...\n";
$foundContact->setName('Jane Doe');
$foundContact->setPhoneNumber('002250777104937'); // Using accepted format with 00 prefix
$contactRepository->save($foundContact);
echo "Contact updated.\n";

// Verify update
$updatedContact = $contactRepository->findById($foundContact->getId());
echo "Updated contact: ID: " . $updatedContact->getId() . ", Name: " . $updatedContact->getName() . ", Phone: " . $updatedContact->getPhoneNumber() . "\n";

// Bulk create contacts
echo "\nBulk creating contacts...\n";
$bulkContacts = [
    [
        'name' => 'Alice Smith',
        'phoneNumber' => '+2250777104938', // Using accepted format
        'email' => 'alice@example.com',
        'notes' => 'Bulk test 1'
    ],
    [
        'name' => 'Bob Johnson',
        'phoneNumber' => '0777104939', // Using local format
        'email' => 'bob@example.com',
        'notes' => 'Bulk test 2'
    ]
];

try {
    $createdContacts = $contactRepository->bulkCreate($bulkContacts, 1);
    echo "Created " . count($createdContacts) . " contacts in bulk.\n";
    foreach ($createdContacts as $c) {
        echo "- ID: " . $c->getId() . ", Name: " . $c->getName() . ", Phone: " . $c->getPhoneNumber() . "\n";
    }
} catch (\Exception $e) {
    echo "Error bulk creating contacts: " . $e->getMessage() . "\n";
}

// Delete contact
echo "\nDeleting contact...\n";
$contactId = $contact->getId(); // Store the ID before deletion
$deleteResult = $contactRepository->deleteById($contactId);
echo "Delete result: " . ($deleteResult ? "Success" : "Failed") . "\n";

// Verify deletion
$deletedContact = $contactRepository->findById($contactId);
echo "Contact after deletion: " . ($deletedContact ? "Still exists (error)" : "Successfully deleted") . "\n";

echo "\nDoctrine ORM Contact test completed successfully!\n";
