<?php

namespace Tests\Integration;

use App\Services\SMSService;
use App\Services\Interfaces\OrangeAPIClientInterface;
use App\Services\Interfaces\SubjectInterface; // EventManager
use App\Entities\User;
use App\Entities\OrangeAPIConfig;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\OrangeAPIConfigRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use DI\ContainerBuilder;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface; // Import ContainerInterface

class SMSServiceIntegrationTest extends TestCase
{
    use ProphecyTrait;

    private static ?EntityManagerInterface $entityManager = null;
    private static ?ContainerInterface $container = null; // Use ContainerInterface type hint
    private ?OrangeAPIClientInterface $mockApiClient = null; // Mocked API client
    private ?SubjectInterface $mockEventManager = null; // Mocked Event Manager

    public static function setUpBeforeClass(): void
    {
        // Bootstrap Doctrine EntityManager only once for the test class
        if (self::$entityManager === null) {
            require_once __DIR__ . '/../../src/bootstrap-doctrine.php'; // Adjust path as needed
            self::$entityManager = $entityManager; // $entityManager is created in bootstrap-doctrine.php
        }

        // Build DI Container only once
        if (self::$container === null) {
            $containerBuilder = new ContainerBuilder();
            // Add definitions (adjust paths as needed)
            // Use actual Doctrine repositories bound to the EntityManager
            $containerBuilder->addDefinitions([
                EntityManagerInterface::class => self::$entityManager,
                // Bind repository interfaces to their Doctrine implementations
                \App\Repositories\Interfaces\UserRepositoryInterface::class => \DI\autowire(\App\Repositories\Doctrine\UserRepository::class),
                \App\Repositories\Interfaces\OrangeAPIConfigRepositoryInterface::class => \DI\autowire(\App\Repositories\Doctrine\OrangeAPIConfigRepository::class),
                \App\Repositories\Interfaces\SMSHistoryRepositoryInterface::class => \DI\autowire(\App\Repositories\Doctrine\SMSHistoryRepository::class),
                \App\Repositories\Interfaces\ContactRepositoryInterface::class => \DI\autowire(\App\Repositories\Doctrine\ContactRepository::class),
                \App\Repositories\Interfaces\CustomSegmentRepositoryInterface::class => \DI\autowire(\App\Repositories\Doctrine\CustomSegmentRepository::class),
                // Add other necessary bindings...
            ]);
            // Add service definitions that might need mocks injected later or use autowiring
            $containerBuilder->addDefinitions(__DIR__ . '/../../src/config/di/services.php'); // Assuming services are defined here
            $containerBuilder->addDefinitions(__DIR__ . '/../../src/config/di/other.php');

            // Build the container
            self::$container = $containerBuilder->build();
        }
    }

    protected function setUp(): void
    {
        // Create fresh mocks for external dependencies for each test
        $this->mockApiClient = $this->prophesize(OrangeAPIClientInterface::class);
        $this->mockEventManager = $this->prophesize(SubjectInterface::class);
        // Logger will be mocked when needed within the test or fetched if a real one is configured

        // Clear the entity manager before each test to ensure isolation (optional, depends on strategy)
        // self::$entityManager->clear();

        // Begin transaction for database isolation
        self::$entityManager->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Rollback transaction to undo changes made during the test
        if (self::$entityManager !== null && self::$entityManager->getConnection()->isTransactionActive()) {
            self::$entityManager->rollback();
        }
        // Optional: Close entity manager if needed, though typically done in tearDownAfterClass
        // self::$entityManager->close();
    }

    public static function tearDownAfterClass(): void
    {
        // Close the EntityManager connection after all tests in the class are done
        if (self::$entityManager !== null && self::$entityManager->isOpen()) {
            self::$entityManager->close();
            self::$entityManager = null;
        }
        self::$container = null;
    }

    /**
     * @test
     */
    public function sendSMSSetsLimitToZeroWhenCreditsReachZeroIntegration(): void
    {
        // Arrange

        // 1. Create Test User and Config Entities
        $testUsername = 'integration_user_' . uniqid();
        $user = new User();
        $user->setUsername($testUsername);
        $user->setPassword(password_hash('password', PASSWORD_DEFAULT)); // Set a password
        $user->setEmail($testUsername . '@example.com');
        $user->setSmsCredit(1); // Start with 1 credit
        $user->setSmsLimit(100); // Start with a non-zero limit
        $user->setIsAdmin(false);
        $user->setCreatedAt(new \DateTime());

        $config = new OrangeAPIConfig();
        // Correctly associate by User ID
        $config->setUserId($user->getId());
        $config->setClientId('test-client-id');
        $config->setClientSecret('test-client-secret');
        // SenderName, SenderAddress, IsDefault are not properties of OrangeAPIConfig entity
        // The service likely uses defaults or user-specific config elsewhere.
        // We'll assume the service uses the default sender name from .env for the API call mock.
        $expectedSenderName = getenv('ORANGE_DEFAULT_SENDER_NAME') ?: 'Qualitas CI'; // Get default from env or fallback

        // 2. Persist to Database
        self::$entityManager->persist($user);
        self::$entityManager->persist($config);
        self::$entityManager->flush(); // Save to DB

        // Ensure user ID is generated
        $userId = $user->getId();
        $this->assertNotNull($userId, "User ID should not be null after flush.");

        // 3. Mock External Dependencies
        $phoneNumber = '+2250777999999';
        $message = 'Integration test last credit';
        $apiResponse = ['outboundSMSMessageRequest' => ['senderName' => $expectedSenderName]]; // Mock successful API response

        $this->mockApiClient
            ->sendSMS($phoneNumber, $message, $expectedSenderName) // Expect the default sender name
            ->shouldBeCalledTimes(1) // Expect exactly one call
            ->willReturn($apiResponse);

        $this->mockEventManager
            ->notify('sms.sent', Argument::that(function ($arg) use ($phoneNumber, $message) {
                return is_array($arg) &&
                    isset($arg['phoneNumber']) && $arg['phoneNumber'] === $phoneNumber &&
                    isset($arg['message']) && $arg['message'] === $message &&
                    isset($arg['senderName']); // Check for expected keys
            }))
            ->shouldBeCalledTimes(1); // Expect exactly one notification

        // 4. Get Service Instance Manually with Mocks and Real Repos
        // Fetch real repositories from the container
        $userRepository = self::$container->get(UserRepositoryInterface::class);
        $configRepository = self::$container->get(OrangeAPIConfigRepositoryInterface::class);
        $smsHistoryRepository = self::$container->get(\App\Repositories\Interfaces\SMSHistoryRepositoryInterface::class); // Use actual interface
        $contactRepository = self::$container->get(\App\Repositories\Interfaces\ContactRepositoryInterface::class); // Use actual interface
        $customSegmentRepository = self::$container->get(\App\Repositories\Interfaces\CustomSegmentRepositoryInterface::class); // Use actual interface
        $mockLoggerProphecy = $this->prophesize(LoggerInterface::class); // Create prophecy first
        $mockLogger = $mockLoggerProphecy->reveal(); // Reveal when injecting

        // Instantiate the service manually
        $smsService = new SMSService(
            $this->mockApiClient->reveal(),
            $smsHistoryRepository,
            $userRepository,
            $configRepository,
            $contactRepository,
            $customSegmentRepository,
            $this->mockEventManager->reveal(),
            $mockLogger // Inject the mock logger
        );


        // Act
        $result = $smsService->sendSMS($phoneNumber, $message, $userId);

        // Assert

        // 5. Verify API call result (optional, basic check)
        $this->assertIsArray($result);

        // 6. Verify Mocks (Prophecy checks automatically on tearDown)

        // 7. Verify Database State
        // Clear EntityManager's identity map to force fetching from DB
        self::$entityManager->clear();
        $userRepository = self::$container->get(UserRepositoryInterface::class);
        $updatedUser = $userRepository->findById($userId);

        $this->assertNotNull($updatedUser, "User should still exist in the database.");
        $this->assertEquals(0, $updatedUser->getSmsCredit(), "SMS Credit should be 0 after sending the last one.");
        $this->assertEquals(0, $updatedUser->getSmsLimit(), "SMS Limit should be set to 0 when credit reaches 0.");
    }

    // Add more integration tests here...
}
