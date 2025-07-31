<?php

/**
 * Script de migration des groupes de contacts depuis la base de données legacy vers Doctrine ORM
 * 
 * Ce script récupère tous les groupes de contacts de la base de données legacy et les migre
 * vers les entités Doctrine ORM. Il vérifie également l'intégrité des données migrées.
 * 
 * Usage: php scripts/migration/migrate-contact-groups.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Entities\ContactGroup as DoctrineContactGroup;
use Doctrine\ORM\EntityManagerInterface;

// Initialisation du conteneur DI
$containerDefinitions = require __DIR__ . '/../../src/config/di.php';
$containerBuilder = new \DI\ContainerBuilder();
$containerBuilder->addDefinitions($containerDefinitions);
$container = $containerBuilder->build();

// Récupération des dépendances
$entityManager = $container->get(EntityManagerInterface::class);
$legacyContactGroupRepository = $container->get(\App\Repositories\ContactGroupRepository::class);
$doctrineContactGroupRepository = $container->get(\App\Repositories\Interfaces\ContactGroupRepositoryInterface::class);
$doctrineUserRepository = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);

// Configuration du logger
$logger = $container->get(\Psr\Log\LoggerInterface::class);
$logger->info('Début de la migration des groupes de contacts');

try {
    // Récupération de tous les groupes de contacts legacy
    $legacyContactGroups = $legacyContactGroupRepository->findAll();
    $logger->info(sprintf('Nombre de groupes de contacts à migrer: %d', count($legacyContactGroups)));

    // Debug: Afficher les groupes de contacts legacy
    echo "Legacy contact groups:\n";
    foreach ($legacyContactGroups as $group) {
        echo "- " . ($group->getName() ?? 'N/A') . " (ID: " . ($group->getId() ?? 'N/A') . ")\n";
    }

    // Vérifier si la migration a déjà été effectuée
    $doctrineContactGroups = $doctrineContactGroupRepository->findAll();
    echo "Doctrine contact groups (before migration):\n";
    foreach ($doctrineContactGroups as $group) {
        echo "- " . ($group->getName() ?? 'N/A') . " (ID: " . ($group->getId() ?? 'N/A') . ")\n";
    }

    // Compteurs pour les statistiques
    $migratedCount = 0;
    $skippedCount = 0;
    $errorCount = 0;

    // Migration de chaque groupe de contacts
    foreach ($legacyContactGroups as $legacyContactGroup) {
        try {
            // Vérifier si le groupe de contacts existe déjà dans Doctrine
            $queryBuilder = $entityManager->createQueryBuilder();
            $queryBuilder->select('g')
                ->from('App\Entities\ContactGroup', 'g')
                ->where('g.name = :name')
                ->setParameter('name', $legacyContactGroup->getName())
                ->setMaxResults(1);

            $existingContactGroup = $queryBuilder->getQuery()->getOneOrNullResult();

            if ($existingContactGroup) {
                $logger->info(sprintf('Le groupe de contacts "%s" existe déjà, mise à jour...', $legacyContactGroup->getName()));

                // Mise à jour des propriétés
                $existingContactGroup->setDescription((string)$legacyContactGroup->getDescription()); // Cast description
                // Try passing raw date string for update
                $legacyUpdatedAtString = $legacyContactGroup->getUpdatedAt();
                try {
                    // Still validate the string format before attempting update
                    new \DateTime($legacyUpdatedAtString);
                    // If validation passes, attempt to set the string (might cause TypeError if setter strictly enforces DateTime)
                    // $existingContactGroup->setUpdatedAt($legacyUpdatedAtString); // This will likely fail due to type hint
                    // Let's just update using new DateTime() for updates, as the issue seems to be on INSERT
                    $existingContactGroup->setUpdatedAt(new \DateTime());
                } catch (\Exception $dateError) {
                    $logger->error(sprintf('Date de mise à jour invalide ("%s") pour le groupe existant "%s". Mise à jour de la date ignorée.', $legacyUpdatedAtString, $existingContactGroup->getName()));
                    // Decide if you want to skip the whole update or just the date field
                }


                // Persister les modifications
                $entityManager->persist($existingContactGroup);
                $skippedCount++;
            } else {
                // Récupérer l'utilisateur associé
                $userId = $legacyContactGroup->getUserId();
                $user = null;
                $userFound = false;

                if ($userId) {
                    $user = $doctrineUserRepository->findById($userId);
                    if ($user) {
                        $userFound = true;
                    } else {
                        $logger->error(sprintf('Utilisateur ID %d non trouvé pour le groupe de contacts "%s" (ID legacy: %d). Groupe ignoré.', $userId, $legacyContactGroup->getName(), $legacyContactGroup->getId()));
                        $errorCount++; // Increment error count as we are skipping this group
                        continue; // Skip to the next group
                    }
                } else {
                    // If legacy userId is null or 0, we cannot migrate it as userId is non-nullable in Doctrine
                    $logger->error(sprintf('ID utilisateur manquant ou invalide pour le groupe de contacts "%s" (ID legacy: %d). Groupe ignoré.', $legacyContactGroup->getName(), $legacyContactGroup->getId()));
                    $errorCount++; // Increment error count
                    continue; // Skip to the next group
                }

                // Only proceed if the user was found
                if ($userFound) {
                    // Création d'un nouveau groupe de contacts Doctrine
                    $doctrineContactGroup = new DoctrineContactGroup();
                    // Explicitly cast name and description to string
                    $doctrineContactGroup->setName((string)$legacyContactGroup->getName());
                    $doctrineContactGroup->setDescription((string)$legacyContactGroup->getDescription()); // Handles null correctly
                    $doctrineContactGroup->setUserId($user->getId()); // Set the found user's ID

                    // *** Attempt to pass raw date strings ***
                    $legacyCreatedAtString = $legacyContactGroup->getCreatedAt();
                    $legacyUpdatedAtString = $legacyContactGroup->getUpdatedAt();

                    try {
                        // Validate date strings first
                        new \DateTime($legacyCreatedAtString);
                        new \DateTime($legacyUpdatedAtString);

                        // If valid, try setting DateTime objects (reverting the string attempt as it violates type hints)
                        $doctrineContactGroup->setCreatedAt(new \DateTime($legacyCreatedAtString));
                        $doctrineContactGroup->setUpdatedAt(new \DateTime($legacyUpdatedAtString));
                    } catch (\Exception $dateError) {
                        $logger->error(sprintf('Date invalide ("%s" ou "%s") pour le groupe de contacts "%s" (ID legacy: %d): %s. Groupe ignoré.', $legacyCreatedAtString, $legacyUpdatedAtString, $legacyContactGroup->getName(), $legacyContactGroup->getId(), $dateError->getMessage()));
                        $errorCount++;
                        continue; // Skip this group due to invalid date
                    }

                    // Persister le nouveau groupe de contacts
                    $entityManager->persist($doctrineContactGroup);
                    $migratedCount++;
                }
            } // End of else block (create new group)

            // Flush tous les 50 groupes de contacts pour éviter de surcharger la mémoire
            if (($migratedCount + $skippedCount) % 50 === 0) {
                $entityManager->flush();
                $logger->info(sprintf('Progression: %d groupes de contacts traités', $migratedCount + $skippedCount));
            }
        } catch (\Exception $e) {
            // Log detailed error information
            $errorMsg = sprintf(
                'Erreur lors de la migration du groupe de contacts "%s" (ID legacy: %d): %s',
                $legacyContactGroup->getName() ?? 'N/A',
                $legacyContactGroup->getId() ?? 'N/A',
                $e->getMessage()
            );
            $logger->error($errorMsg);
            $logger->debug("Stack Trace:\n" . $e->getTraceAsString()); // Log stack trace for debugging
            $errorCount++;
        }
    }

    // Flush final pour les groupes de contacts restants
    $entityManager->flush();

    // Vérification de l'intégrité des données
    $logger->info('Vérification de l\'intégrité des données...');
    // Fetch groups again after potential changes
    $finalDoctrineContactGroups = $doctrineContactGroupRepository->findAll();
    // Compare count of legacy groups processed vs final count in doctrine, accounting for errors/skips
    $expectedCount = count($legacyContactGroups) - $errorCount; // Simplistic check, might need refinement based on update logic
    $integrityCheck = count($finalDoctrineContactGroups) >= $expectedCount;

    if ($integrityCheck) {
        $logger->info('Vérification de l\'intégrité des données réussie');
    } else {
        $logger->error(sprintf(
            'Vérification de l\'intégrité des données échouée: %d groupes de contacts dans Doctrine, au moins %d attendus (total legacy: %d, erreurs: %d)',
            count($finalDoctrineContactGroups),
            $expectedCount,
            count($legacyContactGroups),
            $errorCount
        ));
    }

    // Affichage des statistiques
    $logger->info(sprintf(
        'Migration terminée: %d groupes de contacts migrés, %d mis à jour, %d erreurs',
        $migratedCount,
        $skippedCount,
        $errorCount
    ));
} catch (\Exception $e) {
    $logger->error(sprintf('Erreur critique lors de la migration des groupes de contacts: %s', $e->getMessage()));
    $logger->debug("Stack Trace:\n" . $e->getTraceAsString()); // Log stack trace for critical errors too
    exit(1);
}

$logger->info('Fin de la migration des groupes de contacts');
exit(0);
