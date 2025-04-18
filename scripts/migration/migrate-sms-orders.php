<?php

/**
 * Script de migration des commandes SMS depuis la base de données legacy vers Doctrine ORM
 * 
 * Ce script récupère toutes les commandes SMS de la base de données legacy et les migre
 * vers les entités Doctrine ORM. Il vérifie également l'intégrité des données migrées.
 * 
 * Usage: php scripts/migration/migrate-sms-orders.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Entities\SMSOrder as DoctrineSMSOrder;
use Doctrine\ORM\EntityManagerInterface;

// Initialisation du conteneur DI
$containerDefinitions = require __DIR__ . '/../../src/config/di.php';
$containerBuilder = new \DI\ContainerBuilder();
$containerBuilder->addDefinitions($containerDefinitions);
$container = $containerBuilder->build();

// Récupération des dépendances
$entityManager = $container->get(EntityManagerInterface::class);
$legacySMSOrderRepository = $container->get(\App\Repositories\SMSOrderRepository::class);
$doctrineSMSOrderRepository = $container->get(\App\Repositories\Interfaces\SMSOrderRepositoryInterface::class);
$doctrineUserRepository = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);

// Configuration du logger
$logger = $container->get(\Psr\Log\LoggerInterface::class);
$logger->info('Début de la migration des commandes SMS');

try {
    // Récupération de toutes les commandes SMS legacy
    $legacySMSOrders = $legacySMSOrderRepository->findAll();
    $logger->info(sprintf('Nombre de commandes SMS à migrer: %d', count($legacySMSOrders)));

    // Compteurs pour les statistiques
    $migratedCount = 0;
    $skippedCount = 0;
    $errorCount = 0;

    // Migration de chaque commande SMS
    foreach ($legacySMSOrders as $legacySMSOrder) {
        try {
            // Vérifier si la commande SMS existe déjà dans Doctrine
            $existingSMSOrder = $doctrineSMSOrderRepository->findById($legacySMSOrder->getId());

            if ($existingSMSOrder) {
                $logger->info(sprintf('La commande SMS avec l\'ID %d existe déjà, ignorée...', $legacySMSOrder->getId()));
                $skippedCount++;
                continue;
            }

            // Récupérer l'utilisateur associé
            $userId = $legacySMSOrder->getUserId();
            $user = null;

            if ($userId) {
                $user = $doctrineUserRepository->findById($userId);
                if (!$user) {
                    $logger->warning(sprintf('Utilisateur ID %d non trouvé pour la commande SMS ID %d', $userId, $legacySMSOrder->getId()));
                }
            }

            // Création d'une nouvelle commande SMS Doctrine
            $doctrineSMSOrder = new DoctrineSMSOrder();
            $doctrineSMSOrder->setQuantity($legacySMSOrder->getQuantity());
            $doctrineSMSOrder->setStatus($legacySMSOrder->getStatus());

            if ($user) {
                $doctrineSMSOrder->setUserId($user->getId());
            } else if ($userId) {
                $doctrineSMSOrder->setUserId($userId);
            }

            $doctrineSMSOrder->setCreatedAt(new \DateTime($legacySMSOrder->getCreatedAt()));
            $doctrineSMSOrder->setUpdatedAt(new \DateTime($legacySMSOrder->getUpdatedAt()));

            // Persister la nouvelle commande SMS
            $entityManager->persist($doctrineSMSOrder);
            $migratedCount++;

            // Flush toutes les 50 commandes pour éviter de surcharger la mémoire
            if (($migratedCount + $skippedCount) % 50 === 0) {
                $entityManager->flush();
                $logger->info(sprintf('Progression: %d commandes SMS traitées', $migratedCount + $skippedCount));
            }
        } catch (\Exception $e) {
            $logger->error(sprintf(
                'Erreur lors de la migration de la commande SMS ID %d: %s',
                $legacySMSOrder->getId(),
                $e->getMessage()
            ));
            $errorCount++;
        }
    }

    // Flush final pour les commandes restantes
    $entityManager->flush();

    // Vérification de l'intégrité des données
    $logger->info('Vérification de l\'intégrité des données...');
    $doctrineSMSOrders = $doctrineSMSOrderRepository->findAll();
    $integrityCheck = count($doctrineSMSOrders) >= count($legacySMSOrders) - $errorCount;

    if ($integrityCheck) {
        $logger->info('Vérification de l\'intégrité des données réussie');
    } else {
        $logger->error(sprintf(
            'Vérification de l\'intégrité des données échouée: %d commandes SMS dans Doctrine, %d attendues',
            count($doctrineSMSOrders),
            count($legacySMSOrders) - $errorCount
        ));
    }

    // Affichage des statistiques
    $logger->info(sprintf(
        'Migration terminée avec succès: %d commandes SMS migrées, %d ignorées, %d erreurs',
        $migratedCount,
        $skippedCount,
        $errorCount
    ));
} catch (\Exception $e) {
    $logger->error(sprintf('Erreur lors de la migration des commandes SMS: %s', $e->getMessage()));
    exit(1);
}

$logger->info('Fin de la migration des commandes SMS');
exit(0);
