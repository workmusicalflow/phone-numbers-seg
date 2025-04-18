<?php

/**
 * Script de migration des contacts depuis la base de données legacy vers Doctrine ORM
 * 
 * Ce script récupère tous les contacts de la base de données legacy et les migre
 * vers les entités Doctrine ORM. Il vérifie également l'intégrité des données migrées.
 * 
 * Usage: php scripts/migration/migrate-contacts.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Entities\Contact as DoctrineContact;
use Doctrine\ORM\EntityManagerInterface;

// Initialisation du conteneur DI
$containerDefinitions = require __DIR__ . '/../../src/config/di.php';
$containerBuilder = new \DI\ContainerBuilder();
$containerBuilder->addDefinitions($containerDefinitions);
$container = $containerBuilder->build();

// Récupération des dépendances
$entityManager = $container->get(EntityManagerInterface::class);
$legacyContactRepository = $container->get(\App\Repositories\ContactRepository::class);
$doctrineContactRepository = $container->get(\App\Repositories\Interfaces\ContactRepositoryInterface::class);
$doctrineUserRepository = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);

// Configuration du logger
$logger = $container->get(\Psr\Log\LoggerInterface::class);
$logger->info('Début de la migration des contacts');

try {
    // Récupération de tous les contacts legacy
    $legacyContacts = $legacyContactRepository->findAll();
    $logger->info(sprintf('Nombre de contacts à migrer: %d', count($legacyContacts)));

    // Compteurs pour les statistiques
    $migratedCount = 0;
    $skippedCount = 0;
    $errorCount = 0;

    // Migration de chaque contact
    foreach ($legacyContacts as $legacyContact) {
        try {
            // Vérifier si le contact existe déjà dans Doctrine
            $existingContact = $doctrineContactRepository->findByPhoneNumber($legacyContact->getPhoneNumber());

            if ($existingContact) {
                $logger->info(sprintf('Le contact %s existe déjà, mise à jour...', $legacyContact->getPhoneNumber()));

                // Mise à jour des propriétés
                $existingContact->setName($legacyContact->getName());
                $existingContact->setEmail($legacyContact->getEmail());
                $existingContact->setNotes($legacyContact->getNotes());

                // Persister les modifications
                $entityManager->persist($existingContact);
                $skippedCount++;
            } else {
                // Récupérer l'utilisateur associé
                $userId = $legacyContact->getUserId();
                $user = null;

                if ($userId) {
                    $user = $doctrineUserRepository->find($userId);
                    if (!$user) {
                        $logger->warning(sprintf('Utilisateur ID %d non trouvé pour le contact %s', $userId, $legacyContact->getPhoneNumber()));
                    }
                }

                // Création d'un nouveau contact Doctrine
                $doctrineContact = new DoctrineContact();
                $doctrineContact->setName($legacyContact->getName());
                $doctrineContact->setPhoneNumber($legacyContact->getPhoneNumber());
                $doctrineContact->setEmail($legacyContact->getEmail());
                $doctrineContact->setNotes($legacyContact->getNotes());

                if ($user) {
                    $doctrineContact->setUserId($user->getId());
                } else if ($userId) {
                    $doctrineContact->setUserId($userId);
                }

                $doctrineContact->setCreatedAt(new \DateTime($legacyContact->getCreatedAt()));
                $doctrineContact->setUpdatedAt(new \DateTime($legacyContact->getUpdatedAt()));

                // Persister le nouveau contact
                $entityManager->persist($doctrineContact);
                $migratedCount++;
            }

            // Flush tous les 50 contacts pour éviter de surcharger la mémoire
            if (($migratedCount + $skippedCount) % 50 === 0) {
                $entityManager->flush();
                $logger->info(sprintf('Progression: %d contacts traités', $migratedCount + $skippedCount));
            }
        } catch (\Exception $e) {
            $logger->error(sprintf('Erreur lors de la migration du contact %s: %s', $legacyContact->getPhoneNumber(), $e->getMessage()));
            $errorCount++;
        }
    }

    // Flush final pour les contacts restants
    $entityManager->flush();

    // Vérification de l'intégrité des données
    $logger->info('Vérification de l\'intégrité des données...');
    $doctrineContacts = $doctrineContactRepository->findAll();
    $integrityCheck = count($doctrineContacts) >= count($legacyContacts) - $errorCount;

    if ($integrityCheck) {
        $logger->info('Vérification de l\'intégrité des données réussie');
    } else {
        $logger->error(sprintf(
            'Vérification de l\'intégrité des données échouée: %d contacts dans Doctrine, %d attendus',
            count($doctrineContacts),
            count($legacyContacts) - $errorCount
        ));
    }

    // Affichage des statistiques
    $logger->info(sprintf(
        'Migration terminée avec succès: %d contacts migrés, %d mis à jour, %d erreurs',
        $migratedCount,
        $skippedCount,
        $errorCount
    ));
} catch (\Exception $e) {
    $logger->error(sprintf('Erreur lors de la migration des contacts: %s', $e->getMessage()));
    exit(1);
}

$logger->info('Fin de la migration des contacts');
exit(0);
