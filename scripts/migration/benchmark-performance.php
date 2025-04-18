<?php

/**
 * Script de benchmark pour comparer les performances entre l'implémentation legacy et Doctrine ORM
 * 
 * Ce script exécute une série de tests de performance pour comparer les implémentations
 * legacy et Doctrine ORM. Il mesure le temps d'exécution et l'utilisation de la mémoire
 * pour diverses opérations courantes.
 * 
 * Usage: php scripts/migration/benchmark-performance.php
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
$legacySMSHistoryRepository = $container->get(\App\Repositories\SMSHistoryRepository::class);
$legacyPhoneNumberRepository = $container->get(\App\Repositories\PhoneNumberRepository::class);

// Repositories Doctrine
$doctrineUserRepository = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);
$doctrineContactRepository = $container->get(\App\Repositories\Interfaces\ContactRepositoryInterface::class);
$doctrineContactGroupRepository = $container->get(\App\Repositories\Interfaces\ContactGroupRepositoryInterface::class);
$doctrineSMSHistoryRepository = $container->get(\App\Repositories\Interfaces\SMSHistoryRepositoryInterface::class);
$doctrinePhoneNumberRepository = $container->get(\App\Repositories\Interfaces\PhoneNumberRepositoryInterface::class);

// Fonction pour mesurer le temps d'exécution et l'utilisation de la mémoire
function benchmark($name, $callback, $logger)
{
    $startTime = microtime(true);
    $startMemory = memory_get_usage();

    $result = $callback();

    $endTime = microtime(true);
    $endMemory = memory_get_usage();

    $executionTime = round(($endTime - $startTime) * 1000, 2); // en millisecondes
    $memoryUsage = round(($endMemory - $startMemory) / 1024, 2); // en Ko

    $logger->info(sprintf(
        '%s - Temps: %s ms, Mémoire: %s Ko, Résultat: %s',
        $name,
        $executionTime,
        $memoryUsage,
        is_scalar($result) ? $result : (is_array($result) ? count($result) : gettype($result))
    ));

    return [
        'name' => $name,
        'time' => $executionTime,
        'memory' => $memoryUsage,
        'result' => $result
    ];
}

// Fonction pour comparer les résultats
function compareResults($legacyResult, $doctrineResult, $logger)
{
    $timeDiff = $doctrineResult['time'] - $legacyResult['time'];
    $memoryDiff = $doctrineResult['memory'] - $legacyResult['memory'];

    $timePercentage = $legacyResult['time'] > 0 ? round(($timeDiff / $legacyResult['time']) * 100, 2) : 0;
    $memoryPercentage = $legacyResult['memory'] > 0 ? round(($memoryDiff / $legacyResult['memory']) * 100, 2) : 0;

    $logger->info(sprintf(
        'Comparaison - Temps: %s%% (%s ms), Mémoire: %s%% (%s Ko)',
        $timePercentage > 0 ? '+' . $timePercentage : $timePercentage,
        $timeDiff > 0 ? '+' . $timeDiff : $timeDiff,
        $memoryPercentage > 0 ? '+' . $memoryPercentage : $memoryPercentage,
        $memoryDiff > 0 ? '+' . $memoryDiff : $memoryDiff
    ));

    return [
        'timeDiff' => $timeDiff,
        'memoryDiff' => $memoryDiff,
        'timePercentage' => $timePercentage,
        'memoryPercentage' => $memoryPercentage
    ];
}

// Début des benchmarks
$logger->info('=== Début des benchmarks de performance ===');
$startTime = microtime(true);

$results = [];

// 1. Récupération de tous les utilisateurs
$logger->info('--- Test: Récupération de tous les utilisateurs ---');

$legacyResult = benchmark('Legacy - findAll Users', function () use ($legacyUserRepository) {
    return $legacyUserRepository->findAll();
}, $logger);

$doctrineResult = benchmark('Doctrine - findAll Users', function () use ($doctrineUserRepository) {
    return $doctrineUserRepository->findAll();
}, $logger);

$results[] = [
    'test' => 'Récupération de tous les utilisateurs',
    'legacy' => $legacyResult,
    'doctrine' => $doctrineResult,
    'comparison' => compareResults($legacyResult, $doctrineResult, $logger)
];

// 2. Récupération d'un utilisateur par ID
$logger->info('--- Test: Récupération d\'un utilisateur par ID ---');

// Récupérer un ID d'utilisateur existant
$userIds = array_map(function ($user) {
    return $user->getId();
}, $legacyUserRepository->findAll());

if (count($userIds) > 0) {
    $userId = $userIds[0];

    $legacyResult = benchmark('Legacy - findById User', function () use ($legacyUserRepository, $userId) {
        return $legacyUserRepository->find($userId);
    }, $logger);

    $doctrineResult = benchmark('Doctrine - findById User', function () use ($doctrineUserRepository, $userId) {
        return $doctrineUserRepository->find($userId);
    }, $logger);

    $results[] = [
        'test' => 'Récupération d\'un utilisateur par ID',
        'legacy' => $legacyResult,
        'doctrine' => $doctrineResult,
        'comparison' => compareResults($legacyResult, $doctrineResult, $logger)
    ];
}

// 3. Récupération de tous les contacts
$logger->info('--- Test: Récupération de tous les contacts ---');

$legacyResult = benchmark('Legacy - findAll Contacts', function () use ($legacyContactRepository) {
    return $legacyContactRepository->findAll();
}, $logger);

$doctrineResult = benchmark('Doctrine - findAll Contacts', function () use ($doctrineContactRepository) {
    return $doctrineContactRepository->findAll();
}, $logger);

$results[] = [
    'test' => 'Récupération de tous les contacts',
    'legacy' => $legacyResult,
    'doctrine' => $doctrineResult,
    'comparison' => compareResults($legacyResult, $doctrineResult, $logger)
];

// 4. Récupération de tous les groupes de contacts
$logger->info('--- Test: Récupération de tous les groupes de contacts ---');

$legacyResult = benchmark('Legacy - findAll ContactGroups', function () use ($legacyContactGroupRepository) {
    return $legacyContactGroupRepository->findAll();
}, $logger);

$doctrineResult = benchmark('Doctrine - findAll ContactGroups', function () use ($doctrineContactGroupRepository) {
    return $doctrineContactGroupRepository->findAll();
}, $logger);

$results[] = [
    'test' => 'Récupération de tous les groupes de contacts',
    'legacy' => $legacyResult,
    'doctrine' => $doctrineResult,
    'comparison' => compareResults($legacyResult, $doctrineResult, $logger)
];

// 5. Récupération de l'historique SMS
$logger->info('--- Test: Récupération de l\'historique SMS ---');

$legacyResult = benchmark('Legacy - findAll SMSHistory', function () use ($legacySMSHistoryRepository) {
    return $legacySMSHistoryRepository->findAll();
}, $logger);

$doctrineResult = benchmark('Doctrine - findAll SMSHistory', function () use ($doctrineSMSHistoryRepository) {
    return $doctrineSMSHistoryRepository->findAll();
}, $logger);

$results[] = [
    'test' => 'Récupération de l\'historique SMS',
    'legacy' => $legacyResult,
    'doctrine' => $doctrineResult,
    'comparison' => compareResults($legacyResult, $doctrineResult, $logger)
];

// 6. Récupération des numéros de téléphone
$logger->info('--- Test: Récupération des numéros de téléphone ---');

$legacyResult = benchmark('Legacy - findAll PhoneNumbers', function () use ($legacyPhoneNumberRepository) {
    return $legacyPhoneNumberRepository->findAll();
}, $logger);

$doctrineResult = benchmark('Doctrine - findAll PhoneNumbers', function () use ($doctrinePhoneNumberRepository) {
    return $doctrinePhoneNumberRepository->findAll();
}, $logger);

$results[] = [
    'test' => 'Récupération des numéros de téléphone',
    'legacy' => $legacyResult,
    'doctrine' => $doctrineResult,
    'comparison' => compareResults($legacyResult, $doctrineResult, $logger)
];

// 7. Recherche de numéros de téléphone par préfixe
$logger->info('--- Test: Recherche de numéros de téléphone par préfixe ---');

// Récupérer un préfixe de numéro de téléphone existant
$phoneNumbers = $legacyPhoneNumberRepository->findAll();
$prefix = '';

if (count($phoneNumbers) > 0) {
    $number = $phoneNumbers[0]->getNumber();
    $prefix = substr($number, 0, 3);

    $legacyResult = benchmark('Legacy - findByPrefix PhoneNumbers', function () use ($legacyPhoneNumberRepository, $prefix) {
        return $legacyPhoneNumberRepository->findByPrefix($prefix);
    }, $logger);

    $doctrineResult = benchmark('Doctrine - findByPrefix PhoneNumbers', function () use ($doctrinePhoneNumberRepository, $prefix) {
        return $doctrinePhoneNumberRepository->findByPrefix($prefix);
    }, $logger);

    $results[] = [
        'test' => 'Recherche de numéros de téléphone par préfixe',
        'legacy' => $legacyResult,
        'doctrine' => $doctrineResult,
        'comparison' => compareResults($legacyResult, $doctrineResult, $logger)
    ];
}

// 8. Test de création d'entité (sans persistance)
$logger->info('--- Test: Création d\'entité (sans persistance) ---');

$legacyResult = benchmark('Legacy - Create User Entity', function () {
    $user = new \App\Models\User(
        'benchmark_user',
        password_hash('password', PASSWORD_DEFAULT),
        null,
        'benchmark@example.com',
        100,
        null,
        false
    );
    return $user;
}, $logger);

$doctrineResult = benchmark('Doctrine - Create User Entity', function () {
    $user = new \App\Entities\User();
    $user->setUsername('benchmark_user');
    $user->setEmail('benchmark@example.com');
    $user->setPassword(password_hash('password', PASSWORD_DEFAULT));
    $user->setIsAdmin(false);
    $user->setSmsCredit(100);
    return $user;
}, $logger);

$results[] = [
    'test' => 'Création d\'entité (sans persistance)',
    'legacy' => $legacyResult,
    'doctrine' => $doctrineResult,
    'comparison' => compareResults($legacyResult, $doctrineResult, $logger)
];

// Résumé des résultats
$logger->info('=== Résumé des benchmarks ===');

$totalTimeDiff = 0;
$totalMemoryDiff = 0;
$count = count($results);

foreach ($results as $result) {
    $totalTimeDiff += $result['comparison']['timePercentage'];
    $totalMemoryDiff += $result['comparison']['memoryPercentage'];

    $logger->info(sprintf(
        '%s - Temps: %s%%, Mémoire: %s%%',
        $result['test'],
        $result['comparison']['timePercentage'] > 0 ? '+' . $result['comparison']['timePercentage'] : $result['comparison']['timePercentage'],
        $result['comparison']['memoryPercentage'] > 0 ? '+' . $result['comparison']['memoryPercentage'] : $result['comparison']['memoryPercentage']
    ));
}

$avgTimeDiff = $count > 0 ? round($totalTimeDiff / $count, 2) : 0;
$avgMemoryDiff = $count > 0 ? round($totalMemoryDiff / $count, 2) : 0;

$logger->info(sprintf(
    'Moyenne - Temps: %s%%, Mémoire: %s%%',
    $avgTimeDiff > 0 ? '+' . $avgTimeDiff : $avgTimeDiff,
    $avgMemoryDiff > 0 ? '+' . $avgMemoryDiff : $avgMemoryDiff
));

// Recommandations
$logger->info('=== Recommandations ===');

if ($avgTimeDiff > 20) {
    $logger->warning('La performance en temps d\'exécution de Doctrine est significativement plus lente que l\'implémentation legacy.');
    $logger->info('Recommandations:');
    $logger->info('1. Vérifier les configurations de cache de Doctrine');
    $logger->info('2. Optimiser les requêtes DQL');
    $logger->info('3. Utiliser des requêtes natives pour les opérations critiques');
    $logger->info('4. Considérer l\'utilisation de vues matérialisées pour les requêtes complexes');
} elseif ($avgTimeDiff > 10) {
    $logger->info('La performance en temps d\'exécution de Doctrine est légèrement plus lente que l\'implémentation legacy.');
    $logger->info('Recommandations:');
    $logger->info('1. Vérifier les configurations de cache de Doctrine');
    $logger->info('2. Optimiser les requêtes les plus lentes');
} elseif ($avgTimeDiff < -10) {
    $logger->info('La performance en temps d\'exécution de Doctrine est meilleure que l\'implémentation legacy.');
    $logger->info('Recommandation: Continuer à utiliser Doctrine ORM');
} else {
    $logger->info('La performance en temps d\'exécution de Doctrine est comparable à l\'implémentation legacy.');
    $logger->info('Recommandation: Aucune optimisation nécessaire');
}

if ($avgMemoryDiff > 20) {
    $logger->warning('L\'utilisation de la mémoire de Doctrine est significativement plus élevée que l\'implémentation legacy.');
    $logger->info('Recommandations:');
    $logger->info('1. Utiliser des requêtes partielles (SELECT) au lieu de charger des entités complètes');
    $logger->info('2. Optimiser les associations entre entités');
    $logger->info('3. Utiliser des requêtes natives pour les opérations critiques');
} elseif ($avgMemoryDiff > 10) {
    $logger->info('L\'utilisation de la mémoire de Doctrine est légèrement plus élevée que l\'implémentation legacy.');
    $logger->info('Recommandations:');
    $logger->info('1. Optimiser les requêtes qui consomment le plus de mémoire');
} elseif ($avgMemoryDiff < -10) {
    $logger->info('L\'utilisation de la mémoire de Doctrine est meilleure que l\'implémentation legacy.');
    $logger->info('Recommandation: Continuer à utiliser Doctrine ORM');
} else {
    $logger->info('L\'utilisation de la mémoire de Doctrine est comparable à l\'implémentation legacy.');
    $logger->info('Recommandation: Aucune optimisation nécessaire');
}

// Fin des benchmarks
$endTime = microtime(true);
$totalDuration = round($endTime - $startTime, 2);

$logger->info(sprintf('Benchmarks terminés en %s secondes', $totalDuration));
exit(0);
