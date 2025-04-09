<?php

use App\Services\EventManager;
use App\Services\Interfaces\SubjectInterface;
use App\Services\Observers\SMSHistoryObserver;
use App\Repositories\SMSHistoryRepository;
use DI\Container;
use DI\ContainerBuilder;
use function DI\factory;
use function DI\get;

/**
 * Configuration du conteneur d'injection de dépendances
 * 
 * Ce fichier configure le conteneur d'injection de dépendances pour l'application.
 * Il enregistre les services, repositories et autres dépendances nécessaires.
 */

// Créer le builder de conteneur
$containerBuilder = new ContainerBuilder();

// Définir les définitions
$definitions = [
    // PDO instance for database access
    PDO::class => factory(function () {
        $dbConfig = require __DIR__ . '/database.php';

        // Force SQLite as the database driver due to MySQL service error
        $dbConfig['driver'] = 'sqlite';

        $dsn = 'sqlite:' . $dbConfig['sqlite']['path'];

        $pdo = new PDO($dsn, null, null);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }),
    // Services
    SubjectInterface::class => factory(function () {
        return new EventManager();
    }),

    // Repositories
    SMSHistoryRepository::class => factory(function (Container $container) {
        return new SMSHistoryRepository($container->get(PDO::class));
    }),

    // Add other repositories
    \App\Repositories\PhoneNumberRepository::class => factory(function (Container $container) {
        return new \App\Repositories\PhoneNumberRepository($container->get(PDO::class));
    }),

    \App\Repositories\SegmentRepository::class => factory(function (Container $container) {
        return new \App\Repositories\SegmentRepository($container->get(PDO::class));
    }),

    \App\Repositories\CustomSegmentRepository::class => factory(function (Container $container) {
        return new \App\Repositories\CustomSegmentRepository($container->get(PDO::class));
    }),

    \App\Repositories\UserRepository::class => factory(function (Container $container) {
        return new \App\Repositories\UserRepository($container->get(PDO::class));
    }),

    \App\Repositories\SenderNameRepository::class => factory(function (Container $container) {
        return new \App\Repositories\SenderNameRepository($container->get(PDO::class));
    }),

    \App\Repositories\SMSOrderRepository::class => factory(function (Container $container) {
        return new \App\Repositories\SMSOrderRepository($container->get(PDO::class));
    }),

    \App\Repositories\OrangeAPIConfigRepository::class => factory(function (Container $container) {
        return new \App\Repositories\OrangeAPIConfigRepository($container->get(PDO::class));
    }),

    \App\Repositories\AdminContactRepository::class => factory(function (Container $container) {
        return new \App\Repositories\AdminContactRepository($container->get(PDO::class));
    }),

    \App\Repositories\TechnicalSegmentRepository::class => factory(function (Container $container) {
        return new \App\Repositories\TechnicalSegmentRepository($container->get(PDO::class));
    }),

    // Observateurs
    SMSHistoryObserver::class => factory(function (Container $container) {
        return new SMSHistoryObserver(
            $container->get(SMSHistoryRepository::class)
        );
    }),

    // Services
    \App\Services\Interfaces\OrangeAPIClientInterface::class => factory(function () {
        return new \App\Services\OrangeAPIClient(
            'DGxbQKd9JHXLdFaWGtv0FfqFFI7Gu03a',          // client ID
            'S4ywfdZUjNvOXErMr5NyQwgliBCdXIAYp1DcibKThBXs',      // client secret
            'tel:+2250595016840',      // numéro d'expéditeur
            'Qualitas CI'              // Nom d'expéditeur par défaut
        );
    }),

    // SMSService with required parameters
    \App\Services\SMSService::class => factory(function (Container $container) {
        return new \App\Services\SMSService(
            'DGxbQKd9JHXLdFaWGtv0FfqFFI7Gu03a',          // client ID
            'S4ywfdZUjNvOXErMr5NyQwgliBCdXIAYp1DcibKThBXs',      // client secret
            'tel:+2250595016840',      // numéro d'expéditeur
            'Qualitas CI',             // Nom d'expéditeur par défaut
            $container->get(\App\Repositories\PhoneNumberRepository::class),
            $container->get(\App\Repositories\CustomSegmentRepository::class),
            $container->get(\App\Repositories\SMSHistoryRepository::class)
        );
    }),

    \App\Services\Interfaces\SMSSenderServiceInterface::class => factory(function (Container $container) {
        return $container->get(\App\Services\SMSSenderService::class);
    }),

    // Factory for segmentation strategies
    \App\Services\Factories\SegmentationStrategyFactory::class => factory(function () {
        return new \App\Services\Factories\SegmentationStrategyFactory();
    }),

    \App\Services\Interfaces\PhoneSegmentationServiceInterface::class => factory(function (Container $container) {
        return new \App\Services\PhoneSegmentationService(
            $container->get(\App\Services\Interfaces\PhoneNumberValidatorInterface::class),
            $container->get(\App\Services\Factories\SegmentationStrategyFactory::class)
        );
    }),

    \App\Services\Interfaces\BatchSegmentationServiceInterface::class => factory(function (Container $container) {
        return new \App\Services\BatchSegmentationService(
            $container->get(\App\Services\Interfaces\PhoneSegmentationServiceInterface::class),
            $container->get(\App\Repositories\PhoneNumberRepository::class),
            $container->get(\App\Repositories\TechnicalSegmentRepository::class),
            $container->get(\App\Services\Formatters\BatchResultFormatterInterface::class)
        );
    }),

    \App\Services\Formatters\BatchResultFormatterInterface::class => factory(function () {
        return new \App\Services\Formatters\BatchResultFormatter();
    }),

    \App\Services\Interfaces\PhoneNumberValidatorInterface::class => factory(function () {
        return new \App\Services\PhoneNumberValidator();
    }),

    \App\Services\Interfaces\RegexValidatorInterface::class => factory(function () {
        return new \App\Services\RegexValidator();
    }),

    \App\Services\Interfaces\CustomSegmentMatcherInterface::class => factory(function (Container $container) {
        return new \App\Services\CustomSegmentMatcher(
            $container->get(\App\Repositories\CustomSegmentRepository::class),
            $container->get(\App\Services\Interfaces\RegexValidatorInterface::class)
        );
    }),

    \App\Services\Interfaces\SMSValidationServiceInterface::class => factory(function () {
        return new \App\Services\SMSValidationService();
    }),

    \App\Services\Interfaces\SMSHistoryServiceInterface::class => factory(function (Container $container) {
        return new \App\Services\SMSHistoryService(
            $container->get(\App\Repositories\SMSHistoryRepository::class)
        );
    }),

    \App\Services\Interfaces\SMSBusinessServiceInterface::class => factory(function (Container $container) {
        return new \App\Services\SMSBusinessService(
            $container->get(\App\Services\Interfaces\SMSSenderServiceInterface::class),
            $container->get(\App\Services\Interfaces\SMSHistoryServiceInterface::class),
            $container->get(\App\Repositories\CustomSegmentRepository::class),
            $container->get(\App\Repositories\PhoneNumberRepository::class)
        );
    }),

    // Services qui utilisent l'EventManager
    \App\Services\SMSSenderService::class => factory(function (Container $container) {
        $service = new \App\Services\SMSSenderService(
            $container->get(\App\Services\Interfaces\OrangeAPIClientInterface::class),
            $container->get(SubjectInterface::class)
        );

        // Configuration des observateurs
        $eventManager = $container->get(SubjectInterface::class);
        $eventManager->attachForEvent(
            $container->get(SMSHistoryObserver::class),
            'sms.sent'
        );
        $eventManager->attachForEvent(
            $container->get(SMSHistoryObserver::class),
            'sms.failed'
        );

        return $service;
    }),

    // Logger
    Psr\Log\LoggerInterface::class => factory(function () {
        return new \App\Services\SimpleLogger('app');
    }),

    // Email Service
    \App\Services\Interfaces\EmailServiceInterface::class => factory(function () {
        return new \App\Services\EmailService();
    }),

    // Services d'authentification
    \App\Services\Interfaces\AuthServiceInterface::class => factory(function (Container $container) {
        return new \App\Services\AuthService(
            $container->get(\App\Repositories\UserRepository::class),
            $container->get(\App\Services\Interfaces\EmailServiceInterface::class)
        );
    }),

    // Middleware d'authentification par session
    \App\Middleware\SessionAuthMiddleware::class => factory(function () {
        return new \App\Middleware\SessionAuthMiddleware();
    }),

    // Services de notification et de journalisation
    \App\Services\Interfaces\NotificationServiceInterface::class => factory(function (Container $container) {
        return new \App\Services\NotificationService(
            $container->get(\App\Services\Interfaces\EmailServiceInterface::class),
            $container->get(\App\Services\SMSService::class)
        );
    }),

    \App\Services\Interfaces\RealtimeNotificationServiceInterface::class => factory(function (Container $container) {
        return new \App\Services\RealtimeNotificationService(
            $container->get(\App\Repositories\UserRepository::class),
            $container->get(Psr\Log\LoggerInterface::class)
        );
    }),

    \App\Services\Interfaces\ErrorLoggerServiceInterface::class => factory(function (Container $container) {
        return new \App\Services\ErrorLoggerService(
            $container->get(Psr\Log\LoggerInterface::class),
            $container->get(\App\Services\Interfaces\NotificationServiceInterface::class)
        );
    }),

    // Service de journalisation des actions administrateur
    \App\Services\Interfaces\AdminActionLoggerInterface::class => factory(function (Container $container) {
        return new \App\Services\AdminActionLogger(
            $container->get(PDO::class)
        );
    }),
];

// Return the definitions array instead of the built container
// This allows other components to use these definitions
return $definitions;
