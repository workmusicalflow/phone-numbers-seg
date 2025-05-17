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
use App\Services\WhatsApp\WebhookVerificationService;
use App\Services\WhatsApp\WhatsAppApiClient;
use App\Services\WhatsApp\WhatsAppMessageService;
use App\Services\WhatsApp\WhatsAppService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

return [
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
        return new WhatsAppService(
            $container->get(WhatsAppApiClientInterface::class),
            $container->get(WhatsAppMessageHistoryRepositoryInterface::class),
            $container->get(WhatsAppTemplateRepositoryInterface::class),
            $container->get(LoggerInterface::class),
            $container->get('whatsapp.config')
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
    
    // Controllers
    'App\\GraphQL\\Controllers\\WhatsApp\\WebhookController' => \DI\create('App\\GraphQL\\Controllers\\WhatsApp\\WebhookController')
        ->constructor(
            \DI\get(WebhookVerificationServiceInterface::class),
            \DI\get(WhatsAppMessageServiceInterface::class),
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
            $container->get(WhatsAppMessageHistoryRepositoryInterface::class)
        );
    }),
        
    // Alias pour faciliter l'injection
    'App\\GraphQL\\Controllers\\WebhookController' => \DI\get('App\\GraphQL\\Controllers\\WhatsApp\\WebhookController')
];