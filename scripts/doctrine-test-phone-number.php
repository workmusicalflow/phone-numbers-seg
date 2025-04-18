<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\PhoneNumber;
use App\Repositories\Doctrine\PhoneNumberRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Get the entity manager directly from bootstrap file
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Create the repository
$phoneNumberRepository = new PhoneNumberRepository($entityManager);

// Check if the PhoneNumber table exists and create it if needed
try {
    // Try to get the metadata for the PhoneNumber entity
    $phoneNumberMetadata = $entityManager->getClassMetadata(PhoneNumber::class);

    // Create a schema tool
    $schemaTool = new SchemaTool($entityManager);

    // Check if the table exists by trying to find one record
    $testQuery = $entityManager->createQuery('SELECT COUNT(p) FROM ' . PhoneNumber::class . ' p');
    $testQuery->getResult();

    echo "PhoneNumber table exists.\n\n";
} catch (\Exception $e) {
    // If there's an error, the table might not exist
    echo "PhoneNumber table doesn't exist. Creating it...\n";

    try {
        // Create the schema for PhoneNumber entity
        $schemaTool->createSchema([$phoneNumberMetadata]);
        echo "PhoneNumber table created successfully.\n\n";
    } catch (\Exception $e) {
        // If there's an error creating the schema, try to update it
        echo "Error creating schema: " . $e->getMessage() . "\n";
        echo "Trying to update schema instead...\n";
        $schemaTool->updateSchema([$phoneNumberMetadata]);
        echo "PhoneNumber table updated successfully.\n\n";
    }
}

// Test creating a phone number
echo "Test de création d'un numéro de téléphone...\n";
$number = '+2250777104936';
$civility = 'M.';
$firstName = 'John';
$name = 'Doe';
$company = 'Test Company';
$sector = 'Technology';
$notes = 'Test notes';

try {
    $phoneNumber = $phoneNumberRepository->create(
        $number,
        $civility,
        $firstName,
        $name,
        $company,
        $sector,
        $notes
    );
    echo "Numéro de téléphone créé avec succès. ID: " . $phoneNumber->getId() . "\n";
    echo "Numéro: " . $phoneNumber->getNumber() . "\n";
    echo "Nom: " . $phoneNumber->getName() . "\n";
    echo "Prénom: " . $phoneNumber->getFirstName() . "\n";
    echo "Société: " . $phoneNumber->getCompany() . "\n";
    echo "\n";
} catch (Exception $e) {
    echo "Erreur lors de la création du numéro de téléphone: " . $e->getMessage() . "\n";
    exit(1);
}

// Test finding a phone number by ID
echo "Test de recherche d'un numéro de téléphone par ID...\n";
$phoneNumberId = $phoneNumber->getId();
$foundPhoneNumber = $phoneNumberRepository->findById($phoneNumberId);

if ($foundPhoneNumber) {
    echo "Numéro de téléphone trouvé avec succès. ID: " . $foundPhoneNumber->getId() . "\n";
    echo "Numéro: " . $foundPhoneNumber->getNumber() . "\n";
    echo "Nom: " . $foundPhoneNumber->getName() . "\n";
    echo "Prénom: " . $foundPhoneNumber->getFirstName() . "\n";
    echo "Société: " . $foundPhoneNumber->getCompany() . "\n";
    echo "\n";
} else {
    echo "Erreur: Numéro de téléphone non trouvé avec l'ID $phoneNumberId\n";
    exit(1);
}

// Test finding a phone number by number
echo "Test de recherche d'un numéro de téléphone par numéro...\n";
$foundPhoneNumber = $phoneNumberRepository->findByNumber($number);

if ($foundPhoneNumber) {
    echo "Numéro de téléphone trouvé avec succès. ID: " . $foundPhoneNumber->getId() . "\n";
    echo "Numéro: " . $foundPhoneNumber->getNumber() . "\n";
    echo "Nom: " . $foundPhoneNumber->getName() . "\n";
    echo "Prénom: " . $foundPhoneNumber->getFirstName() . "\n";
    echo "Société: " . $foundPhoneNumber->getCompany() . "\n";
    echo "\n";
} else {
    echo "Erreur: Numéro de téléphone non trouvé avec le numéro $number\n";
    exit(1);
}

// Test updating a phone number
echo "Test de mise à jour d'un numéro de téléphone...\n";
$foundPhoneNumber->setCompany('Updated Company');
$foundPhoneNumber->setNotes('Updated notes');
$updatedPhoneNumber = $phoneNumberRepository->save($foundPhoneNumber);

echo "Numéro de téléphone mis à jour avec succès. ID: " . $updatedPhoneNumber->getId() . "\n";
echo "Société: " . $updatedPhoneNumber->getCompany() . "\n";
echo "Notes: " . $updatedPhoneNumber->getNotes() . "\n";
echo "\n";

// Test searching phone numbers
echo "Test de recherche de numéros de téléphone...\n";
$searchResults = $phoneNumberRepository->search('John');

echo "Nombre de résultats: " . count($searchResults) . "\n";
foreach ($searchResults as $index => $result) {
    echo "Résultat #" . ($index + 1) . ":\n";
    echo "  ID: " . $result->getId() . "\n";
    echo "  Numéro: " . $result->getNumber() . "\n";
    echo "  Nom: " . $result->getName() . "\n";
    echo "  Prénom: " . $result->getFirstName() . "\n";
    echo "  Société: " . $result->getCompany() . "\n";
}
echo "\n";

// Test deleting a phone number
echo "Test de suppression d'un numéro de téléphone...\n";
$entityManager->remove($updatedPhoneNumber);
$entityManager->flush();

// Verify deletion
$deletedPhoneNumber = $phoneNumberRepository->findById($phoneNumberId);
if ($deletedPhoneNumber === null) {
    echo "Numéro de téléphone supprimé avec succès.\n";
} else {
    echo "Erreur: Le numéro de téléphone existe toujours après la suppression.\n";
    exit(1);
}

echo "\nTous les tests ont été exécutés avec succès!\n";
