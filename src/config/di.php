<?php

use App\Services\EventManager;
use App\Services\Interfaces\SubjectInterface;
use App\Services\Observers\SMSHistoryObserver;
use App\Repositories\SMSHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    // Doctrine EntityManager
    EntityManagerInterface::class => factory(function () {
        return require __DIR__ . '/../bootstrap-doctrine.php';
    }),

    // Alias for EntityManager with concrete implementation
    \Doctrine\ORM\EntityManager::class => factory(function (Container $container) {
        return $container->get(EntityManagerInterface::class);
    }),
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

    // Doctrine Repositories
    App\Repositories\Doctrine\SenderNameRepository::class => factory(function (Container $container) {
        return new App\Repositories\Doctrine\SenderNameRepository(
            $container->get(\Doctrine\ORM\EntityManager::class)
        );
    }),

    App\Repositories\Doctrine\SegmentRepository::class => factory(function (Container $container) {
        return new App\Repositories\Doctrine\SegmentRepository(
            $container->get(\Doctrine\ORM\EntityManager::class)
        );
    }),

    App\Repositories\Doctrine\CustomSegmentRepository::class => factory(function (Container $container) {
        return new App\Repositories\Doctrine\CustomSegmentRepository(
            $container->get(\Doctrine\ORM\EntityManager::class)
        );
    }),

    App\Repositories\Doctrine\PhoneNumberSegmentRepository::class => factory(function (Container $container) {
        return new App\Repositories\Doctrine\PhoneNumberSegmentRepository(
            $container->get(\Doctrine\ORM\EntityManager::class)
        );
    }),

    App\Repositories\Doctrine\OrangeAPIConfigRepository::class => factory(function (Container $container) {
        return new App\Repositories\Doctrine\OrangeAPIConfigRepository(
            $container->get(\Doctrine\ORM\EntityManager::class)
        );
    }),

    App\Repositories\Doctrine\PhoneNumberRepository::class => factory(function (Container $container) {
        return new App\Repositories\Doctrine\PhoneNumberRepository(
            $container->get(\Doctrine\ORM\EntityManager::class),
            $container->get(App\Repositories\Interfaces\SegmentRepositoryInterface::class),
            $container->get(App\Repositories\Interfaces\CustomSegmentRepositoryInterface::class)
        );
    }),

    App\Repositories\Doctrine\TechnicalSegmentRepository::class => factory(function (Container $container) {
        return new App\Repositories\Doctrine\TechnicalSegmentRepository(
            $container->get(\Doctrine\ORM\EntityManager::class)
        );
    }),

    // Repository interfaces to implementation mapping
    App\Repositories\Interfaces\SegmentRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\SegmentRepository::class);
    }),

    App\Repositories\Interfaces\CustomSegmentRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\CustomSegmentRepository::class);
    }),

    App\Repositories\Interfaces\PhoneNumberSegmentRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\PhoneNumberSegmentRepository::class);
    }),

    App\Repositories\Interfaces\OrangeAPIConfigRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\OrangeAPIConfigRepository::class);
    }),

    App\Repositories\Interfaces\PhoneNumberRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\PhoneNumberRepository::class);
    }),

    App\Repositories\Interfaces\TechnicalSegmentRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\TechnicalSegmentRepository::class);
    }),

    // Phase 2: Mise à jour du conteneur DI pour les repositories restants
    App\Repositories\Interfaces\UserRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\UserRepository::class);
    }),

    App\Repositories\Interfaces\ContactRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\ContactRepository::class);
    }),

    App\Repositories\Interfaces\ContactGroupRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\ContactGroupRepository::class);
    }),

    App\Repositories\Interfaces\ContactGroupMembershipRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\ContactGroupMembershipRepository::class);
    }),

    App\Repositories\Interfaces\SMSHistoryRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\SMSHistoryRepository::class);
    }),

    App\Repositories\Interfaces\SMSOrderRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\SMSOrderRepository::class);
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

    // Contact and Group Repositories
    \App\Repositories\ContactRepository::class => factory(function (Container $container) {
        return new \App\Repositories\ContactRepository($container->get(PDO::class));
    }),
    \App\Repositories\ContactGroupRepository::class => factory(function (Container $container) {
        return new \App\Repositories\ContactGroupRepository($container->get(PDO::class));
    }),
    \App\Repositories\ContactGroupMembershipRepository::class => factory(function (Container $container) {
        return new \App\Repositories\ContactGroupMembershipRepository($container->get(PDO::class));
    }),


    // Observateurs
    SMSHistoryObserver::class => factory(function (Container $container) {
        return new SMSHistoryObserver(
            $container->get(\App\Repositories\Interfaces\SMSHistoryRepositoryInterface::class)
        );
    }),

    // Services
    \App\Services\Interfaces\OrangeAPIClientInterface::class => factory(function () {
        // Read Orange API credentials and defaults from environment variables using $_ENV
        $clientId = $_ENV['ORANGE_API_CLIENT_ID'] ?? ''; // Provide default or handle error if missing
        $clientSecret = $_ENV['ORANGE_API_CLIENT_SECRET'] ?? '';
        $defaultSenderAddress = $_ENV['ORANGE_DEFAULT_SENDER_ADDRESS'] ?? '';
        $defaultSenderName = $_ENV['ORANGE_DEFAULT_SENDER_NAME'] ?? '';

        // Basic validation or logging for missing env vars could be added here
        if (empty($clientId) || empty($clientSecret)) {
            // Log an error or throw an exception if credentials are vital
            error_log('Missing Orange API credentials in environment variables.');
            // Depending on the application's needs, you might return a null object,
            // throw an exception, or allow proceeding with empty credentials if applicable.
        }

        return new \App\Services\OrangeAPIClient(
            $clientId,
            $clientSecret,
            $defaultSenderAddress,
            $defaultSenderName
        );
    }),

    // SMSService now injects OrangeAPIClientInterface and uses repository interfaces
    \App\Services\SMSService::class => factory(function (Container $container) {
        return new \App\Services\SMSService(
            $container->get(\App\Services\Interfaces\OrangeAPIClientInterface::class), // Inject the client
            $container->get(\App\Repositories\Interfaces\PhoneNumberRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\CustomSegmentRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\SMSHistoryRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\ContactRepositoryInterface::class)
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
            $container->get(\App\Services\Factories\SegmentationStrategyFactory::class),
            $container->get(\App\Repositories\Interfaces\SegmentRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\PhoneNumberRepositoryInterface::class)
        );
    }),

    \App\Services\Interfaces\BatchSegmentationServiceInterface::class => factory(function (Container $container) {
        return new \App\Services\BatchSegmentationService(
            $container->get(\App\Services\Interfaces\PhoneSegmentationServiceInterface::class),
            $container->get(\App\Repositories\Interfaces\PhoneNumberRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\TechnicalSegmentRepositoryInterface::class),
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
            $container->get(\App\Repositories\Interfaces\CustomSegmentRepositoryInterface::class),
            $container->get(\App\Services\Interfaces\RegexValidatorInterface::class)
        );
    }),

    \App\Services\Interfaces\SMSValidationServiceInterface::class => factory(function () {
        return new \App\Services\SMSValidationService();
    }),

    \App\Services\Interfaces\SMSHistoryServiceInterface::class => factory(function (Container $container) {
        return new \App\Services\SMSHistoryService(
            $container->get(\App\Repositories\Interfaces\SMSHistoryRepositoryInterface::class)
        );
    }),

    \App\Services\Interfaces\SMSBusinessServiceInterface::class => factory(function (Container $container) {
        return new \App\Services\SMSBusinessService(
            $container->get(\App\Services\Interfaces\SMSSenderServiceInterface::class),
            $container->get(\App\Services\Interfaces\SMSHistoryServiceInterface::class),
            $container->get(\App\Repositories\Interfaces\CustomSegmentRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\PhoneNumberRepositoryInterface::class)
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

    // Controllers
    \App\Controllers\PhoneController::class => factory(function (Container $container) {
        return new \App\Controllers\PhoneController($container->get(PDO::class));
    }),

    \App\Controllers\SMSController::class => factory(function (Container $container) {
        return new \App\Controllers\SMSController(
            $container->get(PDO::class),
            $container->get(\App\Services\SMSService::class),
            $container->get(\App\Repositories\Interfaces\PhoneNumberRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\CustomSegmentRepositoryInterface::class)
        );
    }),

    \App\Controllers\ImportExportController::class => factory(function (Container $container) {
        return new \App\Controllers\ImportExportController(
            $container->get(\App\Services\CSVImportService::class),
            $container->get(\App\Services\ExportService::class)
        );
    }),

    // Services for ImportExportController
    \App\Services\CSVImportService::class => factory(function (Container $container) {
        return new \App\Services\CSVImportService(
            $container->get(\App\Repositories\Interfaces\PhoneNumberRepositoryInterface::class),
            $container->get(\App\Services\Interfaces\PhoneSegmentationServiceInterface::class),
            $container->get(\App\Repositories\Interfaces\ContactRepositoryInterface::class)
        );
    }),

    \App\Services\ExportService::class => factory(function (Container $container) {
        return new \App\Services\ExportService(
            $container->get(\App\Repositories\Interfaces\PhoneNumberRepositoryInterface::class)
        );
    }),

    // Services d'authentification
    \App\Services\Interfaces\AuthServiceInterface::class => factory(function (Container $container) {
        return new \App\Services\AuthService(
            $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class),
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
            $container->get(\App\Services\SMSService::class),
            $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class)
        );
    }),

    \App\Services\Interfaces\RealtimeNotificationServiceInterface::class => factory(function (Container $container) {
        return new \App\Services\RealtimeNotificationService(
            $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class),
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
            $container->get(PDO::class),
            $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class)
        );
    }),

    // New services for sender names and Orange API configurations
    \App\Services\SenderNameService::class => factory(function (Container $container) {
        return new \App\Services\SenderNameService(
            $container->get(\App\Repositories\Doctrine\SenderNameRepository::class)
        );
    }),

    \App\Services\OrangeAPIConfigService::class => factory(function (Container $container) {
        return new \App\Services\OrangeAPIConfigService(
            $container->get(\App\Repositories\Doctrine\OrangeAPIConfigRepository::class)
        );
    }),

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
            $container->get(\App\Services\SMSService::class),
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
];

// Return the definitions array instead of the built container
// This allows other components to use these definitions
return $definitions;
