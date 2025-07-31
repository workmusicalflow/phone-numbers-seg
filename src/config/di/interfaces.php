<?php

use DI\Container;
use function DI\factory;

/**
 * Interface to implementation mappings for Dependency Injection Container
 */
return [
    // Service Interfaces
    \App\Services\Interfaces\SubjectInterface::class => factory(function () {
        return new \App\Services\EventManager(); // EventManager implements SubjectInterface
    }),
    \App\Services\Interfaces\SMSSenderServiceInterface::class => factory(function (Container $container) {
        return $container->get(\App\Services\SMSSenderService::class);
    }),
    \App\Services\Interfaces\PhoneSegmentationServiceInterface::class => factory(function (Container $container) {
        return $container->get(\App\Services\PhoneSegmentationService::class);
    }),
    \App\Services\Interfaces\BatchSegmentationServiceInterface::class => factory(function (Container $container) {
        return $container->get(\App\Services\BatchSegmentationService::class);
    }),
    \App\Services\Formatters\BatchResultFormatterInterface::class => factory(function () {
        return new \App\Services\Formatters\BatchResultFormatter();
    }),
    \App\Services\Interfaces\CustomSegmentMatcherInterface::class => factory(function (Container $container) {
        return $container->get(\App\Services\CustomSegmentMatcher::class);
    }),
    \App\Services\Interfaces\SMSHistoryServiceInterface::class => factory(function (Container $container) {
        return $container->get(\App\Services\SMSHistoryService::class);
    }),
    \App\Services\Interfaces\SMSBusinessServiceInterface::class => factory(function (Container $container) {
        return $container->get(\App\Services\SMSBusinessService::class);
    }),
    \App\Services\Interfaces\EmailServiceInterface::class => factory(function () {
        return new \App\Services\EmailService();
    }),
    \App\Services\Interfaces\AuthServiceInterface::class => factory(function (Container $container) {
        return $container->get(\App\Services\AuthService::class);
    }),
    \App\Services\Interfaces\NotificationServiceInterface::class => factory(function (Container $container) {
        return $container->get(\App\Services\NotificationService::class);
    }),
    \App\Services\Interfaces\RealtimeNotificationServiceInterface::class => factory(function (Container $container) {
        return $container->get(\App\Services\RealtimeNotificationService::class);
    }),
    \App\Services\Interfaces\ErrorLoggerServiceInterface::class => factory(function (Container $container) {
        return $container->get(\App\Services\ErrorLoggerService::class);
    }),
    \App\Services\Interfaces\AdminActionLoggerInterface::class => factory(function (Container $container) {
        return $container->get(\App\Services\AdminActionLogger::class);
    }),

    // Logger Interface
    Psr\Log\LoggerInterface::class => factory(function () {
        return new \App\Services\SimpleLogger('app');
    }),

    // Repository interfaces are mapped in repositories.php
    // Validator interfaces are mapped in validators.php
    // GraphQL Formatter interface is mapped in graphql.php
];
