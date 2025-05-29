<?php

use App\Services\WhatsApp\Bus\CommandBus;
use App\Services\WhatsApp\Bus\LoggingMiddleware;
use App\Services\WhatsApp\Handlers\BulkSendHandler;
use App\Services\WhatsApp\Events\EventDispatcher;
use Psr\Container\ContainerInterface;

/**
 * Configuration DI pour l'envoi en masse WhatsApp
 */
return [
    // CommandBus avec BulkSendHandler
    'whatsapp.command_bus.bulk' => \DI\factory(function(ContainerInterface $container) {
        $commandBus = new CommandBus($container->get(\Psr\Log\LoggerInterface::class));
        
        // Ajouter le middleware de logging
        $commandBus->addMiddleware(new LoggingMiddleware($container->get(\Psr\Log\LoggerInterface::class)));
        
        // Enregistrer le BulkSendHandler
        $bulkSendHandler = new BulkSendHandler(
            $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface::class),
            $container->get(EventDispatcher::class),
            $container->get(\Psr\Log\LoggerInterface::class)
        );
        
        $commandBus->registerHandler($bulkSendHandler);
        
        return $commandBus;
    }),
    
    // EventDispatcher pour les événements d'envoi en masse
    EventDispatcher::class => \DI\factory(function(ContainerInterface $container) {
        $logger = $container->get(\Psr\Log\LoggerInterface::class);
        $dispatcher = new EventDispatcher($logger);
        
        // Les listeners seront ajoutés via le service si nécessaire
        // Pour éviter les problèmes de types avec les closures
        
        return $dispatcher;
    }),
];