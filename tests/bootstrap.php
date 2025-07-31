<?php

// Path to the application's "vendor/"
require_once __DIR__ . '/../vendor/autoload.php';

// Set up environment variables for testing
$_ENV['APP_ENV'] = 'test';
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = ':memory:';

// Set up mock for API endpoints if needed
define('MOCK_API', true);

// Include any other bootstrapping code specific to testing