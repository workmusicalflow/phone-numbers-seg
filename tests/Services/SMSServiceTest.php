<?php

namespace Tests\Services;

// Use Interfaces and Entities
use App\Services\SMSService;
use App\Services\Interfaces\OrangeAPIClientInterface;
use App\Repositories\Interfaces\SMSHistoryRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\OrangeAPIConfigRepositoryInterface;
use App\Repositories\Interfaces\ContactRepositoryInterface;
use App\Repositories\Interfaces\CustomSegmentRepositoryInterface;
use App\Services\Interfaces\SubjectInterface; // EventManager
use App\Entities\User;
use App\Entities\OrangeAPIConfig;
use App\Entities\Contact; // Import Contact entity
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Exception; // Import Exception

class SMSServiceTest extends TestCase
{
    use ProphecyTrait; // Use Prophecy

    // Properties for mocked dependencies
    private $apiClient;
    private $smsHistoryRepository;
    private $userRepository;
    private $configRepository;
    private $contactRepository;
    private $customSegmentRepository;
    private $eventManager;
    private $logger;
    private $smsService; // The actual service instance

    protected function setUp(): void
    {
        // Prophesize dependencies
        $this->apiClient = $this->prophesize(OrangeAPIClientInterface::class);
        $this->smsHistoryRepository = $this->prophesize(SMSHistoryRepositoryInterface::class);
        $this->userRepository = $this->prophesize(UserRepositoryInterface::class);
        $this->configRepository = $this->prophesize(OrangeAPIConfigRepositoryInterface::class);
        $this->contactRepository = $this->prophesize(ContactRepositoryInterface::class);
        $this->customSegmentRepository = $this->prophesize(CustomSegmentRepositoryInterface::class);
        $this->eventManager = $this->prophesize(SubjectInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        // Instantiate the actual SMSService with mocked dependencies
        $this->smsService = new SMSService(
            $this->apiClient->reveal(),
            $this->smsHistoryRepository->reveal(),
            $this->userRepository->reveal(),
            $this->configRepository->reveal(),
            $this->contactRepository->reveal(),
            $this->customSegmentRepository->reveal(),
            $this->eventManager->reveal(),
            $this->logger->reveal()
        );
    }

    /**
     * Test sending a single SMS successfully.
     * @test
     */
    public function sendSMSSuccessfully(): void
    {
        // Arrange
        $userId = 1;
        $phoneNumber = '+2250777104936';
        $message = 'Test message';
        $initialCredits = 10;
        $messageCost = 1; // Assuming 1 credit per message

        // Mock User
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getId()->willReturn($userId);
        $userProphecy->getSmsCredit()->willReturn($initialCredits);
        $userProphecy->setSmsCredit($initialCredits - $messageCost)->shouldBeCalled(); // Expect credit deduction

        // Mock Config
        $configProphecy = $this->prophesize(OrangeAPIConfig::class);
        $configProphecy->getSenderName()->willReturn('TestSender');

        // Mock API Client response
        $apiResponse = [
            'outboundSMSMessageRequest' => [
                'address' => ['tel:' . $phoneNumber],
                'senderAddress' => 'tel:+2250595016840', // Example
                'senderName' => 'TestSender',
                'outboundSMSTextMessage' => ['message' => $message],
                'resourceURL' => 'https://api.orange.com/smsmessaging/v1/outbound/tel:+2250595016840/requests/test-id'
            ]
        ];

        // Setup repository/service expectations
        $this->userRepository->findById($userId)->shouldBeCalled()->willReturn($userProphecy->reveal());
        $this->configRepository->findByUserId($userId)->shouldBeCalled()->willReturn($configProphecy->reveal());
        $this->apiClient->sendSMS($phoneNumber, $message, 'TestSender')->shouldBeCalled()->willReturn($apiResponse);
        $this->userRepository->save($userProphecy->reveal())->shouldBeCalled(); // Expect user save after credit deduction
        $this->eventManager->notify('sms.sent', Argument::type('array'))->shouldBeCalled(); // Expect event notification

        // Act
        $result = $this->smsService->sendSMS($phoneNumber, $message, $userId);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('outboundSMSMessageRequest', $result);
        $this->assertEquals($message, $result['outboundSMSMessageRequest']['outboundSMSTextMessage']['message']);
        $this->assertEquals('TestSender', $result['outboundSMSMessageRequest']['senderName']);
    }

    /**
     * Test sending SMS fails due to insufficient credits.
     * @test
     */
    public function sendSMSFailsInsufficientCredits(): void
    {
        // Arrange
        $userId = 1;
        $phoneNumber = '+2250777104936';
        $message = 'Test message';
        $initialCredits = 0; // Not enough credits

        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getId()->willReturn($userId);
        $userProphecy->getSmsCredit()->willReturn($initialCredits);

        $configProphecy = $this->prophesize(OrangeAPIConfig::class);
        $configProphecy->getSenderName()->willReturn('TestSender');

        $this->userRepository->findById($userId)->shouldBeCalled()->willReturn($userProphecy->reveal());
        $this->configRepository->findByUserId($userId)->shouldBeCalled()->willReturn($configProphecy->reveal());

        // Expect Exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Crédits SMS insuffisants.");

        // Act
        $this->smsService->sendSMS($phoneNumber, $message, $userId);

        // Assertions are handled by expectException. Verify mocks were NOT called beyond user/config fetch.
        $this->apiClient->sendSMS(Argument::any(), Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
        $this->eventManager->notify(Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
        $this->userRepository->save(Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * Test sending SMS fails due to API error.
     * @test
     */
    public function sendSMSFailsAPIError(): void
    {
        // Arrange
        $userId = 1;
        $phoneNumber = '+2250777104936';
        $message = 'Test message';
        $initialCredits = 10;
        $apiErrorMessage = 'Orange API Error';

        // Mock User & Config (found successfully)
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getId()->willReturn($userId);
        $userProphecy->getSmsCredit()->willReturn($initialCredits);
        // IMPORTANT: setSmsCredit should NOT be called if API fails
        $userProphecy->setSmsCredit(Argument::any())->shouldNotBeCalled();

        $configProphecy = $this->prophesize(OrangeAPIConfig::class);
        $configProphecy->getSenderName()->willReturn('TestSender');

        // Setup repository expectations
        $this->userRepository->findById($userId)->shouldBeCalled()->willReturn($userProphecy->reveal());
        $this->configRepository->findByUserId($userId)->shouldBeCalled()->willReturn($configProphecy->reveal());

        // Mock API Client to throw an exception
        $this->apiClient->sendSMS($phoneNumber, $message, 'TestSender')
            ->shouldBeCalled()
            ->willThrow(new Exception($apiErrorMessage));

        // Expect 'sms.failed' event notification
        $this->eventManager->notify('sms.failed', Argument::withEntry('error', $apiErrorMessage))->shouldBeCalled();
        // Expect logging
        $this->logger->error(Argument::containingString($apiErrorMessage), Argument::type('array'))->shouldBeCalled();

        // Expect Exception from the service
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($apiErrorMessage); // Service should re-throw the API exception

        // Act
        try {
            $this->smsService->sendSMS($phoneNumber, $message, $userId);
        } catch (Exception $e) {
            // Assertions after catch block to ensure mocks were checked
            $this->userRepository->save(Argument::any())->shouldNotHaveBeenCalled(); // No user save if API fails
            throw $e; // Re-throw for PHPUnit to catch
        }
    }

    /**
     * Test sending SMS fails when the user is not found.
     * @test
     */
    public function sendSMSFailsUserNotFound(): void
    {
        // Arrange
        $userId = 999; // Non-existent user
        $phoneNumber = '+2250777104936';
        $message = 'Test message';

        // Mock UserRepository to return null
        $this->userRepository->findById($userId)->shouldBeCalled()->willReturn(null);

        // Expect Exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Utilisateur non trouvé.");

        // Act
        $this->smsService->sendSMS($phoneNumber, $message, $userId);

        // Assertions are handled by expectException. Verify other mocks were not called.
        $this->configRepository->findByUserId(Argument::any())->shouldNotHaveBeenCalled();
        $this->apiClient->sendSMS(Argument::any(), Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
        $this->eventManager->notify(Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
        $this->logger->error(Argument::any(), Argument::any())->shouldBeCalled(); // Should log the error
    }

    /**
     * Test sending SMS fails when the Orange API config is not found for the user.
     * @test
     */
    public function sendSMSFailsConfigNotFound(): void
    {
        // Arrange
        $userId = 1;
        $phoneNumber = '+2250777104936';
        $message = 'Test message';
        $initialCredits = 10;

        // Mock User (found successfully)
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getId()->willReturn($userId);
        $userProphecy->getSmsCredit()->willReturn($initialCredits);

        // Mock UserRepository
        $this->userRepository->findById($userId)->shouldBeCalled()->willReturn($userProphecy->reveal());

        // Mock ConfigRepository to return null
        $this->configRepository->findByUserId($userId)->shouldBeCalled()->willReturn(null);

        // Expect Exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Configuration API Orange non trouvée pour l'utilisateur.");

        // Act
        $this->smsService->sendSMS($phoneNumber, $message, $userId);

        // Assertions are handled by expectException. Verify other mocks were not called.
        $this->apiClient->sendSMS(Argument::any(), Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
        $this->eventManager->notify(Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
        $this->userRepository->save(Argument::any())->shouldNotHaveBeenCalled();
        $this->logger->error(Argument::any(), Argument::any())->shouldBeCalled(); // Should log the error
    }

    /**
     * Test sending bulk SMS successfully.
     * @test
     */
    public function sendBulkSMSSuccessfully(): void
    {
        // Arrange
        $userId = 1;
        $phoneNumbers = ['+2250777104936', '+2250777104937'];
        $message = 'Bulk test message';
        $initialCredits = 10;
        $requiredCredits = count($phoneNumbers); // 2 credits needed

        // Mock User
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getId()->willReturn($userId);
        $userProphecy->getSmsCredit()->willReturn($initialCredits);
        $userProphecy->setSmsCredit($initialCredits - $requiredCredits)->shouldBeCalled();

        // Mock Config
        $configProphecy = $this->prophesize(OrangeAPIConfig::class);
        $configProphecy->getSenderName()->willReturn('BulkSender');

        // Mock API Client responses for each number
        $apiResponse1 = ['outboundSMSMessageRequest' => ['senderName' => 'BulkSender', 'outboundSMSTextMessage' => ['message' => $message]]];
        $apiResponse2 = ['outboundSMSMessageRequest' => ['senderName' => 'BulkSender', 'outboundSMSTextMessage' => ['message' => $message]]];

        // Setup expectations
        $this->userRepository->findById($userId)->shouldBeCalled()->willReturn($userProphecy->reveal());
        $this->configRepository->findByUserId($userId)->shouldBeCalled()->willReturn($configProphecy->reveal());
        // Expect API call for each number
        $this->apiClient->sendSMS($phoneNumbers[0], $message, 'BulkSender')->shouldBeCalled()->willReturn($apiResponse1);
        $this->apiClient->sendSMS($phoneNumbers[1], $message, 'BulkSender')->shouldBeCalled()->willReturn($apiResponse2);
        $this->userRepository->save($userProphecy->reveal())->shouldBeCalled();
        // Expect event notification for each successful send
        $this->eventManager->notify('sms.sent', Argument::type('array'))->shouldBeCalledTimes(count($phoneNumbers));

        // Act
        $result = $this->smsService->sendBulkSMS($phoneNumbers, $message, $userId);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(count($phoneNumbers), $result);
        $this->assertArrayHasKey($phoneNumbers[0], $result);
        $this->assertArrayHasKey($phoneNumbers[1], $result);
        $this->assertEquals('success', $result[$phoneNumbers[0]]['status']);
        $this->assertEquals('success', $result[$phoneNumbers[1]]['status']);
        $this->assertEquals($message, $result[$phoneNumbers[0]]['response']['outboundSMSMessageRequest']['outboundSMSTextMessage']['message']);
    }

    /**
     * Test sending bulk SMS fails due to insufficient credits.
     * @test
     */
    public function sendBulkSMSFailsInsufficientCredits(): void
    {
        // Arrange
        $userId = 1;
        $phoneNumbers = ['+2250777104936', '+2250777104937'];
        $message = 'Bulk test message';
        $initialCredits = 1; // Only 1 credit, but 2 needed
        $requiredCredits = count($phoneNumbers);

        // Mock User
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getId()->willReturn($userId);
        $userProphecy->getSmsCredit()->willReturn($initialCredits);

        // Mock Config
        $configProphecy = $this->prophesize(OrangeAPIConfig::class);
        $configProphecy->getSenderName()->willReturn('BulkSender');

        // Setup expectations
        $this->userRepository->findById($userId)->shouldBeCalled()->willReturn($userProphecy->reveal());
        $this->configRepository->findByUserId($userId)->shouldBeCalled()->willReturn($configProphecy->reveal());

        // Expect Exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Crédits SMS insuffisants pour envoyer à tous les numéros.");

        // Act
        $this->smsService->sendBulkSMS($phoneNumbers, $message, $userId);

        // Assertions handled by expectException. Verify mocks not called.
        $this->apiClient->sendSMS(Argument::any(), Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
        $this->eventManager->notify(Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
        $this->userRepository->save(Argument::any())->shouldNotHaveBeenCalled();
        $this->logger->warning(Argument::containingString('Crédits insuffisants'), Argument::type('array'))->shouldBeCalled();
    }

    /**
     * Test sending bulk SMS with partial success (some API calls fail).
     * @test
     */
    public function sendBulkSMSPartialSuccess(): void
    {
        // Arrange
        $userId = 1;
        $phoneNumbers = ['+2250777104936', '+2250777104937', '+2250777104938']; // 3 numbers
        $message = 'Partial bulk test';
        $initialCredits = 10;
        $requiredCredits = count($phoneNumbers); // 3 credits needed
        $apiErrorMessage = 'API Failed for this number';

        // Mock User
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getId()->willReturn($userId);
        $userProphecy->getSmsCredit()->willReturn($initialCredits);
        // Credits should be deducted for ALL attempts, even failures
        $userProphecy->setSmsCredit($initialCredits - $requiredCredits)->shouldBeCalled();

        // Mock Config
        $configProphecy = $this->prophesize(OrangeAPIConfig::class);
        $configProphecy->getSenderName()->willReturn('PartialSender');

        // Mock API Client responses: Success, Fail, Success
        $apiResponseSuccess = ['outboundSMSMessageRequest' => ['senderName' => 'PartialSender']];
        $this->apiClient->sendSMS($phoneNumbers[0], $message, 'PartialSender')
            ->shouldBeCalled()->willReturn($apiResponseSuccess);
        $this->apiClient->sendSMS($phoneNumbers[1], $message, 'PartialSender')
            ->shouldBeCalled()->willThrow(new Exception($apiErrorMessage));
        $this->apiClient->sendSMS($phoneNumbers[2], $message, 'PartialSender')
            ->shouldBeCalled()->willReturn($apiResponseSuccess);

        // Setup expectations
        $this->userRepository->findById($userId)->shouldBeCalled()->willReturn($userProphecy->reveal());
        $this->configRepository->findByUserId($userId)->shouldBeCalled()->willReturn($configProphecy->reveal());
        $this->userRepository->save($userProphecy->reveal())->shouldBeCalled(); // User saved after all attempts

        // Expect event notifications: 2 sent, 1 failed
        $this->eventManager->notify('sms.sent', Argument::withEntry('phoneNumber', $phoneNumbers[0]))->shouldBeCalledTimes(1);
        $this->eventManager->notify('sms.sent', Argument::withEntry('phoneNumber', $phoneNumbers[2]))->shouldBeCalledTimes(1);
        $this->eventManager->notify('sms.failed', Argument::allOf(
            Argument::withEntry('phoneNumber', $phoneNumbers[1]),
            Argument::withEntry('error', $apiErrorMessage)
        ))->shouldBeCalledTimes(1);

        // Expect logging for the failure
        $this->logger->error(Argument::containingString($apiErrorMessage), Argument::withEntry('phoneNumber', $phoneNumbers[1]))->shouldBeCalled();

        // Act
        $result = $this->smsService->sendBulkSMS($phoneNumbers, $message, $userId);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(count($phoneNumbers), $result);
        // Check status for each number
        $this->assertEquals('success', $result[$phoneNumbers[0]]['status']);
        $this->assertEquals('failed', $result[$phoneNumbers[1]]['status']);
        $this->assertEquals($apiErrorMessage, $result[$phoneNumbers[1]]['error']);
        $this->assertEquals('success', $result[$phoneNumbers[2]]['status']);
    }

    /**
     * Test sending SMS to a segment successfully.
     * @test
     */
    public function sendSMSToSegmentSuccessfully(): void
    {
        // Arrange
        $userId = 1;
        $segmentId = 1;
        $message = 'Segment test message';
        $initialCredits = 10;
        $phoneNumbersInSegment = ['+2250111111111', '+2250222222222'];
        $requiredCredits = count($phoneNumbersInSegment);

        // Mock User & Config
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getId()->willReturn($userId);
        $userProphecy->getSmsCredit()->willReturn($initialCredits);
        $userProphecy->setSmsCredit($initialCredits - $requiredCredits)->shouldBeCalled();

        $configProphecy = $this->prophesize(OrangeAPIConfig::class);
        $configProphecy->getSenderName()->willReturn('SegmentSender');

        // Mock CustomSegmentRepository
        $this->customSegmentRepository->findPhoneNumbersBySegmentId($segmentId)
            ->shouldBeCalled()
            ->willReturn($phoneNumbersInSegment);

        // Mock API Client responses
        $apiResponse1 = ['outboundSMSMessageRequest' => ['senderName' => 'SegmentSender', 'outboundSMSTextMessage' => ['message' => $message]]];
        $apiResponse2 = ['outboundSMSMessageRequest' => ['senderName' => 'SegmentSender', 'outboundSMSTextMessage' => ['message' => $message]]];

        // Setup expectations
        $this->userRepository->findById($userId)->shouldBeCalled()->willReturn($userProphecy->reveal());
        $this->configRepository->findByUserId($userId)->shouldBeCalled()->willReturn($configProphecy->reveal());
        $this->apiClient->sendSMS($phoneNumbersInSegment[0], $message, 'SegmentSender')->shouldBeCalled()->willReturn($apiResponse1);
        $this->apiClient->sendSMS($phoneNumbersInSegment[1], $message, 'SegmentSender')->shouldBeCalled()->willReturn($apiResponse2);
        $this->userRepository->save($userProphecy->reveal())->shouldBeCalled();
        $this->eventManager->notify('sms.sent', Argument::type('array'))->shouldBeCalledTimes(count($phoneNumbersInSegment));

        // Act
        $result = $this->smsService->sendSMSToSegment($segmentId, $message, $userId);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(count($phoneNumbersInSegment), $result);
        $this->assertArrayHasKey($phoneNumbersInSegment[0], $result);
        $this->assertEquals('success', $result[$phoneNumbersInSegment[0]]['status']);
    }

    /**
     * Test sending SMS to a segment fails due to insufficient credits.
     * @test
     */
    public function sendSMSToSegmentFailsInsufficientCredits(): void
    {
        // Arrange
        $userId = 1;
        $segmentId = 1;
        $message = 'Segment test message';
        $initialCredits = 1; // Only 1 credit
        $phoneNumbersInSegment = ['+2250111111111', '+2250222222222']; // 2 numbers, need 2 credits
        $requiredCredits = count($phoneNumbersInSegment);

        // Mock User & Config
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getId()->willReturn($userId);
        $userProphecy->getSmsCredit()->willReturn($initialCredits);

        $configProphecy = $this->prophesize(OrangeAPIConfig::class);
        $configProphecy->getSenderName()->willReturn('SegmentSender');

        // Mock CustomSegmentRepository to return numbers
        $this->customSegmentRepository->findPhoneNumbersBySegmentId($segmentId)
            ->shouldBeCalled()
            ->willReturn($phoneNumbersInSegment);

        // Setup expectations
        $this->userRepository->findById($userId)->shouldBeCalled()->willReturn($userProphecy->reveal());
        $this->configRepository->findByUserId($userId)->shouldBeCalled()->willReturn($configProphecy->reveal());

        // Expect Exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Crédits SMS insuffisants pour envoyer à tous les numéros du segment.");

        // Act
        $this->smsService->sendSMSToSegment($segmentId, $message, $userId);

        // Assertions handled by expectException. Verify mocks not called.
        $this->apiClient->sendSMS(Argument::any(), Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
        $this->eventManager->notify(Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
        $this->userRepository->save(Argument::any())->shouldNotHaveBeenCalled();
        $this->logger->warning(Argument::containingString('Crédits insuffisants'), Argument::type('array'))->shouldBeCalled();
    }

    /**
     * Test sending SMS to a segment fails when the segment is empty or not found.
     * @test
     */
    public function sendSMSToSegmentFailsSegmentEmptyOrNotFound(): void
    {
        // Arrange
        $userId = 1;
        $segmentId = 999; // Non-existent or empty segment
        $message = 'Segment test message';
        $initialCredits = 10;

        // Mock User & Config
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getId()->willReturn($userId);
        $userProphecy->getSmsCredit()->willReturn($initialCredits);

        $configProphecy = $this->prophesize(OrangeAPIConfig::class);
        $configProphecy->getSenderName()->willReturn('SegmentSender');

        // Mock CustomSegmentRepository to return an empty array
        $this->customSegmentRepository->findPhoneNumbersBySegmentId($segmentId)
            ->shouldBeCalled()
            ->willReturn([]);

        // Setup expectations
        $this->userRepository->findById($userId)->shouldBeCalled()->willReturn($userProphecy->reveal());
        $this->configRepository->findByUserId($userId)->shouldBeCalled()->willReturn($configProphecy->reveal());

        // Expect Exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Aucun numéro de téléphone trouvé pour ce segment.");

        // Act
        $this->smsService->sendSMSToSegment($segmentId, $message, $userId);

        // Assertions handled by expectException. Verify mocks not called.
        $this->apiClient->sendSMS(Argument::any(), Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
        $this->eventManager->notify(Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
        $this->userRepository->save(Argument::any())->shouldNotHaveBeenCalled();
        $this->logger->warning(Argument::containingString('Aucun numéro trouvé'), Argument::type('array'))->shouldBeCalled();
    }

    /**
     * Test sending SMS to a segment with partial success.
     * @test
     */
    public function sendSMSToSegmentPartialSuccess(): void
    {
        // Arrange
        $userId = 1;
        $segmentId = 1;
        $message = 'Partial segment test';
        $initialCredits = 10;
        $phoneNumbersInSegment = ['+2250111111111', '+2250222222222', '+2250333333333']; // 3 numbers
        $requiredCredits = count($phoneNumbersInSegment);
        $apiErrorMessage = 'API Failed for this segment number';

        // Mock User & Config
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getId()->willReturn($userId);
        $userProphecy->getSmsCredit()->willReturn($initialCredits);
        $userProphecy->setSmsCredit($initialCredits - $requiredCredits)->shouldBeCalled(); // Deduct for all attempts

        $configProphecy = $this->prophesize(OrangeAPIConfig::class);
        $configProphecy->getSenderName()->willReturn('PartialSegmentSender');

        // Mock CustomSegmentRepository
        $this->customSegmentRepository->findPhoneNumbersBySegmentId($segmentId)
            ->shouldBeCalled()
            ->willReturn($phoneNumbersInSegment);

        // Mock API Client responses: Success, Fail, Success
        $apiResponseSuccess = ['outboundSMSMessageRequest' => ['senderName' => 'PartialSegmentSender']];
        $this->apiClient->sendSMS($phoneNumbersInSegment[0], $message, 'PartialSegmentSender')
            ->shouldBeCalled()->willReturn($apiResponseSuccess);
        $this->apiClient->sendSMS($phoneNumbersInSegment[1], $message, 'PartialSegmentSender')
            ->shouldBeCalled()->willThrow(new Exception($apiErrorMessage));
        $this->apiClient->sendSMS($phoneNumbersInSegment[2], $message, 'PartialSegmentSender')
            ->shouldBeCalled()->willReturn($apiResponseSuccess);

        // Setup expectations
        $this->userRepository->findById($userId)->shouldBeCalled()->willReturn($userProphecy->reveal());
        $this->configRepository->findByUserId($userId)->shouldBeCalled()->willReturn($configProphecy->reveal());
        $this->userRepository->save($userProphecy->reveal())->shouldBeCalled(); // User saved after all attempts

        // Expect event notifications: 2 sent, 1 failed
        $this->eventManager->notify('sms.sent', Argument::withEntry('phoneNumber', $phoneNumbersInSegment[0]))->shouldBeCalledTimes(1);
        $this->eventManager->notify('sms.sent', Argument::withEntry('phoneNumber', $phoneNumbersInSegment[2]))->shouldBeCalledTimes(1);
        $this->eventManager->notify('sms.failed', Argument::allOf(
            Argument::withEntry('phoneNumber', $phoneNumbersInSegment[1]),
            Argument::withEntry('error', $apiErrorMessage)
        ))->shouldBeCalledTimes(1);

        // Expect logging for the failure
        $this->logger->error(Argument::containingString($apiErrorMessage), Argument::withEntry('phoneNumber', $phoneNumbersInSegment[1]))->shouldBeCalled();

        // Act
        $result = $this->smsService->sendSMSToSegment($segmentId, $message, $userId);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(count($phoneNumbersInSegment), $result);
        // Check status for each number
        $this->assertEquals('success', $result[$phoneNumbersInSegment[0]]['status']);
        $this->assertEquals('failed', $result[$phoneNumbersInSegment[1]]['status']);
        $this->assertEquals($apiErrorMessage, $result[$phoneNumbersInSegment[1]]['error']);
        $this->assertEquals('success', $result[$phoneNumbersInSegment[2]]['status']);
    }

    // Note: The getSegmentsForSMS method is likely in a Resolver or Controller, not SMSService.
    // Test for sendToAllContacts (needs ContactRepository mock)
    /**
     * Test sending SMS to all contacts successfully.
     * @test
     */
    public function sendToAllContactsSuccessfully(): void
    {
        // Arrange
        $userId = 1;
        $message = 'Message to all contacts';
        $initialCredits = 10;

        // Mock contacts returned by repository
        $contact1 = $this->prophesize(Contact::class);
        $contact1->getPhoneNumber()->willReturn('+2250101010101');
        $contact2 = $this->prophesize(Contact::class);
        $contact2->getPhoneNumber()->willReturn('+2250202020202');
        $contacts = [$contact1->reveal(), $contact2->reveal()];
        $requiredCredits = count($contacts);

        // Mock User & Config
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getId()->willReturn($userId);
        $userProphecy->getSmsCredit()->willReturn($initialCredits);
        $userProphecy->setSmsCredit($initialCredits - $requiredCredits)->shouldBeCalled();

        $configProphecy = $this->prophesize(OrangeAPIConfig::class);
        $configProphecy->getSenderName()->willReturn('AllContactsSender');

        // Mock API Client responses
        $apiResponse1 = ['outboundSMSMessageRequest' => ['senderName' => 'AllContactsSender']];
        $apiResponse2 = ['outboundSMSMessageRequest' => ['senderName' => 'AllContactsSender']];

        // Setup expectations
        $this->userRepository->findById($userId)->shouldBeCalled()->willReturn($userProphecy->reveal());
        $this->configRepository->findByUserId($userId)->shouldBeCalled()->willReturn($configProphecy->reveal());
        $this->contactRepository->findByUserId($userId, null, null)->shouldBeCalled()->willReturn($contacts); // Expect call to get contacts
        $this->apiClient->sendSMS($contacts[0]->getPhoneNumber(), $message, 'AllContactsSender')->shouldBeCalled()->willReturn($apiResponse1);
        $this->apiClient->sendSMS($contacts[1]->getPhoneNumber(), $message, 'AllContactsSender')->shouldBeCalled()->willReturn($apiResponse2);
        $this->userRepository->save($userProphecy->reveal())->shouldBeCalled();
        $this->eventManager->notify('sms.sent', Argument::type('array'))->shouldBeCalledTimes(count($contacts));

        // Act
        $result = $this->smsService->sendToAllContacts($userId, $message);

        // Assert (Structure based on BulkSMSResult type in schema)
        $this->assertIsArray($result);
        $this->assertEquals('COMPLETED', $result['status']);
        $this->assertEquals(count($contacts), $result['summary']['total']);
        $this->assertEquals(count($contacts), $result['summary']['successful']);
        $this->assertEquals(0, $result['summary']['failed']);
        $this->assertCount(count($contacts), $result['results']);
        $this->assertEquals('SENT', $result['results'][0]['status']);
        $this->assertEquals($contacts[0]->getPhoneNumber(), $result['results'][0]['phoneNumber']);
    }

    /**
     * Test sending SMS successfully sets smsLimit to 0 when smsCredit reaches 0.
     * @test
     */
    public function sendSMSSetsLimitToZeroWhenCreditsReachZero(): void
    {
        // Arrange
        $userId = 1;
        $phoneNumber = '+2250777104936';
        $message = 'Last credit message';
        $initialCredits = 1; // Exactly one credit left
        $initialLimit = 100; // Initial limit is non-zero
        $messageCost = 1;

        // Mock User
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getId()->willReturn($userId);
        $userProphecy->getSmsCredit()->willReturn($initialCredits);
        $userProphecy->getSmsLimit()->willReturn($initialLimit); // Need to mock getSmsLimit if checked before setting
        $userProphecy->setSmsCredit(0)->shouldBeCalled(); // Expect credit to become 0
        $userProphecy->setSmsLimit(0)->shouldBeCalled(); // Expect limit to become 0

        // Mock Config
        $configProphecy = $this->prophesize(OrangeAPIConfig::class);
        $configProphecy->getSenderName()->willReturn('LimitTester');

        // Mock API Client response
        $apiResponse = ['outboundSMSMessageRequest' => ['senderName' => 'LimitTester']];

        // Setup repository/service expectations
        $this->userRepository->findById($userId)->shouldBeCalled()->willReturn($userProphecy->reveal());
        $this->configRepository->findByUserId($userId)->shouldBeCalled()->willReturn($configProphecy->reveal());
        $this->apiClient->sendSMS($phoneNumber, $message, 'LimitTester')->shouldBeCalled()->willReturn($apiResponse);
        // Expect user save AFTER credit and limit are updated
        $this->userRepository->save($userProphecy->reveal())->shouldBeCalled();
        $this->eventManager->notify('sms.sent', Argument::type('array'))->shouldBeCalled();

        // Act
        $result = $this->smsService->sendSMS($phoneNumber, $message, $userId);

        // Assert
        $this->assertIsArray($result); // Basic check for successful API call structure
        // Prophecy assertions handle the core logic verification (setSmsCredit, setSmsLimit, save)
    }


    /**
     * Test sending SMS to all contacts fails due to insufficient credits.
     * @test
     */
    public function sendToAllContactsFailsInsufficientCredits(): void
    {
        // Arrange
        $userId = 1;
        $message = 'Message to all contacts';
        $initialCredits = 1; // Only 1 credit

        // Mock contacts returned by repository (2 contacts)
        $contact1 = $this->prophesize(Contact::class);
        $contact1->getPhoneNumber()->willReturn('+2250101010101');
        $contact2 = $this->prophesize(Contact::class);
        $contact2->getPhoneNumber()->willReturn('+2250202020202');
        $contacts = [$contact1->reveal(), $contact2->reveal()];
        $requiredCredits = count($contacts); // Need 2 credits

        // Mock User & Config
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getId()->willReturn($userId);
        $userProphecy->getSmsCredit()->willReturn($initialCredits);

        $configProphecy = $this->prophesize(OrangeAPIConfig::class);
        $configProphecy->getSenderName()->willReturn('AllContactsSender');

        // Setup expectations
        $this->userRepository->findById($userId)->shouldBeCalled()->willReturn($userProphecy->reveal());
        $this->configRepository->findByUserId($userId)->shouldBeCalled()->willReturn($configProphecy->reveal());
        $this->contactRepository->findByUserId($userId, null, null)->shouldBeCalled()->willReturn($contacts); // Expect call to get contacts

        // Expect Exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Crédits SMS insuffisants pour envoyer à tous les contacts.");

        // Act
        $this->smsService->sendToAllContacts($userId, $message);

        // Assertions handled by expectException. Verify mocks not called.
        $this->apiClient->sendSMS(Argument::any(), Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
        $this->eventManager->notify(Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
        $this->userRepository->save(Argument::any())->shouldNotHaveBeenCalled();
        $this->logger->warning(Argument::containingString('Crédits insuffisants'), Argument::type('array'))->shouldBeCalled();
    }

    /**
     * Test sending SMS to all contacts fails when the user has no contacts.
     * @test
     */
    public function sendToAllContactsFailsNoContacts(): void
    {
        // Arrange
        $userId = 1;
        $message = 'Message to all contacts';
        $initialCredits = 10;

        // Mock User & Config
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getId()->willReturn($userId);
        $userProphecy->getSmsCredit()->willReturn($initialCredits);

        $configProphecy = $this->prophesize(OrangeAPIConfig::class);
        $configProphecy->getSenderName()->willReturn('AllContactsSender');

        // Mock ContactRepository to return an empty array
        $this->contactRepository->findByUserId($userId, null, null)->shouldBeCalled()->willReturn([]);

        // Setup expectations
        $this->userRepository->findById($userId)->shouldBeCalled()->willReturn($userProphecy->reveal());
        $this->configRepository->findByUserId($userId)->shouldBeCalled()->willReturn($configProphecy->reveal());

        // Expect Exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Aucun contact trouvé pour cet utilisateur.");

        // Act
        $this->smsService->sendToAllContacts($userId, $message);

        // Assertions handled by expectException. Verify mocks not called.
        $this->apiClient->sendSMS(Argument::any(), Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
        $this->eventManager->notify(Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
        $this->userRepository->save(Argument::any())->shouldNotHaveBeenCalled();
        $this->logger->warning(Argument::containingString('Aucun contact trouvé'), Argument::type('array'))->shouldBeCalled();
    }

    /**
     * Test sending SMS to all contacts with partial success.
     * @test
     */
    public function sendToAllContactsPartialSuccess(): void
    {
        // Arrange
        $userId = 1;
        $message = 'Partial message to all contacts';
        $initialCredits = 10;
        $apiErrorMessage = 'API Failed for this contact';

        // Mock contacts returned by repository
        $contact1 = $this->prophesize(Contact::class);
        $contact1->getPhoneNumber()->willReturn('+2250101010101');
        $contact2 = $this->prophesize(Contact::class);
        $contact2->getPhoneNumber()->willReturn('+2250202020202'); // This one will fail
        $contact3 = $this->prophesize(Contact::class);
        $contact3->getPhoneNumber()->willReturn('+2250303030303');
        $contacts = [$contact1->reveal(), $contact2->reveal(), $contact3->reveal()];
        $requiredCredits = count($contacts); // 3 credits needed

        // Mock User & Config
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getId()->willReturn($userId);
        $userProphecy->getSmsCredit()->willReturn($initialCredits);
        $userProphecy->setSmsCredit($initialCredits - $requiredCredits)->shouldBeCalled(); // Deduct for all attempts

        $configProphecy = $this->prophesize(OrangeAPIConfig::class);
        $configProphecy->getSenderName()->willReturn('PartialAllContactsSender');

        // Mock API Client responses: Success, Fail, Success
        $apiResponseSuccess = ['outboundSMSMessageRequest' => ['senderName' => 'PartialAllContactsSender']];
        $this->apiClient->sendSMS($contacts[0]->getPhoneNumber(), $message, 'PartialAllContactsSender')
            ->shouldBeCalled()->willReturn($apiResponseSuccess);
        $this->apiClient->sendSMS($contacts[1]->getPhoneNumber(), $message, 'PartialAllContactsSender')
            ->shouldBeCalled()->willThrow(new Exception($apiErrorMessage));
        $this->apiClient->sendSMS($contacts[2]->getPhoneNumber(), $message, 'PartialAllContactsSender')
            ->shouldBeCalled()->willReturn($apiResponseSuccess);

        // Setup expectations
        $this->userRepository->findById($userId)->shouldBeCalled()->willReturn($userProphecy->reveal());
        $this->configRepository->findByUserId($userId)->shouldBeCalled()->willReturn($configProphecy->reveal());
        $this->contactRepository->findByUserId($userId, null, null)->shouldBeCalled()->willReturn($contacts);
        $this->userRepository->save($userProphecy->reveal())->shouldBeCalled(); // User saved after all attempts

        // Expect event notifications: 2 sent, 1 failed
        $this->eventManager->notify('sms.sent', Argument::withEntry('phoneNumber', $contacts[0]->getPhoneNumber()))->shouldBeCalledTimes(1);
        $this->eventManager->notify('sms.sent', Argument::withEntry('phoneNumber', $contacts[2]->getPhoneNumber()))->shouldBeCalledTimes(1);
        $this->eventManager->notify('sms.failed', Argument::allOf(
            Argument::withEntry('phoneNumber', $contacts[1]->getPhoneNumber()),
            Argument::withEntry('error', $apiErrorMessage)
        ))->shouldBeCalledTimes(1);

        // Expect logging for the failure
        $this->logger->error(Argument::containingString($apiErrorMessage), Argument::withEntry('phoneNumber', $contacts[1]->getPhoneNumber()))->shouldBeCalled();

        // Act
        $result = $this->smsService->sendToAllContacts($userId, $message);

        // Assert (Structure based on BulkSMSResult type in schema)
        $this->assertIsArray($result);
        $this->assertEquals('PARTIAL', $result['status']); // Status should be PARTIAL
        $this->assertEquals(count($contacts), $result['summary']['total']);
        $this->assertEquals(2, $result['summary']['successful']); // 2 succeeded
        $this->assertEquals(1, $result['summary']['failed']);     // 1 failed
        $this->assertCount(count($contacts), $result['results']);
        // Check individual results
        $this->assertEquals('SENT', $result['results'][0]['status']);
        $this->assertEquals($contacts[0]->getPhoneNumber(), $result['results'][0]['phoneNumber']);
        $this->assertEquals('FAILED', $result['results'][1]['status']);
        $this->assertEquals($contacts[1]->getPhoneNumber(), $result['results'][1]['phoneNumber']);
        $this->assertEquals($apiErrorMessage, $result['results'][1]['error']);
        $this->assertEquals('SENT', $result['results'][2]['status']);
        $this->assertEquals($contacts[2]->getPhoneNumber(), $result['results'][2]['phoneNumber']);
    }
}
