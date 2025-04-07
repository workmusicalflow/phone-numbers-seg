<?php

/**
 * Database Configuration
 * 
 * Centralized configuration for database connections.
 * Uses environment variables with sensible defaults.
 */

return [
    'driver' => getenv('DB_DRIVER') ?: 'sqlite', // 'mysql' or 'sqlite'

    'sqlite' => [
        'path' => __DIR__ . '/../database/database.sqlite',
    ],

    'mysql' => [
        'host' => getenv('MYSQL_HOST') ?: '127.0.0.1', // Use 127.0.0.1 instead of localhost for consistency
        'port' => getenv('MYSQL_PORT') ?: '3306',
        'database' => getenv('MYSQL_DATABASE') ?: 'oracle_sms',
        'username' => getenv('MYSQL_USER') ?: 'oracle_user',
        'password' => getenv('MYSQL_PASSWORD') ?: 'secure_password', // Replace with a strong default or enforce env var
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => 'InnoDB', // Specify InnoDB engine
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false, // Important for security and performance
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci' // Ensure correct encoding
        ],
    ],

    // Add other database configurations if needed (e.g., redis for caching)
    'redis' => [
        'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
        'password' => getenv('REDIS_PASSWORD') ?: null,
        'port' => getenv('REDIS_PORT') ?: '6379',
        'database' => getenv('REDIS_DB') ?: 0,
    ],
];
