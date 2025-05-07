<?php
/**
 * Configuration d'injection de dépendances pour les services WhatsApp
 */

use App\Entities\WhatsApp\WhatsAppMessage;
use App\GraphQL\Controllers\WhatsApp\WebhookController;
use App\Repositories\Doctrine\WhatsApp\WhatsAppMessageRepository;
use App\Repositories\Interfaces\WhatsApp\WhatsAppMessageRepositoryInterface;
use App\Services\Interfaces\WhatsApp\WebhookVerificationServiceInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppMessageServiceInterface;
use App\Services\WhatsApp\WebhookVerificationService;
use App\Services\WhatsApp\WhatsAppApiClient;
use App\Services\WhatsApp\WhatsAppMessageService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

return [
    // Configuration
    'whatsapp.config' => function() {
        return require __DIR__ . '/../whatsapp.php';
    },
    
    // Repositories
    WhatsAppMessageRepositoryInterface::class => \DI\factory(function(EntityManagerInterface $entityManager) {
        return new WhatsAppMessageRepository($entityManager);
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
        return new WhatsAppApiClient(
            $config['access_token'],
            $config['phone_number_id'],
            $config['api_version'],
            $logger
        );
    }),
    
    WhatsAppMessageServiceInterface::class => \DI\create(WhatsAppMessageService::class)
        ->constructor(
            \DI\get(WhatsAppMessageRepositoryInterface::class),
            \DI\get(WhatsAppApiClientInterface::class),
            \DI\get(LoggerInterface::class)
        ),
    
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
        
    // Alias pour faciliter l'injection
    'App\\GraphQL\\Controllers\\WebhookController' => \DI\get('App\\GraphQL\\Controllers\\WhatsApp\\WebhookController')
];