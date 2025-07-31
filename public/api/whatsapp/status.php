<?php
/**
 * Simple endpoint de statut pour tester si l'API WhatsApp REST est accessible
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Utils\CorsHelper;
use App\Utils\JsonResponse;

// Activer CORS pour permettre les requêtes depuis le frontend
CorsHelper::enableCors();

// Informations de base sur l'environnement
$phpVersion = phpversion();
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$requestTime = date('Y-m-d H:i:s');

// Tester l'accès au système de fichiers
$logsAccessible = is_dir(__DIR__ . '/../../../logs') && is_writable(__DIR__ . '/../../../logs');
$vendorAccessible = is_dir(__DIR__ . '/../../../vendor') && file_exists(__DIR__ . '/../../../vendor/autoload.php');
$srcAccessible = is_dir(__DIR__ . '/../../../src') && file_exists(__DIR__ . '/../../../src/bootstrap-rest.php');

// Tester l'accès à la base de données SQLite
$dbPath = __DIR__ . '/../../../var/database.sqlite';
$dbAccessible = file_exists($dbPath) && is_readable($dbPath);
$dbSize = $dbAccessible ? filesize($dbPath) : 0;

// Vérifier si les classes nécessaires sont disponibles
$requiredClasses = [
    'App\Utils\JsonResponse' => class_exists('App\Utils\JsonResponse'),
    'App\Utils\CorsHelper' => class_exists('App\Utils\CorsHelper'),
    'App\Services\WhatsApp\WhatsAppService' => class_exists('App\Services\WhatsApp\WhatsAppService'),
    'App\Services\WhatsApp\WhatsAppApiClient' => class_exists('App\Services\WhatsApp\WhatsAppApiClient'),
    'Doctrine\ORM\EntityManager' => class_exists('Doctrine\ORM\EntityManager')
];

// Construire la réponse
$response = [
    'status' => 'online',
    'timestamp' => $requestTime,
    'environment' => [
        'php_version' => $phpVersion,
        'server' => $serverSoftware,
        'operating_system' => PHP_OS,
        'memory_limit' => ini_get('memory_limit')
    ],
    'filesystem' => [
        'logs_accessible' => $logsAccessible,
        'vendor_accessible' => $vendorAccessible,
        'src_accessible' => $srcAccessible,
        'database_accessible' => $dbAccessible,
        'database_size' => $dbAccessible ? number_format($dbSize / 1024 / 1024, 2) . ' MB' : 'N/A'
    ],
    'classes' => $requiredClasses,
    'endpoint_info' => 'Cet endpoint est utilisé pour tester l\'accès à l\'API REST WhatsApp',
    'api_version' => '1.0'
];

// Envoyer la réponse
JsonResponse::success($response);