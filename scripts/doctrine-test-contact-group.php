<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\Contact;
use App\Entities\ContactGroup;
use App\Entities\ContactGroupMembership;
use App\Repositories\Doctrine\ContactGroupRepository;
use App\Repositories\Doctrine\ContactGroupMembershipRepository;
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

// Create the repositories manually
$contactGroupRepository = new ContactGroupRepository($entityManager);
$contactGroupMembershipRepository = new ContactGroupMembershipRepository($entityManager);
$contactRepository = new ContactRepository($entityManager);

// Create a new contact group
$contactGroup = new ContactGroup();
$contactGroup->setUserId(1); // Assuming user ID 1 exists
$contactGroup->setName('Test Group');
$contactGroup->setDescription('This is a test group');

// Save the contact group
try {
    echo "Creating a new contact group...\n";
    $contactGroupRepository->save($contactGroup);
    echo "Contact group created with ID: " . $contactGroup->getId() . "\n";
} catch (\Exception $e) {
    echo "Error creating contact group: " . $e->getMessage() . "\n";

    // Check if the error is related to missing tables
    if (strpos($e->getMessage(), 'no such table') !== false) {
        echo "It seems the database tables don't exist yet. Let's create them.\n";

        // Get the schema tool
        $schemaManager = $entityManager->getConnection()->createSchemaManager();

        // Create the schema for ContactGroup and ContactGroupMembership entities
        echo "Creating database schema for ContactGroup and ContactGroupMembership entities...\n";
        $contactGroupMetadata = $entityManager->getClassMetadata(ContactGroup::class);
        $contactGroupMembershipMetadata = $entityManager->getClassMetadata(ContactGroupMembership::class);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);

        try {
            // Try to create the tables
            $schemaTool->createSchema([$contactGroupMetadata, $contactGroupMembershipMetadata]);
        } catch (\Exception $e) {
            // If there's an error, try to update the schema instead
            echo "Error creating schema: " . $e->getMessage() . "\n";
            echo "Trying to update schema instead...\n";
            $schemaTool->updateSchema([$contactGroupMetadata, $contactGroupMembershipMetadata]);
        }

        echo "Schema created successfully. Please run the script again.\n";
        exit(1);
    }

    exit(1);
}

// Find all contact groups
echo "\nFinding all contact groups...\n";
$contactGroups = $contactGroupRepository->findAll();
echo "Found " . count($contactGroups) . " contact groups:\n";
foreach ($contactGroups as $group) {
    echo "- ID: " . $group->getId() . ", Name: " . $group->getName() . ", Description: " . $group->getDescription() . "\n";
}

// Find contact group by ID
echo "\nFinding contact group by ID " . $contactGroup->getId() . "...\n";
$foundContactGroup = $contactGroupRepository->findById($contactGroup->getId());
if ($foundContactGroup) {
    echo "Found contact group: ID: " . $foundContactGroup->getId() . ", Name: " . $foundContactGroup->getName() . ", Description: " . $foundContactGroup->getDescription() . "\n";
} else {
    echo "Contact group not found.\n";
}

// Find contact groups by user ID
echo "\nFinding contact groups by user ID 1...\n";
$userContactGroups = $contactGroupRepository->findByUserId(1);
echo "Found " . count($userContactGroups) . " contact groups for user ID 1:\n";
foreach ($userContactGroups as $group) {
    echo "- ID: " . $group->getId() . ", Name: " . $group->getName() . ", Description: " . $group->getDescription() . "\n";
}

// Search contact groups
echo "\nSearching for contact groups with 'Test'...\n";
$searchResults = $contactGroupRepository->search('Test');
echo "Found " . count($searchResults) . " contact groups matching 'Test':\n";
foreach ($searchResults as $group) {
    echo "- ID: " . $group->getId() . ", Name: " . $group->getName() . ", Description: " . $group->getDescription() . "\n";
}

// Search contact groups by user ID
echo "\nSearching for contact groups with 'Test' for user ID 1...\n";
$userSearchResults = $contactGroupRepository->searchByUserId('Test', 1);
echo "Found " . count($userSearchResults) . " contact groups matching 'Test' for user ID 1:\n";
foreach ($userSearchResults as $group) {
    echo "- ID: " . $group->getId() . ", Name: " . $group->getName() . ", Description: " . $group->getDescription() . "\n";
}

// Count contact groups
echo "\nCounting all contact groups...\n";
$contactGroupCount = $contactGroupRepository->count();
echo "Total contact groups: " . $contactGroupCount . "\n";

// Count contact groups by user ID
echo "\nCounting contact groups for user ID 1...\n";
$userContactGroupCount = $contactGroupRepository->countByUserId(1);
echo "Total contact groups for user ID 1: " . $userContactGroupCount . "\n";

// Update contact group
echo "\nUpdating contact group...\n";
$foundContactGroup->setName('Updated Test Group');
$foundContactGroup->setDescription('This is an updated test group');
$contactGroupRepository->save($foundContactGroup);
echo "Contact group updated.\n";

// Verify update
$updatedContactGroup = $contactGroupRepository->findById($foundContactGroup->getId());
echo "Updated contact group: ID: " . $updatedContactGroup->getId() . ", Name: " . $updatedContactGroup->getName() . ", Description: " . $updatedContactGroup->getDescription() . "\n";

// Create a contact for testing memberships
echo "\nCreating a test contact...\n";
$contact = new Contact();
$contact->setUserId(1);
$contact->setName('Test Contact');
$contact->setPhoneNumber('+2250777104936');
$contact->setEmail('test@example.com');
$contactRepository->save($contact);
echo "Contact created with ID: " . $contact->getId() . "\n";

// Add contact to group
echo "\nAdding contact to group...\n";
$result = $contactGroupRepository->addContactToGroup($contact->getId(), $contactGroup->getId());
echo "Add contact to group result: " . ($result ? "Success" : "Failed") . "\n";

// Get contacts in group
echo "\nGetting contacts in group...\n";
$contactsInGroup = $contactGroupRepository->getContactsInGroup($contactGroup->getId());
echo "Found " . count($contactsInGroup) . " contacts in group:\n";
foreach ($contactsInGroup as $c) {
    echo "- ID: " . $c->getId() . ", Name: " . $c->getName() . ", Phone: " . $c->getPhoneNumber() . "\n";
}

// Find memberships by contact ID
echo "\nFinding memberships by contact ID...\n";
$membershipsByContact = $contactGroupMembershipRepository->findByContactId($contact->getId());
echo "Found " . count($membershipsByContact) . " memberships for contact ID " . $contact->getId() . ":\n";
foreach ($membershipsByContact as $membership) {
    echo "- ID: " . $membership->getId() . ", Contact ID: " . $membership->getContactId() . ", Group ID: " . $membership->getGroupId() . "\n";
}

// Find memberships by group ID
echo "\nFinding memberships by group ID...\n";
$membershipsByGroup = $contactGroupMembershipRepository->findByGroupId($contactGroup->getId());
echo "Found " . count($membershipsByGroup) . " memberships for group ID " . $contactGroup->getId() . ":\n";
foreach ($membershipsByGroup as $membership) {
    echo "- ID: " . $membership->getId() . ", Contact ID: " . $membership->getContactId() . ", Group ID: " . $membership->getGroupId() . "\n";
}

// Count memberships by group ID
echo "\nCounting memberships by group ID...\n";
$membershipCountByGroup = $contactGroupMembershipRepository->countByGroupId($contactGroup->getId());
echo "Total memberships for group ID " . $contactGroup->getId() . ": " . $membershipCountByGroup . "\n";

// Count memberships by contact ID
echo "\nCounting memberships by contact ID...\n";
$membershipCountByContact = $contactGroupMembershipRepository->countByContactId($contact->getId());
echo "Total memberships for contact ID " . $contact->getId() . ": " . $membershipCountByContact . "\n";

// Remove contact from group
echo "\nRemoving contact from group...\n";
$result = $contactGroupRepository->removeContactFromGroup($contact->getId(), $contactGroup->getId());
echo "Remove contact from group result: " . ($result ? "Success" : "Failed") . "\n";

// Verify removal
$membershipCountAfterRemoval = $contactGroupMembershipRepository->countByContactId($contact->getId());
echo "Total memberships for contact ID " . $contact->getId() . " after removal: " . $membershipCountAfterRemoval . "\n";

// Delete contact
echo "\nDeleting contact...\n";
$contactRepository->delete($contact);
echo "Contact deleted.\n";

// Delete contact group
echo "\nDeleting contact group...\n";
$contactGroupId = $contactGroup->getId(); // Store the ID before deletion
$deleteResult = $contactGroupRepository->deleteById($contactGroupId);
echo "Delete result: " . ($deleteResult ? "Success" : "Failed") . "\n";

// Verify deletion
$deletedContactGroup = $contactGroupRepository->findById($contactGroupId);
echo "Contact group after deletion: " . ($deletedContactGroup ? "Still exists (error)" : "Successfully deleted") . "\n";

echo "\nDoctrine ORM ContactGroup test completed successfully!\n";
