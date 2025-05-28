<?php

use App\Services\WhatsApp\WhatsAppServiceWithCommands;
use App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface;
use Psr\Container\ContainerInterface;

/**
 * Configuration DI pour la version avec Command et Observer patterns
 * 
 * Pour activer cette version :
 * 1. Inclure ce fichier dans la configuration DI principale
 * 2. Il remplacera WhatsAppService par WhatsAppServiceWithCommands
 */
return [
    // Remplacer WhatsAppService par la version avec Commands
    WhatsAppServiceInterface::class => \DI\factory(function(ContainerInterface $container) {
        $service = new WhatsAppServiceWithCommands(
            $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface::class),
            $container->get(\App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface::class),
            $container->get(\Psr\Log\LoggerInterface::class),
            $container->get('whatsapp.config'),
            $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface::class),
            $container->get(\App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateHistoryRepositoryInterface::class)
        );
        
        // Configurer les listeners si les services sont disponibles
        if ($container->has(\App\Repositories\Interfaces\UserRepositoryInterface::class) &&
            $container->has(\App\Services\Interfaces\NotificationServiceInterface::class)) {
            $service->configureEventListeners(
                $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class),
                $container->get(\App\Services\Interfaces\NotificationServiceInterface::class)
            );
        }
        
        return $service;
    }),
    
    // Alias pour la compatibilité
    \App\Services\WhatsApp\WhatsAppService::class => \DI\get(WhatsAppServiceInterface::class),
    \App\Services\WhatsApp\WhatsAppServiceRefactored::class => \DI\get(WhatsAppServiceInterface::class),
];