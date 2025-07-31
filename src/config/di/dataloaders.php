<?php

use DI\Container;
use function DI\factory;

/**
 * DataLoader definitions for Dependency Injection Container
 */
return [
    // ContactGroupDataLoader
    \App\GraphQL\DataLoaders\ContactGroupDataLoader::class => factory(function (Container $container) {
        return new \App\GraphQL\DataLoaders\ContactGroupDataLoader(
            $container->get(\App\Repositories\Interfaces\ContactGroupMembershipRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\ContactGroupRepositoryInterface::class),
            $container->get(\App\GraphQL\Formatters\GraphQLFormatterInterface::class),
            $container->get(Psr\Log\LoggerInterface::class)
        );
    }),
    
    // SMSHistoryDataLoader
    \App\GraphQL\DataLoaders\SMSHistoryDataLoader::class => factory(function (Container $container) {
        return new \App\GraphQL\DataLoaders\SMSHistoryDataLoader(
            $container->get(\App\Repositories\Interfaces\SMSHistoryRepositoryInterface::class),
            $container->get(\App\GraphQL\Formatters\GraphQLFormatterInterface::class),
            $container->get(Psr\Log\LoggerInterface::class)
        );
    }),
];