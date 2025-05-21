<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Base TestCase for all tests
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * @var ContainerInterface
     */
    protected static ?ContainerInterface $container = null;
    
    /**
     * @var EntityManagerInterface
     */
    protected static ?EntityManagerInterface $entityManager = null;
    
    /**
     * Set up before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        if (static::$container === null) {
            $this->initContainer();
        }
    }
    
    /**
     * Initialize the dependency injection container
     */
    protected function initContainer(): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addDefinitions(__DIR__ . '/../src/config/di.php');
        
        // Override services for testing
        $this->overrideServicesForTesting($containerBuilder);
        
        static::$container = $containerBuilder->build();
    }
    
    /**
     * Override services for testing
     * 
     * @param ContainerBuilder $containerBuilder
     */
    protected function overrideServicesForTesting(ContainerBuilder $containerBuilder): void
    {
        // Set up in-memory database for testing
        $containerBuilder->addDefinitions([
            EntityManagerInterface::class => function() {
                if (static::$entityManager === null) {
                    $config = Setup::createAnnotationMetadataConfiguration(
                        [__DIR__ . '/../src/Entities'],
                        true,
                        null,
                        null,
                        false
                    );
                    
                    $config->setMetadataDriverImpl(
                        new \Doctrine\ORM\Mapping\Driver\AnnotationDriver(
                            new AnnotationReader(),
                            [__DIR__ . '/../src/Entities']
                        )
                    );
                    
                    $conn = [
                        'driver' => 'pdo_sqlite',
                        'memory' => true,
                    ];
                    
                    static::$entityManager = EntityManager::create($conn, $config);
                    
                    // Create schema
                    $schemaTool = new SchemaTool(static::$entityManager);
                    $classes = static::$entityManager->getMetadataFactory()->getAllMetadata();
                    $schemaTool->createSchema($classes);
                }
                
                return static::$entityManager;
            }
        ]);
    }
    
    /**
     * Create a mock with expectation for a given interface
     * 
     * @param string $className
     * @return MockObject
     */
    protected function createMockWithExpectations(string $className): MockObject
    {
        return $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();
    }
    
    /**
     * Add fixtures to the database
     * 
     * @param array $fixtures
     */
    protected function addFixtures(array $fixtures): void
    {
        $em = static::$container->get(EntityManagerInterface::class);
        
        foreach ($fixtures as $fixture) {
            $em->persist($fixture);
        }
        
        $em->flush();
    }
    
    /**
     * Create a mock HTTP client for testing API requests
     * 
     * @param array $responses Array of responses for different requests
     * @return MockObject GuzzleHttp\Client mock
     */
    protected function createMockHttpClient(array $responses): MockObject
    {
        $mockClient = $this->createMockWithExpectations(\GuzzleHttp\Client::class);
        
        // Configure the mock to return specific responses for requests
        $mockClient->method('request')
            ->will($this->returnCallback(function($method, $uri, $options) use ($responses) {
                $key = "$method $uri";
                
                if (isset($responses[$key])) {
                    return $responses[$key];
                }
                
                // Default response if not specified
                $mockResponse = $this->createMockWithExpectations(\GuzzleHttp\Psr7\Response::class);
                $mockResponse->method('getStatusCode')->willReturn(404);
                $mockResponse->method('getBody')->willReturn('Not found');
                
                return $mockResponse;
            }));
        
        return $mockClient;
    }
    
    /**
     * Create a mock Response for HTTP client
     * 
     * @param int $statusCode
     * @param mixed $body
     * @param array $headers
     * @return MockObject
     */
    protected function createMockResponse(int $statusCode, $body, array $headers = []): MockObject
    {
        $mockResponse = $this->createMockWithExpectations(\GuzzleHttp\Psr7\Response::class);
        
        $mockResponse->method('getStatusCode')
            ->willReturn($statusCode);
        
        if (is_array($body)) {
            $body = json_encode($body);
        }
        
        $mockResponse->method('getBody')
            ->willReturn($body);
        
        $mockResponse->method('getHeaders')
            ->willReturn($headers);
        
        return $mockResponse;
    }
    
    /**
     * Get a service from the container
     * 
     * @param string $id
     * @return mixed
     */
    protected function getService(string $id)
    {
        return static::$container->get($id);
    }
}