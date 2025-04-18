<?php

/**
 * Script de migration de l'historique des SMS depuis la base de données legacy vers Doctrine ORM
 * 
 * Ce script récupère tout l'historique des SMS de la base de données legacy et le migre
 * vers les entités Doctrine ORM. Il vérifie également l'intégrité des données migrées.
 * 
 * Usage: php scripts/migration/migrate-sms-history.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Entities\SMSHistory as DoctrineSMSHistory;
use Doctrine\ORM\EntityManagerInterface;

// Initialisation du conteneur DI
$containerDefinitions = require __DIR__ . '/../../src/config/di.php';
$containerBuilder = new \DI\ContainerBuilder();
$containerBuilder->addDefinitions($containerDefinitions);
$container = $containerBuilder->build();

// Récupération des dépendances
$entityManager = $container->get(EntityManagerInterface::class);
$legacySMSHistoryRepository = $container->get(\App\Repositories\SMSHistoryRepository::class);
$doctrineSMSHistoryRepository = $container->get(\App\Repositories\Interfaces\SMSHistoryRepositoryInterface::class);
$doctrineUserRepository = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);

// Configuration du logger
$logger = $container->get(\Psr\Log\LoggerInterface::class);
$logger->info('Début de la migration de l\'historique des SMS');

try {
    // Récupération de tout l'historique des SMS legacy
    $legacySMSHistory = $legacySMSHistoryRepository->findAll();
    $logger->info(sprintf('Nombre d\'entrées d\'historique SMS à migrer: %d', count($legacySMSHistory)));

    // Compteurs pour les statistiques
    $migratedCount = 0;
    $skippedCount = 0;
    $errorCount = 0;

    // Migration de chaque entrée d'historique SMS
    foreach ($legacySMSHistory as $legacySMS) {
        try {
            // Vérifier si l'entrée d'historique SMS existe déjà dans Doctrine
            $existingSMS = $doctrineSMSHistoryRepository->findByMessageId($legacySMS->getMessageId());

            if ($existingSMS) {
                $logger->info(sprintf('L\'entrée d\'historique SMS avec ID de message %s existe déjà, ignorée...', $legacySMS->getMessageId()));
                $skippedCount++;
                continue;
            }

            // Récupérer l'utilisateur associé
            $userId = $legacySMS->getUserId();
            $user = null;

            if ($userId) {
                $user = $doctrineUserRepository->find($userId);
                if (!$user) {
                    $logger->warning(sprintf('Utilisateur ID %d non trouvé pour l\'entrée d\'historique SMS %s', $userId, $legacySMS->getMessageId()));
                }
            }

            // Création d'une nouvelle entrée d'historique SMS Doctrine
            $doctrineSMS = new DoctrineSMSHistory();
            $doctrineSMS->setMessageId($legacySMS->getMessageId());
            $doctrineSMS->setPhoneNumber($legacySMS->getPhoneNumber());
            $doctrineSMS->setMessage($legacySMS->getMessage());
            $doctrineSMS->setStatus($legacySMS->getStatus());
            $doctrineSMS->setSenderName($legacySMS->getSenderName());
            $doctrineSMS->setSenderAddress($legacySMS->getSenderAddress());
            $doctrineSMS->setErrorMessage($legacySMS->getErrorMessage());
            $doctrineSMS->setPhoneNumberId($legacySMS->getPhoneNumberId());
            $doctrineSMS->setSegmentId($legacySMS->getSegmentId());

            if ($user) {
                $doctrineSMS->setUserId($user->getId());
            } else if ($userId) {
                $doctrineSMS->setUserId($userId);
            }

            $doctrineSMS->setCreatedAt(new \DateTime($legacySMS->getCreatedAt()));

            // Persister la nouvelle entrée d'historique SMS
            $entityManager->persist($doctrineSMS);
            $migratedCount++;

            // Flush toutes les 50 entrées pour éviter de surcharger la mémoire
            if (($migratedCount + $skippedCount) % 50 === 0) {
                $entityManager->flush();
                $logger->info(sprintf('Progression: %d entrées d\'historique SMS traitées', $migratedCount + $skippedCount));
            }
        } catch (\Exception $e) {
            $logger->error(sprintf(
                'Erreur lors de la migration de l\'entrée d\'historique SMS %s: %s',
                $legacySMS->getMessageId(),
                $e->getMessage()
            ));
            $errorCount++;
        }
    }

    // Flush final pour les entrées restantes
    $entityManager->flush();

    // Vérification de l'intégrité des données
    $logger->info('Vérification de l\'intégrité des données...');
    $doctrineSMSHistory = $doctrineSMSHistoryRepository->findAll();
    $integrityCheck = count($doctrineSMSHistory) >= count($legacySMSHistory) - $errorCount;

    if ($integrityCheck) {
        $logger->info('Vérification de l\'intégrité des données réussie');
    } else {
        $logger->error(sprintf(
            'Vérification de l\'intégrité des données échouée: %d entrées d\'historique SMS dans Doctrine, %d attendues',
            count($doctrineSMSHistory),
            count($legacySMSHistory) - $errorCount
        ));
    }

    // Affichage des statistiques
    $logger->info(sprintf(
        'Migration terminée avec succès: %d entrées d\'historique SMS migrées, %d ignorées, %d erreurs',
        $migratedCount,
        $skippedCount,
        $errorCount
    ));
} catch (\Exception $e) {
    $logger->error(sprintf('Erreur lors de la migration de l\'historique des SMS: %s', $e->getMessage()));
    exit(1);
}

$logger->info('Fin de la migration de l\'historique des SMS');
exit(0);
