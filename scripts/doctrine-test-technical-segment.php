<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\Segment;
use App\Repositories\Doctrine\TechnicalSegmentRepository;
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
$technicalSegmentRepository = new TechnicalSegmentRepository($entityManager);

// Check if the technical_segments table exists and create it if needed
try {
    // Try to get the metadata for the Segment entity
    $segmentMetadata = $entityManager->getClassMetadata(Segment::class);

    // Create a schema tool
    $schemaTool = new SchemaTool($entityManager);

    // Check if the table exists by trying to find one record
    $testQuery = $entityManager->createQuery('SELECT COUNT(s) FROM ' . Segment::class . ' s');
    $testQuery->getResult();

    echo "Technical segments table exists.\n\n";
} catch (\Exception $e) {
    // If there's an error, the table might not exist
    echo "Technical segments table doesn't exist. Creating it...\n";

    try {
        // Create the schema for Segment entity
        $schemaTool->createSchema([$segmentMetadata]);
        echo "Technical segments table created successfully.\n\n";
    } catch (\Exception $e) {
        // If there's an error creating the schema, try to update it
        echo "Error creating schema: " . $e->getMessage() . "\n";
        echo "Trying to update schema instead...\n";
        $schemaTool->updateSchema([$segmentMetadata]);
        echo "Technical segments table updated successfully.\n\n";
    }
}

// Create a test phone number ID (this would normally be a real phone number ID)
$phoneNumberId = 999;

// Test creating a segment
echo "Test de création d'un segment technique...\n";
$segmentType = Segment::TYPE_OPERATOR_NAME;
$value = 'Orange CI';

try {
    $segment = $technicalSegmentRepository->create(
        $segmentType,
        $value,
        $phoneNumberId
    );
    echo "Segment technique créé avec succès. ID: " . $segment->getId() . "\n";
    echo "Type: " . $segment->getSegmentType() . "\n";
    echo "Valeur: " . $segment->getValue() . "\n";
    echo "ID du numéro de téléphone: " . $segment->getPhoneNumberId() . "\n";
    echo "\n";
} catch (Exception $e) {
    echo "Erreur lors de la création du segment technique: " . $e->getMessage() . "\n";
    exit(1);
}

// Create another segment for the same phone number
echo "Test de création d'un autre segment technique pour le même numéro...\n";
$segmentType2 = Segment::TYPE_COUNTRY_CODE;
$value2 = '225';

try {
    $segment2 = $technicalSegmentRepository->create(
        $segmentType2,
        $value2,
        $phoneNumberId
    );
    echo "Segment technique créé avec succès. ID: " . $segment2->getId() . "\n";
    echo "Type: " . $segment2->getSegmentType() . "\n";
    echo "Valeur: " . $segment2->getValue() . "\n";
    echo "ID du numéro de téléphone: " . $segment2->getPhoneNumberId() . "\n";
    echo "\n";
} catch (Exception $e) {
    echo "Erreur lors de la création du segment technique: " . $e->getMessage() . "\n";
    exit(1);
}

// Test finding segments by phone number ID
echo "Test de recherche de segments par ID de numéro de téléphone...\n";
$segments = $technicalSegmentRepository->findByPhoneNumberId($phoneNumberId);

echo "Nombre de segments trouvés: " . count($segments) . "\n";
foreach ($segments as $index => $foundSegment) {
    echo "Segment #" . ($index + 1) . ":\n";
    echo "  ID: " . $foundSegment->getId() . "\n";
    echo "  Type: " . $foundSegment->getSegmentType() . "\n";
    echo "  Valeur: " . $foundSegment->getValue() . "\n";
    echo "  ID du numéro de téléphone: " . $foundSegment->getPhoneNumberId() . "\n";
}
echo "\n";

// Test finding segments by type
echo "Test de recherche de segments par type...\n";
$segmentsByType = $technicalSegmentRepository->findByType(Segment::TYPE_OPERATOR_NAME);

echo "Nombre de segments trouvés: " . count($segmentsByType) . "\n";
foreach ($segmentsByType as $index => $foundSegment) {
    echo "Segment #" . ($index + 1) . ":\n";
    echo "  ID: " . $foundSegment->getId() . "\n";
    echo "  Type: " . $foundSegment->getSegmentType() . "\n";
    echo "  Valeur: " . $foundSegment->getValue() . "\n";
    echo "  ID du numéro de téléphone: " . $foundSegment->getPhoneNumberId() . "\n";
}
echo "\n";

// Test updating a segment
echo "Test de mise à jour d'un segment technique...\n";
$segment->setValue('MTN CI');
$updatedSegment = $technicalSegmentRepository->save($segment);

echo "Segment technique mis à jour avec succès. ID: " . $updatedSegment->getId() . "\n";
echo "Nouvelle valeur: " . $updatedSegment->getValue() . "\n";
echo "\n";

// Test deleting segments by phone number ID
echo "Test de suppression de segments par ID de numéro de téléphone...\n";
$result = $technicalSegmentRepository->deleteByPhoneNumberId($phoneNumberId);

if ($result) {
    echo "Segments supprimés avec succès.\n";
} else {
    echo "Aucun segment n'a été supprimé.\n";
}

// Verify deletion
$remainingSegments = $technicalSegmentRepository->findByPhoneNumberId($phoneNumberId);
if (count($remainingSegments) === 0) {
    echo "Vérification réussie: aucun segment restant pour le numéro de téléphone ID $phoneNumberId.\n";
} else {
    echo "Erreur: " . count($remainingSegments) . " segments existent toujours pour le numéro de téléphone ID $phoneNumberId.\n";
    exit(1);
}

echo "\nTous les tests ont été exécutés avec succès!\n";
