<?php

/**
 * Script de migration des appartenances aux groupes de contacts depuis la base de données legacy vers Doctrine ORM
 * 
 * Ce script récupère toutes les appartenances aux groupes de contacts de la base de données legacy et les migre
 * vers les entités Doctrine ORM. Il vérifie également l'intégrité des données migrées.
 * 
 * Usage: php scripts/migration/migrate-contact-group-memberships.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Entities\ContactGroupMembership as DoctrineContactGroupMembership;
use Doctrine\ORM\EntityManagerInterface;

// Initialisation du conteneur DI
$containerDefinitions = require __DIR__ . '/../../src/config/di.php';
$containerBuilder = new \DI\ContainerBuilder();
$containerBuilder->addDefinitions($containerDefinitions);
$container = $containerBuilder->build();

// Récupération des dépendances
$entityManager = $container->get(EntityManagerInterface::class);
$legacyMembershipRepository = $container->get(\App\Repositories\ContactGroupMembershipRepository::class);
$doctrineMembershipRepository = $container->get(\App\Repositories\Interfaces\ContactGroupMembershipRepositoryInterface::class);
$doctrineContactRepository = $container->get(\App\Repositories\Interfaces\ContactRepositoryInterface::class);
$doctrineContactGroupRepository = $container->get(\App\Repositories\Interfaces\ContactGroupRepositoryInterface::class);

// Configuration du logger
$logger = $container->get(\Psr\Log\LoggerInterface::class);
$logger->info('Début de la migration des appartenances aux groupes de contacts');

try {
    // Récupération de toutes les appartenances aux groupes de contacts legacy
    $legacyMemberships = $legacyMembershipRepository->findAll();
    $logger->info(sprintf('Nombre d\'appartenances à migrer: %d', count($legacyMemberships)));

    // Compteurs pour les statistiques
    $migratedCount = 0;
    $skippedCount = 0;
    $errorCount = 0;

    // Migration de chaque appartenance
    foreach ($legacyMemberships as $legacyMembership) {
        try {
            $contactId = $legacyMembership->getContactId();
            $groupId = $legacyMembership->getGroupId();

            // Vérifier si l'appartenance existe déjà dans Doctrine
            $existingMembership = $doctrineMembershipRepository->findByContactIdAndGroupId($contactId, $groupId);

            if ($existingMembership) {
                $logger->info(sprintf('L\'appartenance (contact: %d, groupe: %d) existe déjà, ignorée...', $contactId, $groupId));
                $skippedCount++;
                continue;
            }

            // Récupérer le contact et le groupe
            $contact = $doctrineContactRepository->find($contactId);
            $group = $doctrineContactGroupRepository->find($groupId);

            if (!$contact) {
                $logger->warning(sprintf('Contact ID %d non trouvé pour l\'appartenance', $contactId));
                $errorCount++;
                continue;
            }

            if (!$group) {
                $logger->warning(sprintf('Groupe ID %d non trouvé pour l\'appartenance', $groupId));
                $errorCount++;
                continue;
            }

            // Création d'une nouvelle appartenance Doctrine
            $doctrineMembership = new DoctrineContactGroupMembership();
            $doctrineMembership->setContactId($contactId);
            $doctrineMembership->setGroupId($groupId);
            $doctrineMembership->setCreatedAt(new \DateTime($legacyMembership->getCreatedAt()));

            // Persister la nouvelle appartenance
            $entityManager->persist($doctrineMembership);
            $migratedCount++;

            // Flush toutes les 50 appartenances pour éviter de surcharger la mémoire
            if (($migratedCount + $skippedCount) % 50 === 0) {
                $entityManager->flush();
                $logger->info(sprintf('Progression: %d appartenances traitées', $migratedCount + $skippedCount));
            }
        } catch (\Exception $e) {
            $logger->error(sprintf(
                'Erreur lors de la migration de l\'appartenance (contact: %d, groupe: %d): %s',
                $legacyMembership->getContactId(),
                $legacyMembership->getGroupId(),
                $e->getMessage()
            ));
            $errorCount++;
        }
    }

    // Flush final pour les appartenances restantes
    $entityManager->flush();

    // Vérification de l'intégrité des données
    $logger->info('Vérification de l\'intégrité des données...');
    $doctrineMemberships = $doctrineMembershipRepository->findAll();
    $integrityCheck = count($doctrineMemberships) >= count($legacyMemberships) - $errorCount;

    if ($integrityCheck) {
        $logger->info('Vérification de l\'intégrité des données réussie');
    } else {
        $logger->error(sprintf(
            'Vérification de l\'intégrité des données échouée: %d appartenances dans Doctrine, %d attendues',
            count($doctrineMemberships),
            count($legacyMemberships) - $errorCount
        ));
    }

    // Affichage des statistiques
    $logger->info(sprintf(
        'Migration terminée avec succès: %d appartenances migrées, %d ignorées, %d erreurs',
        $migratedCount,
        $skippedCount,
        $errorCount
    ));
} catch (\Exception $e) {
    $logger->error(sprintf('Erreur lors de la migration des appartenances aux groupes de contacts: %s', $e->getMessage()));
    exit(1);
}

$logger->info('Fin de la migration des appartenances aux groupes de contacts');
exit(0);
