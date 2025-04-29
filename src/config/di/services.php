<?php

use DI\Container;
use function DI\factory;
use App\Services\EventManager;
use App\Services\Interfaces\SubjectInterface;
use App\Services\Observers\SMSHistoryObserver;

/**
 * Service definitions for Dependency Injection Container
 */
return [
    // Core Services
    // Token Cache Service
    \App\Services\Interfaces\TokenCacheInterface::class => factory(function (Container $container) {
        $cacheDir = $_ENV['CACHE_DIR'] ?? sys_get_temp_dir();
        $tokenLifetime = isset($_ENV['ORANGE_API_TOKEN_LIFETIME']) ? (int)$_ENV['ORANGE_API_TOKEN_LIFETIME'] : 3600;
        
        return new \App\Services\TokenCacheService(
            $cacheDir,
            $tokenLifetime,
            $container->get(Psr\Log\LoggerInterface::class)
        );
    }),
    
    // SMS Queue Service for asynchronous SMS processing
    \App\Services\Interfaces\SMSQueueServiceInterface::class => factory(function (Container $container) {
        $maxAttempts = isset($_ENV['SMS_QUEUE_MAX_ATTEMPTS']) ? (int)$_ENV['SMS_QUEUE_MAX_ATTEMPTS'] : 5;
        
        return new \App\Services\SMSQueueService(
            $container->get(\App\Repositories\Interfaces\SMSQueueRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\PhoneNumberRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\ContactRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\SegmentRepositoryInterface::class),
            $container->get(\App\Services\Interfaces\OrangeAPIClientInterface::class),
            $container->get(\App\Services\Interfaces\AuthServiceInterface::class),
            $container->get(Psr\Log\LoggerInterface::class),
            $maxAttempts
        );
    }),
    
    // OrangeAPI Client with Token Cache
    \App\Services\Interfaces\OrangeAPIClientInterface::class => factory(function (Container $container) {
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

        // Create the Token Cache service
        $tokenCache = $container->get(\App\Services\Interfaces\TokenCacheInterface::class);
        
        return new \App\Services\OrangeAPIClient(
            $clientId,
            $clientSecret,
            $defaultSenderAddress,
            $defaultSenderName,
            $tokenCache,
            $container->get(Psr\Log\LoggerInterface::class)
        );
    }),

    // SMSService now injects OrangeAPIClientInterface, repository interfaces, and LoggerInterface
    \App\Services\SMSService::class => factory(function (Container $container) {
        return new \App\Services\SMSService(
            $container->get(\App\Services\Interfaces\OrangeAPIClientInterface::class), // Inject the client
            $container->get(\App\Repositories\Interfaces\PhoneNumberRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\CustomSegmentRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\SMSHistoryRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\ContactRepositoryInterface::class),
            $container->get(\App\Services\Interfaces\SMSQueueServiceInterface::class), // Inject SMSQueueService
            $container->get(Psr\Log\LoggerInterface::class) // Inject Logger
        );
    }),

    // Services qui utilisent l'EventManager
    \App\Services\SMSSenderService::class => factory(function (Container $container) {
        $service = new \App\Services\SMSSenderService(
            $container->get(\App\Services\Interfaces\OrangeAPIClientInterface::class),
            $container->get(SubjectInterface::class)
        );

        // Configuration des observateurs (Moved observer attachment logic here from original file)
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

    \App\Services\PhoneSegmentationService::class => factory(function (Container $container) {
        return new \App\Services\PhoneSegmentationService(
            $container->get(\App\Services\Interfaces\PhoneNumberValidatorInterface::class),
            $container->get(\App\Services\Factories\SegmentationStrategyFactory::class),
            $container->get(\App\Repositories\Interfaces\SegmentRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\PhoneNumberRepositoryInterface::class)
        );
    }),

    \App\Services\BatchSegmentationService::class => factory(function (Container $container) {
        return new \App\Services\BatchSegmentationService(
            $container->get(\App\Services\Interfaces\PhoneSegmentationServiceInterface::class),
            $container->get(\App\Repositories\Interfaces\PhoneNumberRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\TechnicalSegmentRepositoryInterface::class),
            $container->get(\App\Services\Interfaces\PhoneNumberValidatorInterface::class), // Inject Validator
            $container->get(\App\Services\Formatters\BatchResultFormatterInterface::class),
            $container->get(Psr\Log\LoggerInterface::class) // Inject Logger
        );
    }),

    \App\Services\CustomSegmentMatcher::class => factory(function (Container $container) {
        return new \App\Services\CustomSegmentMatcher(
            $container->get(\App\Repositories\Interfaces\CustomSegmentRepositoryInterface::class),
            $container->get(\App\Services\Interfaces\RegexValidatorInterface::class)
        );
    }),

    \App\Services\SMSValidationService::class => factory(function () {
        return new \App\Services\SMSValidationService();
    }),

    \App\Services\SMSHistoryService::class => factory(function (Container $container) {
        return new \App\Services\SMSHistoryService(
            $container->get(\App\Repositories\Interfaces\SMSHistoryRepositoryInterface::class)
        );
    }),

    \App\Services\SMSBusinessService::class => factory(function (Container $container) {
        return new \App\Services\SMSBusinessService(
            $container->get(\App\Services\Interfaces\SMSSenderServiceInterface::class),
            $container->get(\App\Services\Interfaces\SMSHistoryServiceInterface::class),
            $container->get(\App\Repositories\Interfaces\CustomSegmentRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\PhoneNumberRepositoryInterface::class)
        );
    }),

    // Email Service
    \App\Services\EmailService::class => factory(function () {
        return new \App\Services\EmailService();
    }),

    // Services for ImportExportController
    \App\Services\CSVImportService::class => factory(function (Container $container) {
        return new \App\Services\CSVImportService(
            $container->get(\App\Repositories\Interfaces\PhoneNumberRepositoryInterface::class),
            $container->get(\App\Services\Interfaces\PhoneSegmentationServiceInterface::class),
            $container->get(\App\Repositories\Interfaces\ContactRepositoryInterface::class),
            $container->get(Psr\Log\LoggerInterface::class) // Inject Logger
        );
    }),

    \App\Services\ExportService::class => factory(function (Container $container) {
        return new \App\Services\ExportService(
            $container->get(\App\Repositories\Interfaces\PhoneNumberRepositoryInterface::class)
        );
    }),

    // Services d'authentification
    \App\Services\AuthService::class => factory(function (Container $container) {
        return new \App\Services\AuthService(
            $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class),
            $container->get(\App\Services\Interfaces\EmailServiceInterface::class),
            $container->get(Psr\Log\LoggerInterface::class) // Inject Logger
        );
    }),

    // Services de notification et de journalisation
    \App\Services\NotificationService::class => factory(function (Container $container) {
        return new \App\Services\NotificationService(
            $container->get(\App\Services\Interfaces\EmailServiceInterface::class),
            $container->get(\App\Services\SMSService::class), // Assuming SMSService provides SMS sending capability needed here
            $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class)
        );
    }),

    \App\Services\RealtimeNotificationService::class => factory(function (Container $container) {
        return new \App\Services\RealtimeNotificationService(
            $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class),
            $container->get(Psr\Log\LoggerInterface::class)
        );
    }),

    \App\Services\ErrorLoggerService::class => factory(function (Container $container) {
        return new \App\Services\ErrorLoggerService(
            $container->get(Psr\Log\LoggerInterface::class),
            $container->get(\App\Services\Interfaces\NotificationServiceInterface::class)
        );
    }),

    // Service de journalisation des actions administrateur
    \App\Services\AdminActionLogger::class => factory(function (Container $container) {
        return new \App\Services\AdminActionLogger(
            $container->get(\App\Repositories\Interfaces\AdminActionLogRepositoryInterface::class),
            $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class), // Keep UserRepository if still needed in the service logic
            $container->get(Psr\Log\LoggerInterface::class) // Inject LoggerInterface
        );
    }),

    // New services for sender names and Orange API configurations
    \App\Services\SenderNameService::class => factory(function (Container $container) {
        return new \App\Services\SenderNameService(
            $container->get(\App\Repositories\Doctrine\SenderNameRepository::class) // Assuming Doctrine repo is the target
        );
    }),

    \App\Services\OrangeAPIConfigService::class => factory(function (Container $container) {
        return new \App\Services\OrangeAPIConfigService(
            $container->get(\App\Repositories\Interfaces\OrangeAPIConfigRepositoryInterface::class) // Use interface
        );
    }),

    // Formatters (Considered services for this structure)
    \App\Services\Formatters\BatchResultFormatter::class => factory(function () {
        return new \App\Services\Formatters\BatchResultFormatter();
    }),
];
