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
            $customSegmentId = $legacyCustomSegment->getId();

            // Vérifier que le segment personnalisé existe dans Doctrine
            $customSegment = $doctrineCustomSegmentRepository->find($customSegmentId);
            if (!$customSegment) {
                $logger->warning(sprintf(
                    'Segment personnalisé ID %d non trouvé dans Doctrine, ignoré...',
                    $customSegmentId
                ));
                $skippedCount++;
                continue;
            }

            // Récupérer les numéros de téléphone associés à ce segment
            $phoneNumbers = $legacyCustomSegment->getPhoneNumbers();
            $totalPhoneNumbers += count($phoneNumbers);
            $logger->info(sprintf(
                'Segment personnalisé "%s" (ID %d) a %d numéros de téléphone associés',
                $legacyCustomSegment->getName(),
                $customSegmentId,
                count($phoneNumbers)
            ));

            // Pour chaque numéro de téléphone, créer une association dans Doctrine
            foreach ($phoneNumbers as $phoneNumber) {
                try {
                    $phoneNumberId = $phoneNumber->getId();

                    // Vérifier que le numéro de téléphone existe dans Doctrine
                    $doctrinePhoneNumber = $doctrinePhoneNumberRepository->find($phoneNumberId);
                    if (!$doctrinePhoneNumber) {
                        $logger->warning(sprintf(
                            'Numéro de téléphone ID %d non trouvé dans Doctrine, ignoré...',
                            $phoneNumberId
                        ));
                        $skippedCount++;
                        continue;
                    }

                    // Vérifier si l'association existe déjà
                    $existingAssociation = $doctrinePhoneNumberSegmentRepository->findOneBy([
                        'phoneNumberId' => $phoneNumberId,
                        'customSegmentId' => $customSegmentId
                    ]);

                    if ($existingAssociation) {
                        $logger->info(sprintf(
                            'Association entre le numéro ID %d et le segment ID %d existe déjà, ignorée...',
                            $phoneNumberId,
                            $customSegmentId
                        ));
                        $skippedCount++;
                        continue;
                    }

                    // Création d'une nouvelle association
                    $doctrineAssociation = new DoctrinePhoneNumberSegment();
                    $doctrineAssociation->setPhoneNumberId($phoneNumberId);
                    $doctrineAssociation->setCustomSegmentId($customSegmentId);
                    $doctrineAssociation->setCreatedAt(new \DateTime());

                    // Persister la nouvelle association
                    $entityManager->persist($doctrineAssociation);
                    $migratedCount++;

                    // Flush tous les 50 associations pour éviter de surcharger la mémoire
                    if ($migratedCount % 50 === 0) {
                        $entityManager->flush();
                        $logger->info(sprintf('Progression: %d associations migrées', $migratedCount));
                    }
                } catch (\Exception $e) {
                    $logger->error(sprintf(
                        'Erreur lors de la migration de l\'association entre le numéro ID %d et le segment ID %d: %s',
                        $phoneNumberId,
                        $customSegmentId,
                        $e->getMessage()
                    ));
                    $errorCount++;
                }
            }
        } catch (\Exception $e) {
            $logger->error(sprintf(
                'Erreur lors du traitement du segment personnalisé ID %d: %s',
                $legacyCustomSegment->getId(),
                $e->getMessage()
            ));
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
