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
use Doctrine\DBAL\DriverManager;
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
use App\Services\AuthService;
use App\Services\Interfaces\AuthServiceInterface;
use App\Services\EmailService;
use App\Services\Interfaces\EmailServiceInterface;
use App\GraphQL\Context\GraphQLContextFactory;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Doctrine\UserRepository;
use App\Repositories\Interfaces\ContactGroupMembershipRepositoryInterface;
use App\Repositories\Doctrine\ContactGroupMembershipRepository;
use App\Repositories\Interfaces\ContactGroupRepositoryInterface;
use App\Repositories\Doctrine\ContactGroupRepository;
use App\Repositories\Interfaces\ContactRepositoryInterface;
use App\Repositories\Doctrine\ContactRepository;
use App\GraphQL\Formatters\GraphQLFormatterInterface;
use App\GraphQL\Formatters\GraphQLFormatterService;
use App\Repositories\Interfaces\CustomSegmentRepositoryInterface;
use App\Repositories\Doctrine\CustomSegmentRepository;
use App\Services\SenderNameService;
use App\Services\OrangeAPIConfigService;
use App\Repositories\Interfaces\SenderNameRepositoryInterface;
use App\Repositories\Doctrine\SenderNameRepository;
use App\Repositories\Interfaces\OrangeAPIConfigRepositoryInterface;
use App\Repositories\Doctrine\OrangeAPIConfigRepository;
use App\Repositories\Interfaces\SMSHistoryRepositoryInterface;
use App\Repositories\Doctrine\SMSHistoryRepository;
use App\Services\WhatsApp\Bus\CommandBus;
use App\Services\WhatsApp\Bus\LoggingMiddleware;
use App\Services\WhatsApp\Handlers\BulkSendHandler;
use App\Services\WhatsApp\Events\EventDispatcher;

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
    } else {
        // Méthode de secours : utiliser la configuration XML/YAML si les attributs ne sont pas disponibles
        $logMessage("Utilisation de createXMLMetadataConfiguration en fallback");
        $config = ORMSetup::createXMLMetadataConfiguration(
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
    
    // Créer l'EntityManager avec la méthode moderne
    $logMessage("Création de l'EntityManager avec DriverManager");
    
    // Créer la connexion DBAL
    $connection = DriverManager::getConnection($conn, $config);
    
    // Créer l'EntityManager
    $entityManager = new EntityManager($connection, $config);
    
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
        WhatsAppApiClientInterface::class => function (LoggerInterface $logger) {
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
        ) {
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
        
        // User Repository
        UserRepositoryInterface::class => function (EntityManager $entityManager) {
            return new UserRepository($entityManager, $entityManager->getClassMetadata(\App\Entities\User::class));
        },
        
        // Contact Repository
        ContactRepositoryInterface::class => function (EntityManager $entityManager) {
            return new ContactRepository($entityManager);
        },
        
        // Contact Group Membership Repository
        ContactGroupMembershipRepositoryInterface::class => function (EntityManager $entityManager) {
            return new ContactGroupMembershipRepository($entityManager);
        },
        
        // Contact Group Repository
        ContactGroupRepositoryInterface::class => function (EntityManager $entityManager, ContactRepositoryInterface $contactRepo, ContactGroupMembershipRepositoryInterface $membershipRepo) {
            return new ContactGroupRepository($entityManager, $contactRepo, $membershipRepo);
        },
        
        // Custom Segment Repository
        CustomSegmentRepositoryInterface::class => function (EntityManager $entityManager) {
            return new CustomSegmentRepository($entityManager);
        },
        
        // Sender Name Repository
        SenderNameRepositoryInterface::class => function (EntityManager $entityManager) {
            return new SenderNameRepository($entityManager);
        },
        
        // Orange API Config Repository
        OrangeAPIConfigRepositoryInterface::class => function (EntityManager $entityManager) {
            return new OrangeAPIConfigRepository($entityManager);
        },
        
        // SMS History Repository
        SMSHistoryRepositoryInterface::class => function (EntityManager $entityManager) {
            return new SMSHistoryRepository($entityManager);
        },
        
        // Sender Name Service
        SenderNameService::class => function (SenderNameRepositoryInterface $senderNameRepo) {
            return new SenderNameService($senderNameRepo);
        },
        
        // Orange API Config Service
        OrangeAPIConfigService::class => function (OrangeAPIConfigRepositoryInterface $orangeAPIConfigRepo) {
            return new OrangeAPIConfigService($orangeAPIConfigRepo);
        },
        
        // GraphQL Formatter Service
        GraphQLFormatterInterface::class => function (CustomSegmentRepositoryInterface $customSegmentRepo, LoggerInterface $logger, SenderNameService $senderNameService, OrangeAPIConfigService $orangeAPIConfigService) {
            return new GraphQLFormatterService($customSegmentRepo, $logger, $senderNameService, $orangeAPIConfigService);
        },
        
        // Email Service
        EmailServiceInterface::class => function () {
            return new EmailService();
        },
        
        // Auth Service
        AuthServiceInterface::class => function (UserRepositoryInterface $userRepo, EmailServiceInterface $emailService, LoggerInterface $logger) {
            return new AuthService($userRepo, $emailService, $logger);
        },
        
        // GraphQL Context Factory
        GraphQLContextFactory::class => function ($container, AuthServiceInterface $authService) {
            return new GraphQLContextFactory($container, $authService);
        },
        
        // EventDispatcher pour les événements d'envoi en masse
        EventDispatcher::class => function (LoggerInterface $logger) {
            return new EventDispatcher($logger);
        },
        
        // CommandBus avec BulkSendHandler
        'whatsapp.command_bus.bulk' => function (LoggerInterface $logger, WhatsAppServiceInterface $whatsappService, EventDispatcher $eventDispatcher) {
            $commandBus = new CommandBus($logger);
            
            // Ajouter le middleware de logging
            $commandBus->addMiddleware(new LoggingMiddleware($logger));
            
            // Enregistrer le BulkSendHandler
            $bulkSendHandler = new BulkSendHandler($whatsappService, $eventDispatcher, $logger);
            $commandBus->registerHandler($bulkSendHandler);
            
            return $commandBus;
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