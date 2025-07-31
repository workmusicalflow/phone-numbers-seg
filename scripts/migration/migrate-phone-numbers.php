<?php

/**
 * Script de migration des numéros de téléphone depuis la base de données legacy vers Doctrine ORM
 * 
 * Ce script récupère tous les numéros de téléphone de la base de données legacy et les migre
 * vers les entités Doctrine ORM. Il vérifie également l'intégrité des données migrées.
 * 
 * Usage: php scripts/migration/migrate-phone-numbers.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Entities\PhoneNumber as DoctrinePhoneNumber;
use Doctrine\ORM\EntityManagerInterface;

// Initialisation du conteneur DI
$containerDefinitions = require __DIR__ . '/../../src/config/di.php';
$containerBuilder = new \DI\ContainerBuilder();
$containerBuilder->addDefinitions($containerDefinitions);
$container = $containerBuilder->build();

// Récupération des dépendances
$entityManager = $container->get(EntityManagerInterface::class);
$legacyPhoneNumberRepository = $container->get(\App\Repositories\PhoneNumberRepository::class);
$doctrinePhoneNumberRepository = $container->get(\App\Repositories\Interfaces\PhoneNumberRepositoryInterface::class);
$doctrineUserRepository = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);

// Configuration du logger
$logger = $container->get(\Psr\Log\LoggerInterface::class);
$logger->info('Début de la migration des numéros de téléphone');

try {
    // Récupération de tous les numéros de téléphone legacy
    $legacyPhoneNumbers = $legacyPhoneNumberRepository->findAll();
    $logger->info(sprintf('Nombre de numéros de téléphone à migrer: %d', count($legacyPhoneNumbers)));

    // Compteurs pour les statistiques
    $migratedCount = 0;
    $skippedCount = 0;
    $errorCount = 0;

    // Migration de chaque numéro de téléphone
    foreach ($legacyPhoneNumbers as $legacyPhoneNumber) {
        try {
            // Vérifier si le numéro de téléphone existe déjà dans Doctrine
            $existingPhoneNumber = $doctrinePhoneNumberRepository->findByNumber($legacyPhoneNumber->getNumber());

            if ($existingPhoneNumber) {
                $logger->info(sprintf('Le numéro de téléphone %s existe déjà, mise à jour...', $legacyPhoneNumber->getNumber()));

                // Mise à jour des propriétés
                $existingPhoneNumber->setNotes($legacyPhoneNumber->getNotes());
                $existingPhoneNumber->setCivility($legacyPhoneNumber->getCivility());
                $existingPhoneNumber->setFirstName($legacyPhoneNumber->getFirstName());
                $existingPhoneNumber->setName($legacyPhoneNumber->getName());
                $existingPhoneNumber->setCompany($legacyPhoneNumber->getCompany());
                $existingPhoneNumber->setSector($legacyPhoneNumber->getSector());

                // Persister les modifications
                $entityManager->persist($existingPhoneNumber);
                $skippedCount++;
                continue;
            }

            // Note: Les numéros de téléphone ne sont pas associés à des utilisateurs dans le modèle Doctrine

            // Création d'un nouveau numéro de téléphone Doctrine
            $doctrinePhoneNumber = new DoctrinePhoneNumber();
            $doctrinePhoneNumber->setNumber($legacyPhoneNumber->getNumber());
            $doctrinePhoneNumber->setNotes($legacyPhoneNumber->getNotes());
            $doctrinePhoneNumber->setCivility($legacyPhoneNumber->getCivility());
            $doctrinePhoneNumber->setFirstName($legacyPhoneNumber->getFirstName());
            $doctrinePhoneNumber->setName($legacyPhoneNumber->getName());
            $doctrinePhoneNumber->setCompany($legacyPhoneNumber->getCompany());
            $doctrinePhoneNumber->setSector($legacyPhoneNumber->getSector());
            $doctrinePhoneNumber->setDateAdded(new \DateTime($legacyPhoneNumber->getDateAdded()));

            // Persister le nouveau numéro de téléphone
            $entityManager->persist($doctrinePhoneNumber);
            $migratedCount++;

            // Flush tous les 50 numéros de téléphone pour éviter de surcharger la mémoire
            if (($migratedCount + $skippedCount) % 50 === 0) {
                $entityManager->flush();
                $logger->info(sprintf('Progression: %d numéros de téléphone traités', $migratedCount + $skippedCount));
            }
        } catch (\Exception $e) {
            $logger->error(sprintf(
                'Erreur lors de la migration du numéro de téléphone %s: %s',
                $legacyPhoneNumber->getNumber(),
                $e->getMessage()
            ));
            $errorCount++;
        }
    }

    // Flush final pour les numéros de téléphone restants
    $entityManager->flush();

    // Vérification de l'intégrité des données
    $logger->info('Vérification de l\'intégrité des données...');
    $doctrinePhoneNumbers = $doctrinePhoneNumberRepository->findAll();
    $integrityCheck = count($doctrinePhoneNumbers) >= count($legacyPhoneNumbers) - $errorCount;

    if ($integrityCheck) {
        $logger->info('Vérification de l\'intégrité des données réussie');
    } else {
        $logger->error(sprintf(
            'Vérification de l\'intégrité des données échouée: %d numéros de téléphone dans Doctrine, %d attendus',
            count($doctrinePhoneNumbers),
            count($legacyPhoneNumbers) - $errorCount
        ));
    }

    // Affichage des statistiques
    $logger->info(sprintf(
        'Migration terminée avec succès: %d numéros de téléphone migrés, %d mis à jour, %d erreurs',
        $migratedCount,
        $skippedCount,
        $errorCount
    ));
} catch (\Exception $e) {
    $logger->error(sprintf('Erreur lors de la migration des numéros de téléphone: %s', $e->getMessage()));
    exit(1);
}

$logger->info('Fin de la migration des numéros de téléphone');
exit(0);
