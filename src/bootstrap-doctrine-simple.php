<?php

use App\Repositories\Doctrine\CustomRepositoryFactory;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

// Autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Define paths to entity classes
$paths = [__DIR__ . '/Entities'];

// Always use development mode for simple bootstrap
$isDevMode = true;

// Set up cache
if ($isDevMode) {
    $cache = new ArrayAdapter();
    $config = ORMSetup::createAttributeMetadataConfiguration($paths, $isDevMode, null, $cache);
} else {
    $cache = new FilesystemAdapter('doctrine_cache', 3600, __DIR__ . '/../var/cache/doctrine');
    $config = ORMSetup::createAttributeMetadataConfiguration($paths, $isDevMode, null, $cache);
}

// Set naming strategy
$config->setNamingStrategy(new \Doctrine\ORM\Mapping\UnderscoreNamingStrategy());

// Set up database connection
$connectionParams = [
    'driver' => 'pdo_sqlite',
    'path' => __DIR__ . '/../var/database.sqlite',
];

$connection = DriverManager::getConnection($connectionParams, $config);

// Create EntityManager
$entityManager = new EntityManager($connection, $config);

return $entityManager;