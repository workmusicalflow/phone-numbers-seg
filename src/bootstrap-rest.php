<?php
/**
 * Fichier de bootstrap simplifié pour les endpoints REST
 * 
 * Ce fichier initialise le conteneur DI avec uniquement les dépendances
 * nécessaires pour les endpoints REST WhatsApp, ce qui simplifie le debug.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Charger les variables d'environnement à partir du fichier .env
if (class_exists('\\Dotenv\\Dotenv') && file_exists(__DIR__ . '/../.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

use DI\ContainerBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use App\Services\WhatsApp\WhatsAppService;
use App\Services\WhatsApp\WhatsAppApiClient;
use App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface;
use App\Services\WhatsApp\WhatsAppTemplateService;
use App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateHistoryRepositoryInterface;
use App\Repositories\Doctrine\WhatsApp\WhatsAppMessageHistoryRepository;
use App\Repositories\Doctrine\WhatsApp\WhatsAppTemplateRepository;
use App\Repositories\Doctrine\WhatsApp\WhatsAppTemplateHistoryRepository;
use Psr\Log\LoggerInterface;
use App\Services\SimpleLogger;

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
    // Vérifier quelle méthode est disponible selon la version de Doctrine
    if (method_exists(ORMSetup::class, 'createAttributeMetadataConfiguration')) {
        // Nouvelle méthode pour Doctrine 3.x (PHP 8 avec attributs)
        $logMessage("Utilisation de createAttributeMetadataConfiguration (Doctrine 3.x)");
        $config = ORMSetup::createAttributeMetadataConfiguration(
            $paths,
            $isDevMode,
            $proxyDir,
            $cache
        );
    } else if (method_exists(ORMSetup::class, 'createAnnotationMetadataConfiguration')) {
        // Ancienne méthode pour Doctrine 2.x (avec annotations)
        $logMessage("Utilisation de createAnnotationMetadataConfiguration (Doctrine 2.x)");
        $config = ORMSetup::createAnnotationMetadataConfiguration(
            $paths,
            $isDevMode,
            $proxyDir,
            $cache,
            $useSimpleAnnotationReader
        );
    } else {
        // Méthode de secours pour les versions très récentes
        $logMessage("Utilisation de createDefaultMetadataConfiguration");
        $config = ORMSetup::createDefaultConfiguration(
            $paths,
            $isDevMode,
            $proxyDir,
            $cache
        );
    }
    
    // Base de données SQLite
    $dbPath = __DIR__ . '/../var/database.sqlite';
    $logMessage("Chemin de la base de données: " . $dbPath);
    
    $conn = [
        'driver' => 'pdo_sqlite',
        'path' => $dbPath,
    ];
    
    // Créer l'EntityManager
    $logMessage("Création de l'EntityManager");
    
    // Vérifier la méthode disponible pour créer l'EntityManager selon la version de Doctrine
    if (method_exists(EntityManager::class, 'create')) {
        // Méthode traditionnelle pour Doctrine 2.x
        $logMessage("Utilisation de EntityManager::create (Doctrine 2.x)");
        $entityManager = EntityManager::create($conn, $config);
    } else {
        // Méthode pour Doctrine 3.x
        $logMessage("Utilisation de la nouvelle méthode pour Doctrine 3.x");
        // Utilisation de la factory
        $entityManager = new \Doctrine\ORM\EntityManager(
            \Doctrine\DBAL\DriverManager::getConnection($conn, $config),
            $config
        );
    }
    
    // Créer le conteneur
    $logMessage("Création du conteneur DI");
    $containerBuilder = new ContainerBuilder();
    
    // Définitions
    $containerBuilder->addDefinitions([
        EntityManager::class => $entityManager,
        
        // Configuration WhatsApp (valeurs codées en dur pour les tests uniquement)
        'whatsapp.app_id' => '1193922949108494',
        'whatsapp.phone_number_id' => '660953787095211',
        'whatsapp.waba_id' => '664409593123173',
        'whatsapp.api_version' => 'v22.0',
        'whatsapp.access_token' => 'EAAQ93dlFUw4BOZCu6OPmzQuo47pE8eYgGCJLWaQzeyHo03ZCmUWNOQZABt0NeJgVfx9zgurvJc3YynNmFZBgfsCslzydmfzdWZA3onZCyGQsgSo1ZAC6o7ZCgzukF10wmeCjfWcWItPeOw0hanzT0V5ShOIQZCEzVF9qP2aGALaD5ZCTvy95DhjlUwOwijVNAEXpGzEG0YKIsRI8ZCngj9BiXLltt3azinQQYgPBIs9bZA6K',
        
        // Configuration de base (valeurs codées en dur pour les tests uniquement)
        'whatsapp.config' => [
            'app_id' => '1193922949108494',
            'phone_number_id' => '660953787095211',
            'waba_id' => '664409593123173',
            'whatsapp_business_account_id' => '664409593123173',
            'api_version' => 'v22.0',
            'access_token' => 'EAAQ93dlFUw4BOZCu6OPmzQuo47pE8eYgGCJLWaQzeyHo03ZCmUWNOQZABt0NeJgVfx9zgurvJc3YynNmFZBgfsCslzydmfzdWZA3onZCyGQsgSo1ZAC6o7ZCgzukF10wmeCjfWcWItPeOw0hanzT0V5ShOIQZCEzVF9qP2aGALaD5ZCTvy95DhjlUwOwijVNAEXpGzEG0YKIsRI8ZCngj9BiXLltt3azinQQYgPBIs9bZA6K',
            'webhook_verify_token' => 'oracle_whatsapp_verify_token_2025',
            'base_url' => 'https://graph.facebook.com',
        ],
        
        // Logger
        LoggerInterface::class => function () {
            return new SimpleLogger('whatsapp-rest');
        },
        
        // Repositories
        WhatsAppMessageHistoryRepositoryInterface::class => function (EntityManager $entityManager) {
            return new WhatsAppMessageHistoryRepository($entityManager, \App\Entities\WhatsApp\WhatsAppMessageHistory::class);
        },
        
        WhatsAppTemplateRepositoryInterface::class => function (EntityManager $entityManager) {
            return new WhatsAppTemplateRepository($entityManager, \App\Entities\WhatsApp\WhatsAppTemplate::class);
        },
        
        WhatsAppTemplateHistoryRepositoryInterface::class => function (EntityManager $entityManager) {
            return new WhatsAppTemplateHistoryRepository($entityManager, \App\Entities\WhatsApp\WhatsAppTemplateHistory::class);
        },
        
        // Services WhatsApp
        WhatsAppApiClientInterface::class => function (LoggerInterface $logger) use ($containerBuilder) {
            $config = [
                'app_id' => '1193922949108494',
                'phone_number_id' => '660953787095211',
                'waba_id' => '664409593123173',
                'whatsapp_business_account_id' => '664409593123173', // Clé additionnelle utilisée dans certaines méthodes
                'api_version' => 'v22.0',
                'access_token' => 'EAAQ93dlFUw4BOZCu6OPmzQuo47pE8eYgGCJLWaQzeyHo03ZCmUWNOQZABt0NeJgVfx9zgurvJc3YynNmFZBgfsCslzydmfzdWZA3onZCyGQsgSo1ZAC6o7ZCgzukF10wmeCjfWcWItPeOw0hanzT0V5ShOIQZCEzVF9qP2aGALaD5ZCTvy95DhjlUwOwijVNAEXpGzEG0YKIsRI8ZCngj9BiXLltt3azinQQYgPBIs9bZA6K',
                'base_url' => 'https://graph.facebook.com', // URL de base de l'API WhatsApp Cloud
            ];
            return new WhatsAppApiClient($logger, $config);
        },
        
        WhatsAppTemplateServiceInterface::class => function (
            WhatsAppApiClientInterface $apiClient,
            WhatsAppTemplateRepositoryInterface $templateRepo,
            LoggerInterface $logger
        ) {
            return new WhatsAppTemplateService($apiClient, $templateRepo, $logger);
        },
        
        WhatsAppServiceInterface::class => function (
            WhatsAppApiClientInterface $apiClient,
            WhatsAppMessageHistoryRepositoryInterface $messageHistoryRepo,
            WhatsAppTemplateRepositoryInterface $templateRepo,
            LoggerInterface $logger,
            WhatsAppTemplateServiceInterface $templateService
        ) use ($containerBuilder) {
            // Template history repo is optional
            $templateHistoryRepo = null;
            
            $config = [
                'app_id' => '1193922949108494',
                'phone_number_id' => '660953787095211',
                'waba_id' => '664409593123173',
                'whatsapp_business_account_id' => '664409593123173',
                'api_version' => 'v22.0',
                'access_token' => 'EAAQ93dlFUw4BOZCu6OPmzQuo47pE8eYgGCJLWaQzeyHo03ZCmUWNOQZABt0NeJgVfx9zgurvJc3YynNmFZBgfsCslzydmfzdWZA3onZCyGQsgSo1ZAC6o7ZCgzukF10wmeCjfWcWItPeOw0hanzT0V5ShOIQZCEzVF9qP2aGALaD5ZCTvy95DhjlUwOwijVNAEXpGzEG0YKIsRI8ZCngj9BiXLltt3azinQQYgPBIs9bZA6K',
                'base_url' => 'https://graph.facebook.com' // URL de base de l'API WhatsApp Cloud
            ];
            return new WhatsAppService($apiClient, $messageHistoryRepo, $templateRepo, $logger, $config, $templateService, $templateHistoryRepo);
        },
        
        // Aliases concrets pour faciliter l'usage
        WhatsAppApiClient::class => \DI\get(WhatsAppApiClientInterface::class),
        WhatsAppService::class => \DI\get(WhatsAppServiceInterface::class),
        WhatsAppTemplateService::class => \DI\get(WhatsAppTemplateServiceInterface::class),
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