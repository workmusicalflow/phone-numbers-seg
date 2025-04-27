<?php

use DI\Container;
use function DI\factory;

/**
 * GraphQL related definitions for Dependency Injection Container
 */
return [
    // GraphQL Formatters
    \App\GraphQL\Formatters\GraphQLFormatterInterface::class => factory(function (Container $container) {
        return new \App\GraphQL\Formatters\GraphQLFormatterService(
            $container->get(\App\Repositories\Interfaces\CustomSegmentRepositoryInterface::class),
            $container->get(Psr\Log\LoggerInterface::class),
            $container->get(\App\Services\SenderNameService::class),
            $container->get(\App\Services\OrangeAPIConfigService::class)
        );
    }),

    // GraphQL Resolvers
    \App\GraphQL\Resolvers\UserResolver::class => factory(function (Container $container) {
        return new \App\GraphQL\Resolvers\UserResolver(
            $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class),
            $container->get(\App\Services\Interfaces\AuthServiceInterface::class),
            $container->get(\App\GraphQL\Formatters\GraphQLFormatterInterface::class),
            $container->get(Psr\Log\LoggerInterface::class)
        );
    }),

    \App\GraphQL\Resolvers\ContactResolver::class => factory(function (Container $container) {
        return new \App\GraphQL\Resolvers\ContactResolver(
            $container->get(\App\Repositories\Interfaces\ContactRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\ContactGroupRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\ContactGroupMembershipRepositoryInterface::class),
            $container->get(\App\Services\Interfaces\AuthServiceInterface::class),
            $container->get(\App\GraphQL\Formatters\GraphQLFormatterInterface::class),
            $container->get(Psr\Log\LoggerInterface::class)
        );
    }),

    \App\GraphQL\Resolvers\ContactGroupResolver::class => factory(function (Container $container) {
        return new \App\GraphQL\Resolvers\ContactGroupResolver(
            $container->get(\App\Repositories\Interfaces\ContactGroupRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\ContactGroupMembershipRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\ContactRepositoryInterface::class),
            $container->get(\App\Services\Interfaces\AuthServiceInterface::class),
            $container->get(\App\GraphQL\Formatters\GraphQLFormatterInterface::class),
            $container->get(Psr\Log\LoggerInterface::class)
        );
    }),

    \App\GraphQL\Resolvers\SMSResolver::class => factory(function (Container $container) {
        return new \App\GraphQL\Resolvers\SMSResolver(
            $container->get(\App\Repositories\Interfaces\SMSHistoryRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\CustomSegmentRepositoryInterface::class),
            $container->get(\App\Services\SMSService::class), // Use concrete SMSService here
            $container->get(\App\Services\Interfaces\AuthServiceInterface::class),
            $container->get(\App\GraphQL\Formatters\GraphQLFormatterInterface::class),
            $container->get(Psr\Log\LoggerInterface::class)
        );
    }),

    \App\GraphQL\Resolvers\AuthResolver::class => factory(function (Container $container) {
        return new \App\GraphQL\Resolvers\AuthResolver(
            $container->get(\App\Services\Interfaces\AuthServiceInterface::class),
            $container->get(\App\GraphQL\Formatters\GraphQLFormatterInterface::class),
            $container->get(Psr\Log\LoggerInterface::class)
        );
    }),

    // GraphQL Controllers (If used directly, otherwise resolvers handle logic)
    // Example: (Adjust based on actual usage)
    // \App\GraphQL\Controllers\AdminContactController::class => factory(function (Container $container) {
    //     return new \App\GraphQL\Controllers\AdminContactController(
    //         $container->get(\App\Repositories\Interfaces\AdminContactRepositoryInterface::class),
    //         $container->get(\App\Repositories\Interfaces\CustomSegmentRepositoryInterface::class),
    //         $container->get(\App\Services\Validators\AdminContactValidator::class) // Assuming validator is needed
    //     );
    // }),
    // Add other GraphQL controllers if necessary
];
