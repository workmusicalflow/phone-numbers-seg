<?php

namespace App\GraphQL;

use TheCodingMachine\GraphQLite\SchemaFactory;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use App\GraphQL\SimpleContainer;
use App\Repositories\PhoneNumberRepository;
use App\Repositories\SegmentRepository;
use App\Repositories\TechnicalSegmentRepository;
use App\Repositories\CustomSegmentRepository;
use App\Repositories\SMSHistoryRepository;
use App\Services\PhoneSegmentationService;
use App\Services\BatchSegmentationService;
use App\Services\CSVImportService;
use App\Services\ExportService;
use App\Services\SMSService;

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

        // Create a simple container and register our services
        $container = new SimpleContainer();

        // Create PDO instance
        $dbFile = __DIR__ . '/../../src/database/database.sqlite';
        $pdo = new \PDO("sqlite:$dbFile");
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Create repositories
        $phoneNumberRepository = new PhoneNumberRepository($pdo);
        $segmentRepository = new SegmentRepository($pdo);
        $technicalSegmentRepository = new TechnicalSegmentRepository($pdo);
        $customSegmentRepository = new CustomSegmentRepository($pdo);
        $smsHistoryRepository = new SMSHistoryRepository($pdo);

        // Create services
        $phoneSegmentationService = new PhoneSegmentationService();
        $batchSegmentationService = new BatchSegmentationService(
            $phoneSegmentationService,
            $phoneNumberRepository,
            $technicalSegmentRepository
        );
        $csvImportService = new CSVImportService(
            $phoneNumberRepository,
            $phoneSegmentationService
        );
        $exportService = new ExportService($phoneNumberRepository);

        // Create SMS service with Orange API credentials
        $smsService = new SMSService(
            'DGxbQKd9JHXLdFaWGtv0FfqFFI7Gu03a',  // Client ID
            'S4ywfdZUjNvOXErMr5NyQwgliBCdXIAYp1DcibKThBXs',  // Client Secret
            'tel:+2250595016840',  // Sender address
            'Qualitas CI',  // Sender name
            $phoneNumberRepository,
            $customSegmentRepository,
            $smsHistoryRepository
        );

        // Register all services in the container
        $container->set(\PDO::class, $pdo);
        $container->set(PhoneNumberRepository::class, $phoneNumberRepository);
        $container->set(SegmentRepository::class, $segmentRepository);
        $container->set(TechnicalSegmentRepository::class, $technicalSegmentRepository);
        $container->set(CustomSegmentRepository::class, $customSegmentRepository);
        $container->set(SMSHistoryRepository::class, $smsHistoryRepository);
        $container->set(PhoneSegmentationService::class, $phoneSegmentationService);
        $container->set(BatchSegmentationService::class, $batchSegmentationService);
        $container->set(CSVImportService::class, $csvImportService);
        $container->set(ExportService::class, $exportService);
        $container->set(SMSService::class, $smsService);

        // Create a schema factory with cache and container
        $schemaFactory = new SchemaFactory($cache, $container);

        // Configure the schema factory
        $schemaFactory->addControllerNamespace('App\\GraphQL\\Controllers');
        $schemaFactory->addTypeNamespace('App\\GraphQL\\Types');
        $schemaFactory->addTypeNamespace('App\\Models');

        // Create the schema
        return $schemaFactory->createSchema();
    }
}
