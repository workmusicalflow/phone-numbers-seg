<?php
/**
 * Fichier de bootstrap simplifié pour les endpoints REST
 * 
 * Ce fichier initialise le conteneur DI avec uniquement les dépendances
 * nécessaires pour les endpoints REST WhatsApp, ce qui simplifie le debug.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use App\Services\WhatsApp\WhatsAppService;
use App\Services\WhatsApp\WhatsAppApiClient;

// Fonction d'aide pour le logging
$logFile = __DIR__ . '/../logs/bootstrap-rest-debug.log';
$logMessage = function($message) use ($logFile) {
    $timestamp = date('Y-m-d H:i:s');
    $formattedMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
};

$logMessage("Démarrage de bootstrap-rest.php");

// Configuration Doctrine
$logMessage("Configuration de Doctrine");
$isDevMode = true;
$proxyDir = null;
$cache = null;
$useSimpleAnnotationReader = false;

// Chemins des entités
$paths = [__DIR__ . '/Entities'];
$logMessage("Chemins des entités: " . implode(", ", $paths));

// Créer la configuration
try {
    $config = ORMSetup::createAnnotationMetadataConfiguration(
        $paths,
        $isDevMode,
        $proxyDir,
        $cache,
        $useSimpleAnnotationReader
    );
    
    // Base de données SQLite
    $dbPath = __DIR__ . '/../var/database.sqlite';
    $logMessage("Chemin de la base de données: " . $dbPath);
    
    $conn = [
        'driver' => 'pdo_sqlite',
        'path' => $dbPath,
    ];
    
    // Créer l'EntityManager
    $logMessage("Création de l'EntityManager");
    $entityManager = EntityManager::create($conn, $config);
    
    // Créer le conteneur
    $logMessage("Création du conteneur DI");
    $containerBuilder = new ContainerBuilder();
    
    // Définitions
    $containerBuilder->addDefinitions([
        EntityManager::class => $entityManager,
        
        // Configuration WhatsApp
        'whatsapp.app_id' => $_ENV['WHATSAPP_APP_ID'] ?? getenv('WHATSAPP_APP_ID') ?? '1234567890',
        'whatsapp.phone_number_id' => $_ENV['WHATSAPP_PHONE_NUMBER_ID'] ?? getenv('WHATSAPP_PHONE_NUMBER_ID') ?? '1234567890',
        'whatsapp.waba_id' => $_ENV['WHATSAPP_WABA_ID'] ?? getenv('WHATSAPP_WABA_ID') ?? '1234567890',
        'whatsapp.api_version' => $_ENV['WHATSAPP_API_VERSION'] ?? getenv('WHATSAPP_API_VERSION') ?? 'v22.0',
        'whatsapp.access_token' => $_ENV['WHATSAPP_ACCESS_TOKEN'] ?? getenv('WHATSAPP_API_TOKEN') ?? 'test_token',
        
        // Services WhatsApp
        WhatsAppApiClient::class => \DI\autowire(),
        WhatsAppService::class => \DI\autowire(),
    ]);
    
    // Construire le conteneur
    $logMessage("Construction du conteneur");
    $container = $containerBuilder->build();
    
    $logMessage("Container DI créé avec succès");
    return $container;
} catch (\Throwable $e) {
    $logMessage("ERREUR lors de l'initialisation du bootstrap: " . $e->getMessage());
    $logMessage("Trace: " . $e->getTraceAsString());
    throw $e;
}