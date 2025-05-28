<?php

use App\Services\WhatsApp\WhatsAppServiceRefactored;
use App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface;
use Psr\Container\ContainerInterface;

/**
 * Configuration DI pour la version refactorisée de WhatsAppService
 * 
 * Pour activer la version refactorisée :
 * 1. Inclure ce fichier dans la configuration DI principale
 * 2. Il remplacera automatiquement WhatsAppService par WhatsAppServiceRefactored
 */
return [
    // Remplacer WhatsAppService par la version refactorisée
    WhatsAppServiceInterface::class => \DI\factory(function(ContainerInterface $container) {
        return new WhatsAppServiceRefactored(
            $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface::class),
            $container->get(\App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface::class),
            $container->get(\Psr\Log\LoggerInterface::class),
            $container->get('whatsapp.config'),
            $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface::class),
            $container->get(\App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateHistoryRepositoryInterface::class)
        );
    }),
    
    // Alias pour la compatibilité
    \App\Services\WhatsApp\WhatsAppService::class => \DI\get(WhatsAppServiceInterface::class),
];