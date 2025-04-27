<?php

use DI\Container;
use function DI\factory;
use Doctrine\ORM\EntityManagerInterface;
use App\Services\Observers\SMSHistoryObserver;

/**
 * Other miscellaneous definitions for Dependency Injection Container
 * (Core framework setup, Observers, Controllers, Middleware, etc.)
 */
return [
    // --- Core Setup ---

    // Doctrine EntityManager
    EntityManagerInterface::class => factory(function () {
        // Ensure Doctrine bootstrap returns the EntityManager
        $entityManager = require __DIR__ . '/../../bootstrap-doctrine.php';
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \RuntimeException('bootstrap-doctrine.php must return an instance of EntityManagerInterface');
        }
        return $entityManager;
    }),

    // Alias for EntityManager with concrete implementation
    \Doctrine\ORM\EntityManager::class => factory(function (Container $container) {
        return $container->get(EntityManagerInterface::class);
    }),

    // PDO instance for database access (primarily for legacy or direct queries)
    PDO::class => factory(function () {
        $dbConfig = require __DIR__ . '/../database.php';

        // Force SQLite as the database driver due to MySQL service error (or read from config)
        $driver = $dbConfig['driver'] ?? 'sqlite'; // Default to sqlite if not set

        if ($driver === 'sqlite') {
            $dsn = 'sqlite:' . $dbConfig['sqlite']['path'];
            $pdo = new PDO($dsn, null, null, [
                PDO::ATTR_TIMEOUT => $dbConfig['sqlite']['timeout'] ?? 5, // Example timeout
            ]);
        } elseif ($driver === 'mysql') {
            $dsn = "mysql:host={$dbConfig['mysql']['host']};dbname={$dbConfig['mysql']['dbname']};charset={$dbConfig['mysql']['charset']}";
            $pdo = new PDO($dsn, $dbConfig['mysql']['user'], $dbConfig['mysql']['password'], [
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$dbConfig['mysql']['charset']}",
                PDO::ATTR_TIMEOUT => $dbConfig['mysql']['timeout'] ?? 5, // Example timeout
            ]);
        } else {
            throw new \InvalidArgumentException("Unsupported database driver: {$driver}");
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Consistent fetch mode
        return $pdo;
    }),

    // Event Manager (Subject)
    // Moved from interfaces.php as it's a concrete implementation setup
    \App\Services\Interfaces\SubjectInterface::class => factory(function () {
        return new \App\Services\EventManager();
    }),

    // --- Observers ---

    SMSHistoryObserver::class => factory(function (Container $container) {
        return new SMSHistoryObserver(
            $container->get(\App\Repositories\Interfaces\SMSHistoryRepositoryInterface::class)
        );
    }),

    // --- Controllers ---

    \App\Controllers\PhoneController::class => factory(function (Container $container) {
        // Consider injecting specific dependencies if needed, or rely on autowiring if simple enough
        // Example assumes it might still need PDO for some legacy operations
        return new \App\Controllers\PhoneController($container->get(PDO::class));
    }),

    \App\Controllers\SMSController::class => factory(function (Container $container) {
        return new \App\Controllers\SMSController(
            $container->get(PDO::class), // Keep PDO if needed for legacy parts
            $container->get(\App\Services\SMSService::class), // Use concrete service
            $container->get(\App\Repositories\Interfaces\PhoneNumberRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\CustomSegmentRepositoryInterface::class)
        );
    }),

    \App\Controllers\ImportExportController::class => factory(function (Container $container) {
        return new \App\Controllers\ImportExportController(
            $container->get(\App\Services\CSVImportService::class), // Use concrete service
            $container->get(\App\Services\ExportService::class)  // Use concrete service
        );
    }),

    // --- Middleware ---

    \App\Middleware\SessionAuthMiddleware::class => factory(function () {
        // SessionAuthMiddleware likely doesn't need constructor injection
        return new \App\Middleware\SessionAuthMiddleware();
    }),

];
