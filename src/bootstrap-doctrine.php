<?php

use App\Repositories\Doctrine\CustomRepositoryFactory;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * Bootstrap Doctrine ORM in standalone mode
 * 
 * This file initializes Doctrine ORM without depending on Symfony's framework bundle.
 * It reads configuration from environment variables and sets up the EntityManager.
 * 
 * @return EntityManager The configured EntityManager instance
 */
return (function () {
    // Load environment variables if not already loaded
    if (!isset($_ENV['APP_ENV'])) {
        if (file_exists(__DIR__ . '/../.env')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
            $dotenv->load();
        }
    }

    // Define paths to entity classes
    $paths = [__DIR__ . '/Entities'];

    // Determine if we're in development mode
    $isDevMode = true;

    // Configure Doctrine caching based on environment
    $cache = new ArrayAdapter(); // No caching in dev mode

    // Create configuration
    $config = ORMSetup::createAttributeMetadataConfiguration(
        $paths,
        $isDevMode,
        null, // Proxy directory - let Doctrine use default
        $cache
    );

    // Ensure the var directory exists
    $varDir = __DIR__ . '/../var';
    if (!is_dir($varDir)) {
        mkdir($varDir, 0777, true);
    }

    // Database connection parameters - use SQLite for simplicity
    $dbPath = $varDir . '/database.sqlite';
    $dbParams = [
        'driver' => 'pdo_sqlite',
        'path' => $dbPath
    ];

    // Create connection
    $connection = DriverManager::getConnection($dbParams, $config);

    // Set custom repository factory
    $config->setRepositoryFactory(new CustomRepositoryFactory());

    // Create EntityManager
    return new EntityManager($connection, $config);
})();
