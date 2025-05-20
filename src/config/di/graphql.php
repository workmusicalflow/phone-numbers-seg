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
            $container->get(Psr\Log\LoggerInterface::class),
            $container->get(\App\GraphQL\DataLoaders\ContactGroupDataLoader::class),
            $container->get(\App\Repositories\Interfaces\SMSHistoryRepositoryInterface::class)
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
    
    \App\GraphQL\Resolvers\ContactSMSResolver::class => factory(function (Container $container) {
        return new \App\GraphQL\Resolvers\ContactSMSResolver(
            $container->get(\App\Repositories\Interfaces\SMSHistoryRepositoryInterface::class),
            $container->get(Psr\Log\LoggerInterface::class),
            $container->get(\App\Services\Interfaces\PhoneNumberNormalizerInterface::class)
        );
    }),

    // GraphQL Context Factory
    \App\GraphQL\Context\GraphQLContextFactory::class => factory(function (Container $container) {
        return new \App\GraphQL\Context\GraphQLContextFactory(
            $container,
            $container->get(\App\Services\Interfaces\AuthServiceInterface::class)
        );
    }),

    // GraphQL Types
    \App\GraphQL\Types\WhatsApp\WhatsAppMessageHistoryType::class => factory(function (Container $container) {
        return new \App\GraphQL\Types\WhatsApp\WhatsAppMessageHistoryType();
    }),
    
    \App\GraphQL\Types\WhatsApp\SendTemplateResult::class => factory(function (Container $container) {
        return new \App\GraphQL\Types\WhatsApp\SendTemplateResult(false, null, null);
    }),
    
    \App\GraphQL\Types\WhatsApp\SendTemplateInput::class => factory(function (Container $container) {
        // Factory pour le type d'entrée
        return null; // Les types d'entrée n'ont pas besoin d'être instanciés
    }),
    
    // GraphQL Controllers (If used directly, otherwise resolvers handle logic)
    \App\GraphQL\Controllers\WhatsApp\WhatsAppTemplateController::class => factory(function (Container $container) {
        return new \App\GraphQL\Controllers\WhatsApp\WhatsAppTemplateController(
            $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface::class),
            $container->get(Psr\Log\LoggerInterface::class)
        );
    }),
    // Add other GraphQL controllers if necessary
];
