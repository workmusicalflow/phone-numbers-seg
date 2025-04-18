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

    // Compteurs pour les statistiques
    $migratedCount = 0;
    $skippedCount = 0;
    $errorCount = 0;

    // Migration de chaque groupe de contacts
    foreach ($legacyContactGroups as $legacyContactGroup) {
        try {
            // Vérifier si le groupe de contacts existe déjà dans Doctrine
            $existingContactGroup = $doctrineContactGroupRepository->findByName($legacyContactGroup->getName());

            if ($existingContactGroup) {
                $logger->info(sprintf('Le groupe de contacts %s existe déjà, mise à jour...', $legacyContactGroup->getName()));

                // Mise à jour des propriétés
                $existingContactGroup->setDescription($legacyContactGroup->getDescription());

                // Persister les modifications
                $entityManager->persist($existingContactGroup);
                $skippedCount++;
            } else {
                // Récupérer l'utilisateur associé
                $userId = $legacyContactGroup->getUserId();
                $user = null;

                if ($userId) {
                    $user = $doctrineUserRepository->find($userId);
                    if (!$user) {
                        $logger->warning(sprintf('Utilisateur ID %d non trouvé pour le groupe de contacts %s', $userId, $legacyContactGroup->getName()));
                    }
                }

                // Création d'un nouveau groupe de contacts Doctrine
                $doctrineContactGroup = new DoctrineContactGroup();
                $doctrineContactGroup->setName($legacyContactGroup->getName());
                $doctrineContactGroup->setDescription($legacyContactGroup->getDescription());

                if ($user) {
                    $doctrineContactGroup->setUserId($user->getId());
                } else if ($userId) {
                    $doctrineContactGroup->setUserId($userId);
                }

                $doctrineContactGroup->setCreatedAt(new \DateTime($legacyContactGroup->getCreatedAt()));
                $doctrineContactGroup->setUpdatedAt(new \DateTime($legacyContactGroup->getUpdatedAt()));

                // Persister le nouveau groupe de contacts
                $entityManager->persist($doctrineContactGroup);
                $migratedCount++;
            }

            // Flush tous les 50 groupes de contacts pour éviter de surcharger la mémoire
            if (($migratedCount + $skippedCount) % 50 === 0) {
                $entityManager->flush();
                $logger->info(sprintf('Progression: %d groupes de contacts traités', $migratedCount + $skippedCount));
            }
        } catch (\Exception $e) {
            $logger->error(sprintf('Erreur lors de la migration du groupe de contacts %s: %s', $legacyContactGroup->getName(), $e->getMessage()));
            $errorCount++;
        }
    }

    // Flush final pour les groupes de contacts restants
    $entityManager->flush();

    // Vérification de l'intégrité des données
    $logger->info('Vérification de l\'intégrité des données...');
    $doctrineContactGroups = $doctrineContactGroupRepository->findAll();
    $integrityCheck = count($doctrineContactGroups) >= count($legacyContactGroups) - $errorCount;

    if ($integrityCheck) {
        $logger->info('Vérification de l\'intégrité des données réussie');
    } else {
        $logger->error(sprintf(
            'Vérification de l\'intégrité des données échouée: %d groupes de contacts dans Doctrine, %d attendus',
            count($doctrineContactGroups),
            count($legacyContactGroups) - $errorCount
        ));
    }

    // Affichage des statistiques
    $logger->info(sprintf(
        'Migration terminée avec succès: %d groupes de contacts migrés, %d mis à jour, %d erreurs',
        $migratedCount,
        $skippedCount,
        $errorCount
    ));
} catch (\Exception $e) {
    $logger->error(sprintf('Erreur lors de la migration des groupes de contacts: %s', $e->getMessage()));
    exit(1);
}

$logger->info('Fin de la migration des groupes de contacts');
exit(0);
