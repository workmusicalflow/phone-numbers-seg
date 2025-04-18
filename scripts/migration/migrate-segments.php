<?php

/**
 * Script de migration des segments personnalisés depuis la base de données legacy vers Doctrine ORM
 * 
 * Ce script récupère tous les segments personnalisés de la base de données legacy et les migre
 * vers les entités Doctrine ORM. Il vérifie également l'intégrité des données migrées.
 * 
 * Usage: php scripts/migration/migrate-segments.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Entities\CustomSegment as DoctrineSegment;
use Doctrine\ORM\EntityManagerInterface;

// Initialisation du conteneur DI
$containerDefinitions = require __DIR__ . '/../../src/config/di.php';
$containerBuilder = new \DI\ContainerBuilder();
$containerBuilder->addDefinitions($containerDefinitions);
$container = $containerBuilder->build();

// Récupération des dépendances
$entityManager = $container->get(EntityManagerInterface::class);
$legacySegmentRepository = $container->get(\App\Repositories\CustomSegmentRepository::class);
$doctrineSegmentRepository = $container->get(\App\Repositories\Interfaces\CustomSegmentRepositoryInterface::class);

// Configuration du logger
$logger = $container->get(\Psr\Log\LoggerInterface::class);
$logger->info('Début de la migration des segments');

try {
    // Récupération de tous les segments legacy
    $legacySegments = $legacySegmentRepository->findAll();
    $logger->info(sprintf('Nombre de segments à migrer: %d', count($legacySegments)));

    // Compteurs pour les statistiques
    $migratedCount = 0;
    $skippedCount = 0;
    $errorCount = 0;

    // Migration de chaque segment
    foreach ($legacySegments as $legacySegment) {
        try {
            $legacyName = $legacySegment->getName();
            $legacyId = $legacySegment->getId(); // For logging

            // Validate required fields from legacy data
            if (empty($legacyName)) {
                $logger->error(sprintf('Segment legacy ID %d a un nom vide ou null. Segment ignoré.', $legacyId));
                $errorCount++;
                continue; // Skip this segment
            }

            // Vérifier si le segment existe déjà dans Doctrine par son nom
            $existingSegment = $doctrineSegmentRepository->findOneBy(['name' => $legacyName]);

            if ($existingSegment) {
                $logger->info(sprintf('Le segment "%s" (ID legacy %d) existe déjà, mise à jour...', $legacyName, $legacyId));

                // Mise à jour des propriétés (y compris le pattern)
                $existingSegment->setName($legacyName); // Ensure name is set (might be case difference)
                $existingSegment->setDescription($legacySegment->getDescription());
                $existingSegment->setPattern($legacySegment->getPattern()); // Update pattern

                // Persister les modifications
                $entityManager->persist($existingSegment);
                $skippedCount++;
                // No need to continue here, proceed to flush logic below
            } else {
                // Création d'un nouveau segment personnalisé Doctrine
                $doctrineSegment = new DoctrineSegment();
                $doctrineSegment->setName($legacyName);
                $doctrineSegment->setDescription($legacySegment->getDescription());
                $doctrineSegment->setPattern($legacySegment->getPattern()); // Set pattern directly

                // Persister le nouveau segment
                $entityManager->persist($doctrineSegment);
                $migratedCount++;
            }

            // Flush tous les 50 segments pour éviter de surcharger la mémoire
            if (($migratedCount + $skippedCount) % 50 === 0) {
                $entityManager->flush();
                $logger->info(sprintf('Progression: %d segments traités', $migratedCount + $skippedCount));
            }
        } catch (\Exception $e) {
            // Log detailed error information
            $errorMsg = sprintf(
                'Erreur lors de la migration du segment "%s" (ID legacy: %d): %s',
                $legacySegment->getName() ?? 'N/A',
                $legacySegment->getId() ?? 'N/A',
                $e->getMessage()
            );
            $logger->error($errorMsg);
            $logger->debug("Stack Trace:\n" . $e->getTraceAsString()); // Log stack trace for debugging
            $errorCount++;
        }
    }

    // Flush final pour les segments restants
    $entityManager->flush();

    // Vérification de l'intégrité des données
    $logger->info('Vérification de l\'intégrité des données...');
    $doctrineSegments = $doctrineSegmentRepository->findAll();
    $integrityCheck = count($doctrineSegments) >= count($legacySegments) - $errorCount;

    if ($integrityCheck) {
        $logger->info('Vérification de l\'intégrité des données réussie');
    } else {
        $logger->error(sprintf(
            'Vérification de l\'intégrité des données échouée: %d segments dans Doctrine, %d attendus',
            count($doctrineSegments),
            count($legacySegments) - $errorCount
        ));
    }

    // Affichage des statistiques
    $logger->info(sprintf(
        'Migration terminée avec succès: %d segments migrés, %d mis à jour, %d erreurs',
        $migratedCount,
        $skippedCount,
        $errorCount
    ));
} catch (\Exception $e) {
    $logger->error(sprintf('Erreur lors de la migration des segments: %s', $e->getMessage()));
    exit(1);
}

$logger->info('Fin de la migration des segments');
exit(0);
