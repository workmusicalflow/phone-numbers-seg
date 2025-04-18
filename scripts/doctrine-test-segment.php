<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\Segment;
use App\Repositories\Doctrine\SegmentRepository;
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
$segmentRepository = new SegmentRepository($entityManager);

// Check if the Segment table exists and create it if needed
try {
    // Try to get the metadata for the Segment entity
    $segmentMetadata = $entityManager->getClassMetadata(Segment::class);

    // Create a schema tool
    $schemaTool = new SchemaTool($entityManager);

    // Check if the table exists by trying to find one record
    $testQuery = $entityManager->createQuery('SELECT COUNT(s) FROM ' . Segment::class . ' s');
    $testQuery->getResult();

    echo "Segment table exists.\n\n";
} catch (\Exception $e) {
    // If there's an error, the table might not exist
    echo "Segment table doesn't exist. Creating it...\n";

    try {
        // Create the schema for Segment entity
        $schemaTool->createSchema([$segmentMetadata]);
        echo "Segment table created successfully.\n\n";
    } catch (\Exception $e) {
        // If there's an error creating the schema, try to update it
        echo "Error creating schema: " . $e->getMessage() . "\n";
        echo "Trying to update schema instead...\n";
        $schemaTool->updateSchema([$segmentMetadata]);
        echo "Segment table updated successfully.\n\n";
    }
}

// Test creating a segment
echo "Test de création d'un segment...\n";
$phoneNumberId = 1; // ID d'un numéro de téléphone existant
$segmentType = Segment::TYPE_COUNTRY_CODE;
$value = '+225';

try {
    $segment = $segmentRepository->create($segmentType, $value, $phoneNumberId);
    echo "Segment créé avec succès. ID: " . $segment->getId() . "\n";
    echo "Type: " . $segment->getSegmentType() . "\n";
    echo "Valeur: " . $segment->getValue() . "\n";
    echo "ID du numéro de téléphone: " . $segment->getPhoneNumberId() . "\n";
    echo "\n";
} catch (Exception $e) {
    echo "Erreur lors de la création du segment: " . $e->getMessage() . "\n";
    exit(1);
}

// Test finding a segment by ID
echo "Test de recherche d'un segment par ID...\n";
$segmentId = $segment->getId();
$foundSegment = $segmentRepository->findById($segmentId);

if ($foundSegment) {
    echo "Segment trouvé avec succès. ID: " . $foundSegment->getId() . "\n";
    echo "Type: " . $foundSegment->getSegmentType() . "\n";
    echo "Valeur: " . $foundSegment->getValue() . "\n";
    echo "ID du numéro de téléphone: " . $foundSegment->getPhoneNumberId() . "\n";
    echo "\n";
} else {
    echo "Erreur: Segment non trouvé avec l'ID $segmentId\n";
    exit(1);
}

// Test creating another segment for the same phone number
echo "Test de création d'un autre segment pour le même numéro de téléphone...\n";
$segmentType2 = Segment::TYPE_OPERATOR_CODE;
$value2 = 'MTN';

try {
    $segment2 = $segmentRepository->create($segmentType2, $value2, $phoneNumberId);
    echo "Segment créé avec succès. ID: " . $segment2->getId() . "\n";
    echo "Type: " . $segment2->getSegmentType() . "\n";
    echo "Valeur: " . $segment2->getValue() . "\n";
    echo "ID du numéro de téléphone: " . $segment2->getPhoneNumberId() . "\n";
    echo "\n";
} catch (Exception $e) {
    echo "Erreur lors de la création du segment: " . $e->getMessage() . "\n";
    exit(1);
}

// Test finding segments by phone number ID
echo "Test de recherche de segments par ID de numéro de téléphone...\n";
$segments = $segmentRepository->findByPhoneNumberId($phoneNumberId);
echo "Nombre de segments trouvés: " . count($segments) . "\n";

foreach ($segments as $index => $seg) {
    echo "Segment #" . ($index + 1) . ":\n";
    echo "  ID: " . $seg->getId() . "\n";
    echo "  Type: " . $seg->getSegmentType() . "\n";
    echo "  Valeur: " . $seg->getValue() . "\n";
    echo "  ID du numéro de téléphone: " . $seg->getPhoneNumberId() . "\n";
}
echo "\n";

// Test deleting segments by phone number ID
echo "Test de suppression de segments par ID de numéro de téléphone...\n";
$result = $segmentRepository->deleteByPhoneNumberId($phoneNumberId);

if ($result) {
    echo "Segments supprimés avec succès.\n";

    // Verify deletion
    $remainingSegments = $segmentRepository->findByPhoneNumberId($phoneNumberId);
    echo "Nombre de segments restants: " . count($remainingSegments) . "\n";

    if (count($remainingSegments) === 0) {
        echo "Vérification réussie: Tous les segments ont été supprimés.\n";
    } else {
        echo "Erreur: Des segments existent toujours après la suppression.\n";
        exit(1);
    }
} else {
    echo "Erreur: Impossible de supprimer les segments\n";
    exit(1);
}

echo "\nTous les tests ont été exécutés avec succès!\n";
