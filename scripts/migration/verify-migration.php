<?php

/**
 * Script de vérification de la migration des données vers Doctrine ORM
 * 
 * Ce script effectue des vérifications approfondies pour s'assurer que les données
 * ont été correctement migrées de la base de données legacy vers Doctrine ORM.
 * Il vérifie les comptages, les relations et l'intégrité des données.
 * 
 * Usage: php scripts/migration/verify-migration.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Doctrine\ORM\EntityManagerInterface;

// Initialisation du conteneur DI
$containerDefinitions = require __DIR__ . '/../../src/config/di.php';
$containerBuilder = new \DI\ContainerBuilder();
$containerBuilder->addDefinitions($containerDefinitions);
$container = $containerBuilder->build();

// Récupération des dépendances
$entityManager = $container->get(EntityManagerInterface::class);
$logger = $container->get(\Psr\Log\LoggerInterface::class);

// Repositories legacy
$legacyUserRepository = $container->get(\App\Repositories\UserRepository::class);
$legacyContactRepository = $container->get(\App\Repositories\ContactRepository::class);
$legacyContactGroupRepository = $container->get(\App\Repositories\ContactGroupRepository::class);
$legacyContactGroupMembershipRepository = $container->get(\App\Repositories\ContactGroupMembershipRepository::class);
$legacySMSHistoryRepository = $container->get(\App\Repositories\SMSHistoryRepository::class);
$legacySMSOrderRepository = $container->get(\App\Repositories\SMSOrderRepository::class);
$legacySegmentRepository = $container->get(\App\Repositories\SegmentRepository::class);
$legacyCustomSegmentRepository = $container->get(\App\Repositories\CustomSegmentRepository::class);
$legacyPhoneNumberRepository = $container->get(\App\Repositories\PhoneNumberRepository::class);

// Repositories Doctrine
$doctrineUserRepository = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);
$doctrineContactRepository = $container->get(\App\Repositories\Interfaces\ContactRepositoryInterface::class);
$doctrineContactGroupRepository = $container->get(\App\Repositories\Interfaces\ContactGroupRepositoryInterface::class);
$doctrineContactGroupMembershipRepository = $container->get(\App\Repositories\Interfaces\ContactGroupMembershipRepositoryInterface::class);
$doctrineSMSHistoryRepository = $container->get(\App\Repositories\Interfaces\SMSHistoryRepositoryInterface::class);
$doctrineSMSOrderRepository = $container->get(\App\Repositories\Interfaces\SMSOrderRepositoryInterface::class);
$doctrineSegmentRepository = $container->get(\App\Repositories\Interfaces\SegmentRepositoryInterface::class);
$doctrineCustomSegmentRepository = $container->get(\App\Repositories\Interfaces\CustomSegmentRepositoryInterface::class);
$doctrinePhoneNumberRepository = $container->get(\App\Repositories\Interfaces\PhoneNumberRepositoryInterface::class);
$doctrinePhoneNumberSegmentRepository = $container->get(\App\Repositories\Interfaces\PhoneNumberSegmentRepositoryInterface::class);

// Fonction pour vérifier les comptages
function verifyCount($entityName, $legacyCount, $doctrineCount, $logger)
{
    $logger->info(sprintf('%s: %d (legacy) vs %d (doctrine)', $entityName, $legacyCount, $doctrineCount));

    if ($legacyCount == $doctrineCount) {
        $logger->info(sprintf('✅ Les comptages correspondent pour %s', $entityName));
        return true;
    } else {
        $logger->error(sprintf('❌ Différence de comptage pour %s: %d (legacy) vs %d (doctrine)', $entityName, $legacyCount, $doctrineCount));
        return false;
    }
}

// Fonction pour vérifier les relations
function verifyRelation($entityName, $relationName, $legacyCount, $doctrineCount, $logger)
{
    $logger->info(sprintf('%s - %s: %d (legacy) vs %d (doctrine)', $entityName, $relationName, $legacyCount, $doctrineCount));

    if ($legacyCount == $doctrineCount) {
        $logger->info(sprintf('✅ Les relations correspondent pour %s - %s', $entityName, $relationName));
        return true;
    } else {
        $logger->error(sprintf('❌ Différence de relation pour %s - %s: %d (legacy) vs %d (doctrine)', $entityName, $relationName, $legacyCount, $doctrineCount));
        return false;
    }
}

// Fonction pour vérifier un échantillon de données
function verifySample($entityName, $legacyEntity, $doctrineEntity, $properties, $logger)
{
    $allMatch = true;

    foreach ($properties as $property) {
        $getterMethod = 'get' . ucfirst($property);

        if (!method_exists($legacyEntity, $getterMethod) || !method_exists($doctrineEntity, $getterMethod)) {
            $logger->warning(sprintf('Méthode %s non trouvée pour %s', $getterMethod, $entityName));
            continue;
        }

        $legacyValue = $legacyEntity->$getterMethod();
        $doctrineValue = $doctrineEntity->$getterMethod();

        // Conversion des DateTime en chaînes pour la comparaison
        if ($legacyValue instanceof \DateTime) {
            $legacyValue = $legacyValue->format('Y-m-d H:i:s');
        }
        if ($doctrineValue instanceof \DateTime) {
            $doctrineValue = $doctrineValue->format('Y-m-d H:i:s');
        }

        if ($legacyValue == $doctrineValue) {
            $logger->info(sprintf('✅ %s.%s correspond: %s', $entityName, $property, $legacyValue));
        } else {
            $logger->error(sprintf('❌ %s.%s diffère: %s (legacy) vs %s (doctrine)', $entityName, $property, $legacyValue, $doctrineValue));
            $allMatch = false;
        }
    }

    return $allMatch;
}

// Début des vérifications
$logger->info('=== Début de la vérification de la migration ===');
$startTime = microtime(true);
$successCount = 0;
$failureCount = 0;

// 1. Vérification des comptages
$logger->info('--- Vérification des comptages ---');

// Utilisateurs
$legacyUserCount = count($legacyUserRepository->findAll());
$doctrineUserCount = count($doctrineUserRepository->findAll());
verifyCount('Utilisateurs', $legacyUserCount, $doctrineUserCount, $logger) ? $successCount++ : $failureCount++;

// Contacts
$legacyContactCount = count($legacyContactRepository->findAll());
$doctrineContactCount = count($doctrineContactRepository->findAll());
verifyCount('Contacts', $legacyContactCount, $doctrineContactCount, $logger) ? $successCount++ : $failureCount++;

// Groupes de contacts
$legacyContactGroupCount = count($legacyContactGroupRepository->findAll());
$doctrineContactGroupCount = count($doctrineContactGroupRepository->findAll());
verifyCount('Groupes de contacts', $legacyContactGroupCount, $doctrineContactGroupCount, $logger) ? $successCount++ : $failureCount++;

// Appartenances aux groupes
$legacyMembershipCount = count($legacyContactGroupMembershipRepository->findAll());
$doctrineMembershipCount = count($doctrineContactGroupMembershipRepository->findAll());
verifyCount('Appartenances aux groupes', $legacyMembershipCount, $doctrineMembershipCount, $logger) ? $successCount++ : $failureCount++;

// Historique SMS
$legacySMSHistoryCount = count($legacySMSHistoryRepository->findAll());
$doctrineSMSHistoryCount = count($doctrineSMSHistoryRepository->findAll());
verifyCount('Historique SMS', $legacySMSHistoryCount, $doctrineSMSHistoryCount, $logger) ? $successCount++ : $failureCount++;

// Commandes SMS
$legacySMSOrderCount = count($legacySMSOrderRepository->findAll());
$doctrineSMSOrderCount = count($doctrineSMSOrderRepository->findAll());
verifyCount('Commandes SMS', $legacySMSOrderCount, $doctrineSMSOrderCount, $logger) ? $successCount++ : $failureCount++;

// Segments
$legacySegmentCount = count($legacySegmentRepository->findAll());
$doctrineSegmentCount = count($doctrineSegmentRepository->findAll());
verifyCount('Segments', $legacySegmentCount, $doctrineSegmentCount, $logger) ? $successCount++ : $failureCount++;

// Segments personnalisés
$legacyCustomSegmentCount = count($legacyCustomSegmentRepository->findAll());
$doctrineCustomSegmentCount = count($doctrineCustomSegmentRepository->findAll());
verifyCount('Segments personnalisés', $legacyCustomSegmentCount, $doctrineCustomSegmentCount, $logger) ? $successCount++ : $failureCount++;

// Numéros de téléphone
$legacyPhoneNumberCount = count($legacyPhoneNumberRepository->findAll());
$doctrinePhoneNumberCount = count($doctrinePhoneNumberRepository->findAll());
verifyCount('Numéros de téléphone', $legacyPhoneNumberCount, $doctrinePhoneNumberCount, $logger) ? $successCount++ : $failureCount++;

// 2. Vérification des échantillons de données
$logger->info('--- Vérification des échantillons de données ---');

// Échantillon d'utilisateur
if ($legacyUserCount > 0 && $doctrineUserCount > 0) {
    $legacyUser = $legacyUserRepository->findAll()[0];
    $doctrineUser = $doctrineUserRepository->findById($legacyUser->getId());

    if ($doctrineUser) {
        $userProperties = ['username', 'email', 'role', 'smsCredits'];
        verifySample('Utilisateur', $legacyUser, $doctrineUser, $userProperties, $logger) ? $successCount++ : $failureCount++;
    } else {
        $logger->error('❌ Utilisateur non trouvé dans Doctrine');
        $failureCount++;
    }
}

// Échantillon de contact
if ($legacyContactCount > 0 && $doctrineContactCount > 0) {
    $legacyContact = $legacyContactRepository->findAll()[0];
    $doctrineContact = $doctrineContactRepository->findById($legacyContact->getId());

    if ($doctrineContact) {
        $contactProperties = ['firstName', 'lastName', 'email', 'phoneNumber'];
        verifySample('Contact', $legacyContact, $doctrineContact, $contactProperties, $logger) ? $successCount++ : $failureCount++;
    } else {
        $logger->error('❌ Contact non trouvé dans Doctrine');
        $failureCount++;
    }
}

// Échantillon de groupe de contacts
if ($legacyContactGroupCount > 0 && $doctrineContactGroupCount > 0) {
    $legacyGroup = $legacyContactGroupRepository->findAll()[0];
    $doctrineGroup = $doctrineContactGroupRepository->findById($legacyGroup->getId());

    if ($doctrineGroup) {
        $groupProperties = ['name', 'description', 'userId'];
        verifySample('Groupe de contacts', $legacyGroup, $doctrineGroup, $groupProperties, $logger) ? $successCount++ : $failureCount++;
    } else {
        $logger->error('❌ Groupe de contacts non trouvé dans Doctrine');
        $failureCount++;
    }
}

// Échantillon d'historique SMS
if ($legacySMSHistoryCount > 0 && $doctrineSMSHistoryCount > 0) {
    $legacySMS = $legacySMSHistoryRepository->findAll()[0];
    $doctrineSMS = $doctrineSMSHistoryRepository->findById($legacySMS->getId());

    if ($doctrineSMS) {
        $smsProperties = ['messageId', 'phoneNumber', 'message', 'status'];
        verifySample('Historique SMS', $legacySMS, $doctrineSMS, $smsProperties, $logger) ? $successCount++ : $failureCount++;
    } else {
        $logger->error('❌ Historique SMS non trouvé dans Doctrine');
        $failureCount++;
    }
}

// Échantillon de numéro de téléphone
if ($legacyPhoneNumberCount > 0 && $doctrinePhoneNumberCount > 0) {
    $legacyPhone = $legacyPhoneNumberRepository->findAll()[0];
    $doctrinePhone = $doctrinePhoneNumberRepository->findByNumber($legacyPhone->getNumber());

    if ($doctrinePhone) {
        $phoneProperties = ['number', 'firstName', 'name', 'company'];
        verifySample('Numéro de téléphone', $legacyPhone, $doctrinePhone, $phoneProperties, $logger) ? $successCount++ : $failureCount++;
    } else {
        $logger->error('❌ Numéro de téléphone non trouvé dans Doctrine');
        $failureCount++;
    }
}

// 3. Vérification des relations
$logger->info('--- Vérification des relations ---');

// Relation entre groupes et contacts
if ($legacyContactGroupCount > 0 && $doctrineContactGroupCount > 0) {
    $legacyGroup = $legacyContactGroupRepository->findAll()[0];
    $doctrineGroup = $doctrineContactGroupRepository->findById($legacyGroup->getId());

    if ($doctrineGroup) {
        $legacyMemberships = $legacyContactGroupMembershipRepository->findByGroupId($legacyGroup->getId());
        $doctrineMemberships = $doctrineContactGroupMembershipRepository->findBy(['contactGroupId' => $doctrineGroup->getId()]);

        verifyRelation('Groupe de contacts', 'Membres', count($legacyMemberships), count($doctrineMemberships), $logger) ? $successCount++ : $failureCount++;
    }
}

// Relation entre segments personnalisés et numéros de téléphone
if ($legacyCustomSegmentCount > 0 && $doctrineCustomSegmentCount > 0) {
    $legacySegment = $legacyCustomSegmentRepository->findAll()[0];
    $doctrineSegment = $doctrineCustomSegmentRepository->findById($legacySegment->getId());

    if ($doctrineSegment) {
        $legacyPhoneNumbers = $legacySegment->getPhoneNumbers();
        $doctrineAssociations = $doctrinePhoneNumberSegmentRepository->findBy(['customSegmentId' => $doctrineSegment->getId()]);

        verifyRelation('Segment personnalisé', 'Numéros de téléphone', count($legacyPhoneNumbers), count($doctrineAssociations), $logger) ? $successCount++ : $failureCount++;
    }
}

// Fin des vérifications
$endTime = microtime(true);
$totalDuration = round($endTime - $startTime, 2);

// Affichage des statistiques
$logger->info("=== Résumé de la vérification ===");
$logger->info("Vérifications réussies: $successCount");
$logger->info("Vérifications échouées: $failureCount");
$logger->info("Durée totale: $totalDuration secondes");

if ($failureCount > 0) {
    $logger->warning("Certaines vérifications ont échoué. Veuillez vérifier les logs pour plus de détails.");
    exit(1);
} else {
    $logger->info("Toutes les vérifications ont été effectuées avec succès.");
    exit(0);
}
