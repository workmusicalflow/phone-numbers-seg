<?php
/**
 * Configuration d'injection de dépendances pour les services WhatsApp
 */

use App\Entities\WhatsApp\WhatsAppMessage;
use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\Entities\WhatsApp\WhatsAppTemplate;
use App\Entities\WhatsApp\WhatsAppQueue;
use App\GraphQL\Controllers\WhatsApp\WebhookController;
use App\Repositories\Doctrine\WhatsApp\WhatsAppMessageRepository;
use App\Repositories\Doctrine\WhatsApp\WhatsAppMessageHistoryRepository;
use App\Repositories\Doctrine\WhatsApp\WhatsAppTemplateRepository;
use App\Repositories\Doctrine\WhatsApp\WhatsAppQueueRepository;
use App\Repositories\Interfaces\WhatsApp\WhatsAppMessageRepositoryInterface;
use App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface;
use App\Repositories\Interfaces\WhatsApp\WhatsAppQueueRepositoryInterface;
use App\Services\Interfaces\WhatsApp\WebhookVerificationServiceInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppMessageServiceInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppTemplateSyncServiceInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppMonitoringServiceInterface;
use App\Repositories\Interfaces\WhatsApp\WhatsAppApiMetricRepositoryInterface;
use App\Repositories\Doctrine\WhatsApp\WhatsAppApiMetricRepository;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateHistoryRepositoryInterface;
use App\Repositories\Doctrine\WhatsApp\WhatsAppTemplateHistoryRepository;
use App\Services\WhatsApp\WebhookVerificationService;
use App\Services\WhatsApp\WhatsAppApiClient;
use App\Services\WhatsApp\WhatsAppMessageService;
use App\Services\WhatsApp\WhatsAppService;
use App\Services\WhatsApp\WhatsAppServiceEnhanced;
use App\Services\WhatsApp\WhatsAppServiceRefactored;
use App\Services\WhatsApp\WhatsAppServiceWithCommands;
use App\Services\WhatsApp\WhatsAppServiceWithResilience;
use App\Services\WhatsApp\WhatsAppTemplateService;
use App\Services\WhatsApp\WhatsAppTemplateSyncService;
use App\Services\WhatsApp\WhatsAppMonitoringService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

// Charger la configuration de résilience
$resilienceConfig = require __DIR__ . '/whatsapp_resilience.php';

return array_merge([
    // Configuration
    'whatsapp.config' => function() {
        return require __DIR__ . '/../whatsapp.php';
    },
    
    // Repositories
    WhatsAppMessageRepositoryInterface::class => \DI\factory(function(EntityManagerInterface $entityManager) {
        return new WhatsAppMessageRepository($entityManager, WhatsAppMessageHistory::class);
    }),
    
    WhatsAppMessageHistoryRepositoryInterface::class => \DI\factory(function(EntityManagerInterface $entityManager) {
        return new WhatsAppMessageHistoryRepository($entityManager, WhatsAppMessageHistory::class);
    }),
    
    WhatsAppTemplateRepositoryInterface::class => \DI\factory(function(EntityManagerInterface $entityManager) {
        return new WhatsAppTemplateRepository($entityManager, WhatsAppTemplate::class);
    }),
    
    WhatsAppQueueRepositoryInterface::class => \DI\factory(function(EntityManagerInterface $entityManager) {
        return new WhatsAppQueueRepository($entityManager, WhatsAppQueue::class);
    }),
    
    // Services
    WebhookVerificationServiceInterface::class => \DI\factory(function(LoggerInterface $logger, \Psr\Container\ContainerInterface $container) {
        $config = $container->get('whatsapp.config');
        return new WebhookVerificationService(
            $config['webhook_verify_token'],
            $logger
        );
    }),
    
    WhatsAppApiClientInterface::class => \DI\factory(function(LoggerInterface $logger, \Psr\Container\ContainerInterface $container) {
        $config = $container->get('whatsapp.config');
        return new WhatsAppApiClient($logger, $config);
    }),
    
    WhatsAppMessageServiceInterface::class => \DI\create(WhatsAppMessageService::class)
        ->constructor(
            \DI\get(WhatsAppMessageRepositoryInterface::class),
            \DI\get(WhatsAppApiClientInterface::class),
            \DI\get(LoggerInterface::class)
        ),
    
    WhatsAppServiceInterface::class => \DI\factory(function(\Psr\Container\ContainerInterface $container) {
        return new WhatsAppServiceWithResilience(
            $container->get(WhatsAppApiClientInterface::class),
            $container->get(WhatsAppMessageHistoryRepositoryInterface::class),
            $container->get(WhatsAppTemplateRepositoryInterface::class),
            $container->get(LoggerInterface::class),
            $container->get('whatsapp.config'),
            $container->get(WhatsAppTemplateServiceInterface::class),
            $container->get(\App\Services\WhatsApp\ResilientWhatsAppClient::class)
        );
    }),
    
    // Service WhatsApp refactorisé (Phase 1)
    WhatsAppServiceRefactored::class => \DI\factory(function(\Psr\Container\ContainerInterface $container) {
        return new WhatsAppServiceRefactored(
            $container->get(WhatsAppApiClientInterface::class),
            $container->get(WhatsAppMessageHistoryRepositoryInterface::class),
            $container->get(WhatsAppTemplateRepositoryInterface::class),
            $container->get(LoggerInterface::class),
            $container->get('whatsapp.config'),
            $container->get(WhatsAppTemplateServiceInterface::class)
        );
    }),
    
    // Service WhatsApp avec Commands (Phase 2) 
    WhatsAppServiceWithCommands::class => \DI\factory(function(\Psr\Container\ContainerInterface $container) {
        return new WhatsAppServiceWithCommands(
            $container->get(WhatsAppApiClientInterface::class),
            $container->get(WhatsAppMessageHistoryRepositoryInterface::class),
            $container->get(WhatsAppTemplateRepositoryInterface::class),
            $container->get(LoggerInterface::class),
            $container->get('whatsapp.config'),
            $container->get(WhatsAppTemplateServiceInterface::class)
        );
    }),
    
    \App\Services\WhatsApp\WhatsAppWebhookService::class => \DI\factory(function(\Psr\Container\ContainerInterface $container) {
        return new \App\Services\WhatsApp\WhatsAppWebhookService(
            $container->get(WhatsAppMessageHistoryRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class),
            $container->get(\App\Services\PhoneNumberNormalizerService::class),
            $container->get(LoggerInterface::class),
            $container->get('whatsapp.config')
        );
    }),
    
    // Services WhatsApp Template
    WhatsAppTemplateServiceInterface::class => \DI\create(WhatsAppTemplateService::class)
        ->constructor(
            \DI\get(WhatsAppApiClientInterface::class),
            \DI\get(WhatsAppTemplateRepositoryInterface::class),
            \DI\get(LoggerInterface::class)
        ),
        
    // Service de synchronisation des templates WhatsApp
    WhatsAppTemplateSyncServiceInterface::class => \DI\create(WhatsAppTemplateSyncService::class)
        ->constructor(
            \DI\get(WhatsAppApiClientInterface::class),
            \DI\get(WhatsAppTemplateRepositoryInterface::class),
            \DI\get('App\\Repositories\\Interfaces\\WhatsApp\\WhatsAppUserTemplateRepositoryInterface'),
            \DI\get('App\\Repositories\\Interfaces\\UserRepositoryInterface'),
            \DI\get(EntityManagerInterface::class),
            \DI\get(LoggerInterface::class)
        ),

    // Controllers
    'App\\GraphQL\\Controllers\\WhatsApp\\WebhookController' => \DI\create('App\\GraphQL\\Controllers\\WhatsApp\\WebhookController')
        ->constructor(
            \DI\get(WebhookVerificationServiceInterface::class),
            \DI\get(WhatsAppMessageServiceInterface::class),
            \DI\get(LoggerInterface::class)
        ),
    
    'App\\GraphQL\\Controllers\\WhatsApp\\WhatsAppTemplateController' => \DI\create('App\\GraphQL\\Controllers\\WhatsApp\\WhatsAppTemplateController')
        ->constructor(
            \DI\get(WhatsAppServiceInterface::class),
            \DI\get(WhatsAppTemplateServiceInterface::class),
            \DI\get(LoggerInterface::class)
        ),
        
    // Contrôleur local avec templates de secours qui ne dépend pas de l'API
    'App\\GraphQL\\Controllers\\WhatsApp\\WhatsAppTemplateLocalController' => \DI\create('App\\GraphQL\\Controllers\\WhatsApp\\WhatsAppTemplateLocalController')
        ->constructor(
            \DI\get(WhatsAppServiceInterface::class),
            \DI\get(WhatsAppTemplateServiceInterface::class),
            \DI\get(LoggerInterface::class)
        ),
        
    // Contrôleur de monitoring WhatsApp
    'App\\GraphQL\\Controllers\\WhatsApp\\WhatsAppMonitoringController' => \DI\create('App\\GraphQL\\Controllers\\WhatsApp\\WhatsAppMonitoringController')
        ->constructor(
            \DI\get(WhatsAppMonitoringServiceInterface::class),
            \DI\get(LoggerInterface::class)
        ),
        
    // Resolvers
    'App\\GraphQL\\Resolvers\\WhatsApp\\WhatsAppMessageResolver' => \DI\create('App\\GraphQL\\Resolvers\\WhatsApp\\WhatsAppMessageResolver')
        ->constructor(
            \DI\get(WhatsAppMessageServiceInterface::class),
            \DI\get(WhatsAppApiClientInterface::class)
        ),
    
    'App\\GraphQL\\Resolvers\\WhatsApp\\WhatsAppResolver' => \DI\factory(function(\Psr\Container\ContainerInterface $container) {
        return new \App\GraphQL\Resolvers\WhatsApp\WhatsAppResolver(
            $container->get(WhatsAppServiceInterface::class),
            $container->get(WhatsAppMessageHistoryRepositoryInterface::class),
            $container->get(LoggerInterface::class)
        );
    }),

    // Ajouter le client REST WhatsApp
    'App\\Services\\Interfaces\\WhatsApp\\WhatsAppRestClientInterface' => \DI\factory(function(
        LoggerInterface $logger,
        WhatsAppMonitoringServiceInterface $monitoringService
    ) {
        $baseUrl = isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : 'http://localhost:8000';
        return new \App\Services\WhatsApp\WhatsAppRestClient($logger, $baseUrl, $monitoringService);
    }),
    
    'App\\GraphQL\\Resolvers\\WhatsApp\\WhatsAppTemplateResolver' => \DI\create('App\\GraphQL\\Resolvers\\WhatsApp\\WhatsAppTemplateResolver')
        ->constructor(
            \DI\get(WhatsAppTemplateServiceInterface::class),
            \DI\get(WhatsAppServiceInterface::class),
            \DI\get(WhatsAppTemplateRepositoryInterface::class),
            \DI\get('App\\Services\\Interfaces\\WhatsApp\\WhatsAppRestClientInterface'),
            \DI\get(LoggerInterface::class)
        ),
        
    'App\\GraphQL\\Resolvers\\WhatsApp\\WhatsAppCompleteTemplateResolver' => \DI\create('App\\GraphQL\\Resolvers\\WhatsApp\\WhatsAppCompleteTemplateResolver')
        ->constructor(
            \DI\get(WhatsAppTemplateServiceInterface::class),
            \DI\get(LoggerInterface::class)
        ),
        
    // Repository des métriques WhatsApp API
    WhatsAppApiMetricRepositoryInterface::class => \DI\factory(function(EntityManagerInterface $entityManager) {
        return new WhatsAppApiMetricRepository($entityManager);
    }),
    
    // Repository de l'historique des templates WhatsApp
    WhatsAppTemplateHistoryRepositoryInterface::class => \DI\factory(function(EntityManagerInterface $entityManager) {
        return new WhatsAppTemplateHistoryRepository($entityManager);
    }),
    
    // Service de monitoring WhatsApp
    WhatsAppMonitoringServiceInterface::class => \DI\create(WhatsAppMonitoringService::class)
        ->constructor(
            \DI\get(\App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateHistoryRepositoryInterface::class),
            \DI\get(WhatsAppMessageHistoryRepositoryInterface::class),
            \DI\get(WhatsAppApiMetricRepositoryInterface::class),
            \DI\get(LoggerInterface::class)
        ),
        
    // Alias pour faciliter l'injection
    'App\\GraphQL\\Controllers\\WebhookController' => \DI\get('App\\GraphQL\\Controllers\\WhatsApp\\WebhookController')
], $resilienceConfig);