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
        'path' => __DIR__ . '/../database/database.sqlite',
    ],
];
