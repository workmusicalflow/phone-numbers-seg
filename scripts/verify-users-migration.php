<?php

/**
 * Script de vérification de la migration des utilisateurs vers Doctrine ORM
 * 
 * Ce script vérifie uniquement la migration des utilisateurs.
 * 
 * Usage: php scripts/verify-users-migration.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\ORM\EntityManagerInterface;

// Initialisation du conteneur DI
$containerDefinitions = require __DIR__ . '/../src/config/di.php';
$containerBuilder = new \DI\ContainerBuilder();
$containerBuilder->addDefinitions($containerDefinitions);
$container = $containerBuilder->build();

// Récupération des dépendances
$entityManager = $container->get(EntityManagerInterface::class);
$logger = $container->get(\Psr\Log\LoggerInterface::class);

// Repositories legacy
$legacyUserRepository = $container->get(\App\Repositories\UserRepository::class);

// Repositories Doctrine
$doctrineUserRepository = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);

// Afficher les informations de connexion à la base de données
$connectionParams = $entityManager->getConnection()->getParams();
echo "Doctrine se connecte à : " . ($connectionParams['path'] ?? 'CHEMIN NON TROUVÉ DANS PARAMS') . "\n";

// Fonction pour vérifier les comptages
function verifyCount($entityName, $legacyCount, $doctrineCount)
{
    echo sprintf('%s: %d (legacy) vs %d (doctrine)', $entityName, $legacyCount, $doctrineCount) . "\n";

    if ($legacyCount == $doctrineCount) {
        echo sprintf('✅ Les comptages correspondent pour %s', $entityName) . "\n";
        return true;
    } else {
        echo sprintf('❌ Différence de comptage pour %s: %d (legacy) vs %d (doctrine)', $entityName, $legacyCount, $doctrineCount) . "\n";
        return false;
    }
}

// Fonction pour vérifier un échantillon de données
function verifySample($entityName, $legacyEntity, $doctrineEntity, $properties)
{
    $allMatch = true;

    foreach ($properties as $property) {
        $getterMethod = 'get' . ucfirst($property);

        if (!method_exists($legacyEntity, $getterMethod) || !method_exists($doctrineEntity, $getterMethod)) {
            echo sprintf('Méthode %s non trouvée pour %s', $getterMethod, $entityName) . "\n";
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
            echo sprintf('✅ %s.%s correspond: %s', $entityName, $property, $legacyValue) . "\n";
        } else {
            echo sprintf('❌ %s.%s diffère: %s (legacy) vs %s (doctrine)', $entityName, $property, $legacyValue, $doctrineValue) . "\n";
            $allMatch = false;
        }
    }

    return $allMatch;
}

// Début des vérifications
echo '=== Début de la vérification de la migration des utilisateurs ===' . "\n";
$startTime = microtime(true);
$successCount = 0;
$failureCount = 0;

// Vérification des comptages
echo '--- Vérification des comptages ---' . "\n";

// Utilisateurs
$legacyUserCount = count($legacyUserRepository->findAll());
$doctrineUserCount = count($doctrineUserRepository->findAll());
verifyCount('Utilisateurs', $legacyUserCount, $doctrineUserCount) ? $successCount++ : $failureCount++;

// Vérification des échantillons de données
echo '--- Vérification des échantillons de données ---' . "\n";

// Échantillon d'utilisateur
if ($legacyUserCount > 0 && $doctrineUserCount > 0) {
    $legacyUser = $legacyUserRepository->findAll()[0];
    $doctrineUser = $doctrineUserRepository->findById($legacyUser->getId());

    if ($doctrineUser) {
        $userProperties = ['username', 'email', 'smsCredit'];
        verifySample('Utilisateur', $legacyUser, $doctrineUser, $userProperties) ? $successCount++ : $failureCount++;
    } else {
        echo '❌ Utilisateur non trouvé dans Doctrine' . "\n";
        $failureCount++;
    }
}

// Fin des vérifications
$endTime = microtime(true);
$totalDuration = round($endTime - $startTime, 2);

// Affichage des statistiques
echo "=== Résumé de la vérification ===" . "\n";
echo "Vérifications réussies: $successCount" . "\n";
echo "Vérifications échouées: $failureCount" . "\n";
echo "Durée totale: $totalDuration secondes" . "\n";

if ($failureCount > 0) {
    echo "Certaines vérifications ont échoué." . "\n";
    exit(1);
} else {
    echo "Toutes les vérifications ont été effectuées avec succès." . "\n";
    exit(0);
}
