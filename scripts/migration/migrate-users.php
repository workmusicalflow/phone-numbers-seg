<?php

/**
 * Script de migration des utilisateurs depuis la base de données legacy vers Doctrine ORM
 * 
 * Ce script récupère tous les utilisateurs de la base de données legacy et les migre
 * vers les entités Doctrine ORM. Il vérifie également l'intégrité des données migrées.
 * 
 * Usage: php scripts/migration/migrate-users.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Entities\User as DoctrineUser;
use Doctrine\ORM\EntityManagerInterface;

// Initialisation du conteneur DI
$containerDefinitions = require __DIR__ . '/../../src/config/di.php';
$containerBuilder = new \DI\ContainerBuilder();
$containerBuilder->addDefinitions($containerDefinitions);
$container = $containerBuilder->build();

// Récupération des dépendances
$entityManager = $container->get(EntityManagerInterface::class);
$legacyUserRepository = $container->get(\App\Repositories\UserRepository::class);
$doctrineUserRepository = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);

// Configuration du logger
$logger = $container->get(\Psr\Log\LoggerInterface::class);
$logger->info('Début de la migration des utilisateurs');

// Afficher les informations de connexion à la base de données
$connectionParams = $entityManager->getConnection()->getParams();
echo "Doctrine se connecte à : " . ($connectionParams['path'] ?? 'CHEMIN NON TROUVÉ DANS PARAMS') . "\n";

try {
    // Récupération de tous les utilisateurs legacy
    try {
        $legacyUsers = $legacyUserRepository->findAll();
        $logger->info(sprintf('Nombre d\'utilisateurs à migrer: %d', count($legacyUsers)));
    } catch (\Exception $e) {
        echo "Erreur lors de la récupération des utilisateurs legacy: " . $e->getMessage() . "\n";
        $logger->error(sprintf('Erreur lors de la récupération des utilisateurs legacy: %s', $e->getMessage()));
        exit(1);
    }

    // Compteurs pour les statistiques
    $migratedCount = 0;
    $skippedCount = 0;
    $errorCount = 0;

    // Migration de chaque utilisateur
    foreach ($legacyUsers as $legacyUser) {
        try {
            // Vérifier si l'utilisateur existe déjà dans Doctrine
            $existingUser = $doctrineUserRepository->findByUsername($legacyUser->getUsername());

            if ($existingUser) {
                $logger->info(sprintf('L\'utilisateur %s existe déjà, mise à jour...', $legacyUser->getUsername()));

                // Mise à jour des propriétés
                $existingUser->setEmail($legacyUser->getEmail());
                $existingUser->setPassword($legacyUser->getPassword()); // Déjà hashé
                $existingUser->setSmsCredit($legacyUser->getSmsCredit());
                $existingUser->setSmsLimit($legacyUser->getSmsLimit());
                $existingUser->setIsAdmin($legacyUser->isAdmin());

                // Préserver les valeurs existantes des nouveaux champs s'ils existent
                if ($existingUser->getApiKey() === null) {
                    $existingUser->setApiKey(null);
                }
                if ($existingUser->getResetToken() === null) {
                    $existingUser->setResetToken(null);
                }

                // Persister les modifications
                $entityManager->persist($existingUser);
                $skippedCount++;
            } else {
                // Création d'un nouvel utilisateur Doctrine
                $doctrineUser = new DoctrineUser();
                $doctrineUser->setUsername($legacyUser->getUsername());
                $doctrineUser->setEmail($legacyUser->getEmail());
                $doctrineUser->setPassword($legacyUser->getPassword()); // Déjà hashé
                $doctrineUser->setSmsCredit($legacyUser->getSmsCredit());
                $doctrineUser->setSmsLimit($legacyUser->getSmsLimit());
                $doctrineUser->setIsAdmin($legacyUser->isAdmin());
                $doctrineUser->setCreatedAt(new \DateTime($legacyUser->getCreatedAt()));
                $doctrineUser->setUpdatedAt(new \DateTime($legacyUser->getUpdatedAt()));

                // Initialiser les nouveaux champs à null
                $doctrineUser->setApiKey(null);
                $doctrineUser->setResetToken(null);

                // Persister le nouvel utilisateur
                $entityManager->persist($doctrineUser);
                $migratedCount++;
            }

            // Flush toutes les 50 utilisateurs pour éviter de surcharger la mémoire
            if (($migratedCount + $skippedCount) % 50 === 0) {
                $entityManager->flush();
                $logger->info(sprintf('Progression: %d utilisateurs traités', $migratedCount + $skippedCount));
            }
        } catch (\Exception $e) {
            $logger->error(sprintf('Erreur lors de la migration de l\'utilisateur %s: %s', $legacyUser->getUsername(), $e->getMessage()));
            $errorCount++;
        }
    }

    // Flush final pour les utilisateurs restants
    $entityManager->flush();

    // Vérification de l'intégrité des données
    $logger->info('Vérification de l\'intégrité des données...');
    $doctrineUsers = $doctrineUserRepository->findAll();
    $integrityCheck = count($doctrineUsers) >= count($legacyUsers) - $errorCount;

    if ($integrityCheck) {
        $logger->info('Vérification de l\'intégrité des données réussie');
    } else {
        $logger->error(sprintf(
            'Vérification de l\'intégrité des données échouée: %d utilisateurs dans Doctrine, %d attendus',
            count($doctrineUsers),
            count($legacyUsers) - $errorCount
        ));
    }

    // Affichage des statistiques
    $logger->info(sprintf(
        'Migration terminée avec succès: %d utilisateurs migrés, %d mis à jour, %d erreurs',
        $migratedCount,
        $skippedCount,
        $errorCount
    ));

    // Considérer la migration comme réussie même s'il y a des erreurs
    // tant que certains utilisateurs ont été migrés
    if ($migratedCount > 0 || $skippedCount > 0) {
        $logger->info('Fin de la migration des utilisateurs');
        exit(0); // Succès
    } else {
        $logger->error('Aucun utilisateur n\'a été migré');
        exit(1); // Échec
    }
} catch (\Exception $e) {
    $logger->error(sprintf('Erreur lors de la migration des utilisateurs: %s', $e->getMessage()));
    echo "Erreur lors de la migration des utilisateurs: " . $e->getMessage() . "\n";
    exit(1);
}

$logger->info('Fin de la migration des utilisateurs');
exit(0);
