<?php

namespace App\GraphQL;

use TheCodingMachine\GraphQLite\SchemaFactory;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use App\GraphQL\DIContainer;
use App\Repositories\PhoneNumberRepository;
use App\Repositories\SegmentRepository;
use App\Repositories\TechnicalSegmentRepository;
use App\Repositories\CustomSegmentRepository;
use App\Repositories\SMSHistoryRepository;
use App\Repositories\UserRepository;
use App\Repositories\SenderNameRepository;
use App\Repositories\SMSOrderRepository;
use App\Repositories\OrangeAPIConfigRepository;
use App\Repositories\AdminContactRepository;
use App\Services\BatchSegmentationService;
use App\Services\CSVImportService;
use App\Services\ExportService;
use App\Services\Factories\SegmentationStrategyFactory;
use App\Services\Formatters\BatchResultFormatter;
use App\Services\Formatters\BatchResultFormatterInterface;
use App\Services\Interfaces\BatchSegmentationServiceInterface;
use App\Services\Interfaces\OrangeAPIClientInterface;
use App\Services\Interfaces\PhoneNumberValidatorInterface;
use App\Services\Interfaces\PhoneSegmentationServiceInterface;
use App\Services\Interfaces\SMSBusinessServiceInterface;
use App\Services\Interfaces\SMSHistoryServiceInterface;
use App\Services\Interfaces\SMSSenderServiceInterface;
use App\Services\Interfaces\SMSValidationServiceInterface;
use App\Services\OrangeAPIClient;
use App\Services\PhoneNumberValidator;
use App\Services\PhoneSegmentationService;
use App\Services\SMSBusinessService;
use App\Services\SMSHistoryService;
use App\Services\SMSService;
use App\Services\SMSSenderService;
use App\Services\SMSValidationService;
use TheCodingMachine\GraphQLite\Security\AuthenticationServiceInterface;
use TheCodingMachine\GraphQLite\Security\AuthorizationServiceInterface;

/**
 * Configuration class for GraphQLite
 */
class GraphQLiteConfiguration
{
    /**
     * Create and configure the GraphQL schema
     *
     * @return \GraphQL\Type\Schema
     */
    public static function createSchema(): \GraphQL\Type\Schema
    {
        // Create a PSR-16 compatible cache
        $cache = new Psr16Cache(new ArrayAdapter());

        // Create a DI container with PHP-DI
        $container = new DIContainer();

        // All services are now configured in src/config/di.php
        // and automatically loaded by the DIContainer

        // Create a schema factory with cache and container
        $schemaFactory = new SchemaFactory($cache, $container);

        // Configure the schema factory
        $schemaFactory->addNamespace('App\\GraphQL\\Controllers');
        $schemaFactory->addNamespace('App\\GraphQL\\Types');
        $schemaFactory->addNamespace('App\\Models');

        // Add authentication middleware
        $schemaFactory->setAuthenticationService(new class implements AuthenticationServiceInterface {
            public function isLogged(): bool
            {
                // Démarrer la session si elle n'est pas déjà démarrée
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }

                return isset($_SESSION['user_id']);
            }

            public function getUserId()
            {
                // Démarrer la session si elle n'est pas déjà démarrée
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }

                return $_SESSION['user_id'] ?? null;
            }
        });

        // Add authorization middleware
        $schemaFactory->setAuthorizationService(new class implements AuthorizationServiceInterface {
            public function isAllowed(string $right, mixed $subject = null): bool
            {
                // Démarrer la session si elle n'est pas déjà démarrée
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }

                // Vérifier si l'utilisateur est administrateur
                if ($right === 'ADMIN') {
                    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
                }

                // Par défaut, autoriser l'accès aux utilisateurs authentifiés
                return isset($_SESSION['user_id']);
            }
        });

        // Add debug logging
        error_log("Creating GraphQL schema with controllers in App\\GraphQL\\Controllers namespace");

        // List all controller files
        $controllersDir = __DIR__ . '/Controllers';
        if (is_dir($controllersDir)) {
            $files = scandir($controllersDir);
            error_log("Controllers found: " . implode(", ", array_filter($files, function ($file) {
                return $file !== '.' && $file !== '..';
            })));

            // Try to instantiate the DummyController directly
            try {
                $dummyController = $container->get(\App\GraphQL\Controllers\DummyController::class);
                error_log("DummyController instantiated successfully");

                // Try to instantiate the SMSController directly
                try {
                    $smsController = $container->get(\App\GraphQL\Controllers\SMSController::class);
                    error_log("SMSController instantiated successfully");
                } catch (\Throwable $e) {
                    error_log("Error instantiating SMSController: " . $e->getMessage());
                }
            } catch (\Throwable $e) {
                error_log("Error instantiating DummyController: " . $e->getMessage());
            }
        } else {
            error_log("Controllers directory not found: $controllersDir");
        }

        // Create the schema
        try {
            $schema = $schemaFactory->createSchema();
            error_log("Schema created successfully");

            // Log the queries available in the schema
            $queryType = $schema->getQueryType();
            $fields = $queryType->getFields();
            error_log("Queries available in schema: " . implode(", ", array_keys($fields)));

            return $schema;
        } catch (\Throwable $e) {
            error_log("Error creating schema: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
}
