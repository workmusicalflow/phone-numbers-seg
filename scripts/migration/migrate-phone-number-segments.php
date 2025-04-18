<?php

/**
 * Script de migration des segments de numéros de téléphone depuis la base de données legacy vers Doctrine ORM
 * 
 * Ce script récupère tous les segments de numéros de téléphone de la base de données legacy et les migre
 * vers les entités Doctrine ORM. Il vérifie également l'intégrité des données migrées.
 * 
 * Usage: php scripts/migration/migrate-phone-number-segments.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Entities\PhoneNumberSegment as DoctrinePhoneNumberSegment;
use Doctrine\ORM\EntityManagerInterface;

// Initialisation du conteneur DI
$containerDefinitions = require __DIR__ . '/../../src/config/di.php';
$containerBuilder = new \DI\ContainerBuilder();
$containerBuilder->addDefinitions($containerDefinitions);
$container = $containerBuilder->build();

// Récupération des dépendances
$entityManager = $container->get(EntityManagerInterface::class);
$legacyCustomSegmentRepository = $container->get(\App\Repositories\CustomSegmentRepository::class);
$doctrinePhoneNumberSegmentRepository = $container->get(\App\Repositories\Interfaces\PhoneNumberSegmentRepositoryInterface::class);
$doctrinePhoneNumberRepository = $container->get(\App\Repositories\Interfaces\PhoneNumberRepositoryInterface::class);
$doctrineCustomSegmentRepository = $container->get(\App\Repositories\Interfaces\CustomSegmentRepositoryInterface::class);

// Configuration du logger
$logger = $container->get(\Psr\Log\LoggerInterface::class);
$logger->info('Début de la migration des segments de numéros de téléphone');

try {
    // Récupération de tous les segments personnalisés legacy
    $legacyCustomSegments = $legacyCustomSegmentRepository->findAll();
    $logger->info(sprintf('Nombre de segments personnalisés à traiter: %d', count($legacyCustomSegments)));

    // Compteurs pour les statistiques
    $migratedCount = 0;
    $skippedCount = 0;
    $errorCount = 0;
    $totalPhoneNumbers = 0;

    // Pour chaque segment personnalisé, récupérer les numéros de téléphone associés
    foreach ($legacyCustomSegments as $legacyCustomSegment) {
        try {
            $legacySegmentName = $legacyCustomSegment->getName();
            $legacySegmentId = $legacyCustomSegment->getId(); // For logging

            // Trouver le segment personnalisé dans Doctrine par son nom
            $doctrineCustomSegment = $doctrineCustomSegmentRepository->findOneBy(['name' => $legacySegmentName]);
            if (!$doctrineCustomSegment) {
                $logger->warning(sprintf(
                    'Segment personnalisé "%s" (ID legacy %d) non trouvé dans Doctrine. Associations ignorées.',
                    $legacySegmentName,
                    $legacySegmentId
                ));
                // We don't know how many phone numbers were associated, so can't accurately update skippedCount here easily.
                // Consider adding logic to count skipped phone numbers if precise counts are needed.
                continue; // Skip this entire legacy segment
            }
            $doctrineSegmentId = $doctrineCustomSegment->getId(); // Get the actual Doctrine ID

            // Récupérer les numéros de téléphone associés à ce segment legacy
            // Assuming getPhoneNumbers() returns legacy PhoneNumber objects or similar structure
            $legacyPhoneNumbers = $legacyCustomSegment->getPhoneNumbers();
            $totalPhoneNumbers += count($legacyPhoneNumbers); // Keep track for final count check
            $logger->info(sprintf(
                'Segment personnalisé "%s" (ID Doctrine %d) a %d numéros de téléphone associés (legacy)',
                $legacySegmentName,
                $doctrineSegmentId,
                count($legacyPhoneNumbers)
            ));

            // Pour chaque numéro de téléphone legacy, créer une association dans Doctrine
            foreach ($legacyPhoneNumbers as $legacyPhoneNumber) {
                try {
                    // Assuming the legacy phone number object has getNumber() and getId() methods
                    $legacyPhoneNumberStr = $legacyPhoneNumber->getNumber();
                    $legacyPhoneNumberId = $legacyPhoneNumber->getId(); // For logging

                    if (empty($legacyPhoneNumberStr)) {
                        $logger->warning(sprintf(
                            'Numéro de téléphone legacy ID %d associé au segment "%s" est vide. Association ignorée.',
                            $legacyPhoneNumberId,
                            $legacySegmentName
                        ));
                        $errorCount++;
                        continue;
                    }

                    // Trouver le numéro de téléphone dans Doctrine par son numéro
                    $doctrinePhoneNumber = $doctrinePhoneNumberRepository->findOneBy(['phoneNumber' => $legacyPhoneNumberStr]);
                    if (!$doctrinePhoneNumber) {
                        $logger->warning(sprintf(
                            'Numéro de téléphone "%s" (ID legacy %d) non trouvé dans Doctrine. Association avec segment "%s" ignorée.',
                            $legacyPhoneNumberStr,
                            $legacyPhoneNumberId,
                            $legacySegmentName
                        ));
                        $errorCount++; // Count as error because the phone number should exist if its migration succeeded
                        continue; // Skip this association
                    }
                    $doctrinePhoneNumberId = $doctrinePhoneNumber->getId(); // Get the actual Doctrine ID

                    // Vérifier si l'association existe déjà en utilisant les IDs Doctrine
                    $existingAssociation = $doctrinePhoneNumberSegmentRepository->findOneBy([
                        'phoneNumberId' => $doctrinePhoneNumberId,
                        'customSegmentId' => $doctrineSegmentId
                    ]);

                    if ($existingAssociation) {
                        $logger->info(sprintf(
                            'Association entre numéro "%s" (ID %d) et segment "%s" (ID %d) existe déjà. Ignorée.',
                            $legacyPhoneNumberStr,
                            $doctrinePhoneNumberId,
                            $legacySegmentName,
                            $doctrineSegmentId
                        ));
                        $skippedCount++;
                        continue; // Skip creating duplicate
                    }

                    // Création d'une nouvelle association avec les IDs Doctrine
                    $doctrineAssociation = new DoctrinePhoneNumberSegment();
                    $doctrineAssociation->setPhoneNumberId($doctrinePhoneNumberId); // Use Doctrine ID
                    $doctrineAssociation->setCustomSegmentId($doctrineSegmentId); // Use Doctrine ID
                    $doctrineAssociation->setCreatedAt(new \DateTime()); // Set creation time

                    // Persister la nouvelle association
                    $entityManager->persist($doctrineAssociation);
                    $migratedCount++;

                    // Flush tous les 50 associations pour éviter de surcharger la mémoire
                    if ($migratedCount % 50 === 0) {
                        $entityManager->flush();
                        $logger->info(sprintf('Progression: %d associations migrées', $migratedCount));
                    }
                } catch (\Exception $e) {
                    // Log detailed error information for association migration
                    $errorMsg = sprintf(
                        'Erreur lors de la migration de l\'association entre numéro "%s" (ID legacy %d) et segment "%s" (ID legacy %d): %s',
                        $legacyPhoneNumber->getNumber() ?? 'N/A',
                        $legacyPhoneNumber->getId() ?? 'N/A',
                        $legacySegmentName ?? 'N/A',
                        $legacySegmentId ?? 'N/A',
                        $e->getMessage()
                    );
                    $logger->error($errorMsg);
                    $logger->debug("Stack Trace:\n" . $e->getTraceAsString()); // Log stack trace
                    $errorCount++;
                }
            }
        } catch (\Exception $e) {
            // Log detailed error information for segment processing
            $errorMsg = sprintf(
                'Erreur lors du traitement du segment personnalisé "%s" (ID legacy %d): %s',
                $legacyCustomSegment->getName() ?? 'N/A',
                $legacyCustomSegment->getId() ?? 'N/A',
                $e->getMessage() // Removed extra parenthesis here
            );
            $logger->error($errorMsg); // Also log the error message
            $logger->debug("Stack Trace:\n" . $e->getTraceAsString()); // Log stack trace
            $errorCount++;
        }
    }

    // Flush final pour les associations restantes
    $entityManager->flush();

    // Vérification de l'intégrité des données
    $logger->info('Vérification de l\'intégrité des données...');
    $doctrineAssociations = $doctrinePhoneNumberSegmentRepository->findAll();
    $integrityCheck = count($doctrineAssociations) >= $totalPhoneNumbers - $errorCount - $skippedCount;

    if ($integrityCheck) {
        $logger->info('Vérification de l\'intégrité des données réussie');
    } else {
        $logger->error(sprintf(
            'Vérification de l\'intégrité des données échouée: %d associations dans Doctrine, %d attendues',
            count($doctrineAssociations),
            $totalPhoneNumbers - $errorCount - $skippedCount
        ));
    }

    // Affichage des statistiques
    $logger->info(sprintf(
        'Migration terminée avec succès: %d associations migrées, %d ignorées, %d erreurs',
        $migratedCount,
        $skippedCount,
        $errorCount
    ));
} catch (\Exception $e) {
    $logger->error(sprintf('Erreur lors de la migration des associations de segments: %s', $e->getMessage()));
    exit(1);
}

$logger->info('Fin de la migration des associations de segments');
exit(0);
