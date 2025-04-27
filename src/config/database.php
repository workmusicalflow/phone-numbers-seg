<?php

/**
 * Database Configuration
 * 
 * Centralized configuration for database connections.
 * Uses environment variables with sensible defaults.
 */

return [
    'driver' => getenv('DB_DRIVER') ?: 'sqlite', // Default to SQLite

    'sqlite' => [
        // Point to the same database used by Doctrine bootstrap
        'path' => __DIR__ . '/../../var/database.sqlite',
    ],
];
