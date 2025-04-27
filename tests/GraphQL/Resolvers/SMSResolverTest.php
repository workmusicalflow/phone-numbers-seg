<?php

namespace Tests\GraphQL\Resolvers;

use App\GraphQL\Resolvers\SMSResolver;
use App\Repositories\Interfaces\SMSHistoryRepositoryInterface;
use App\Repositories\Interfaces\CustomSegmentRepositoryInterface;
use App\Services\SMSService;
use App\Services\Interfaces\AuthServiceInterface;
use App\GraphQL\Formatters\GraphQLFormatterInterface;
use App\Entities\User; // Import User entity
use App\Entities\SMSHistory; // Import SMSHistory entity
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Test class for SMSResolver
 *
 * @covers \App\GraphQL\Resolvers\SMSResolver
 */
class SMSResolverTest extends TestCase
{
    use ProphecyTrait;

    private $smsHistoryRepository;
    private $customSegmentRepository;
    private $smsService;
    private $authService;
    private $formatter;
    private $logger;
    private $resolver;
    private $userProphecy; // To store user mock

    protected function setUp(): void
    {
        $this->smsHistoryRepository = $this->prophesize(SMSHistoryRepositoryInterface::class);
        $this->customSegmentRepository = $this->prophesize(CustomSegmentRepositoryInterface::class);
        $this->smsService = $this->prophesize(SMSService::class);
        $this->authService = $this->prophesize(AuthServiceInterface::class);
        $this->formatter = $this->prophesize(GraphQLFormatterInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        // Mock a user for authentication checks
        $this->userProphecy = $this->prophesize(User::class);
        $this->userProphecy->getId()->willReturn(1); // Example user ID
        $this->userProphecy->isAdmin()->willReturn(false); // Default to non-admin

        // Default auth service behavior
        $this->authService->getCurrentUser()->willReturn($this->userProphecy->reveal());
        $this->authService->isAuthenticated()->willReturn(true);

        // Default formatter behavior
        $this->formatter->formatSmsHistory(Argument::type(SMSHistory::class))->will(function ($args) {
            // Simple mock formatter
            $history = $args[0];
            return [
                'id' => $history->getId() ?? 'mock_id',
                'phoneNumber' => $history->getPhoneNumber(),
                'message' => $history->getMessage(),
                'status' => $history->getStatus(),
                'createdAt' => $history->getCreatedAt() ? $history->getCreatedAt()->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
                // Add other fields as needed by frontend
            ];
        });

        $this->resolver = new SMSResolver(
            $this->smsHistoryRepository->reveal(),
            $this->customSegmentRepository->reveal(),
            $this->smsService->reveal(),
            $this->authService->reveal(),
            $this->formatter->reveal(),
            $this->logger->reveal()
        );
    }

    /**
     * Test resolveSmsHistory with basic arguments (limit, offset, userId).
     * @test
     */
    public function resolveSmsHistoryBasic(): void
    {
        $args = ['limit' => 10, 'offset' => 0, 'userId' => 1];
        $expectedCriteria = ['userId' => 1]; // Non-admin user can only see their own

        $this->smsHistoryRepository->findByCriteria($expectedCriteria, 10, 0)
            ->shouldBeCalledOnce()
            ->willReturn([]); // Return empty array for simplicity

        $result = $this->resolver->resolveSmsHistory($args, null);
        $this->assertIsArray($result);
    }

    /**
     * Test resolveSmsHistory with status filter.
     * @test
     */
    public function resolveSmsHistoryWithStatusFilter(): void
    {
        $args = ['status' => 'SENT', 'userId' => 1];
        $expectedCriteria = ['userId' => 1, 'status' => 'SENT'];

        $this->smsHistoryRepository->findByCriteria($expectedCriteria, 100, 0) // Default limit/offset
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $result = $this->resolver->resolveSmsHistory($args, null);
        $this->assertIsArray($result);
    }

    /**
     * Test resolveSmsHistory with search filter.
     * @test
     */
    public function resolveSmsHistoryWithSearchFilter(): void
    {
        $args = ['search' => '12345', 'userId' => 1];
        $expectedCriteria = ['userId' => 1, 'search' => '12345'];

        $this->smsHistoryRepository->findByCriteria($expectedCriteria, 100, 0)
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $result = $this->resolver->resolveSmsHistory($args, null);
        $this->assertIsArray($result);
    }

    /**
     * Test resolveSmsHistory with segmentId filter.
     * @test
     */
    public function resolveSmsHistoryWithSegmentIdFilter(): void
    {
        $args = ['segmentId' => 5, 'userId' => 1];
        $expectedCriteria = ['userId' => 1, 'segmentId' => 5];

        $this->smsHistoryRepository->findByCriteria($expectedCriteria, 100, 0)
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $result = $this->resolver->resolveSmsHistory($args, null);
        $this->assertIsArray($result);
    }

    /**
     * Test resolveSmsHistory with all filters combined.
     * @test
     */
    public function resolveSmsHistoryWithAllFilters(): void
    {
        $args = [
            'limit' => 20,
            'offset' => 10,
            'userId' => 1,
            'status' => 'FAILED',
            'search' => '999',
            'segmentId' => 7
        ];
        $expectedCriteria = [
            'userId' => 1,
            'status' => 'FAILED',
            'search' => '999',
            'segmentId' => 7
        ];

        $this->smsHistoryRepository->findByCriteria($expectedCriteria, 20, 10)
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $result = $this->resolver->resolveSmsHistory($args, null);
        $this->assertIsArray($result);
    }

    /**
     * Test resolveSmsHistory as admin without userId filter (should fetch all).
     * @test
     */
    public function resolveSmsHistoryAsAdminNoUserId(): void
    {
        // Mock admin user
        $this->userProphecy->isAdmin()->willReturn(true);
        $this->authService->getCurrentUser()->willReturn($this->userProphecy->reveal());

        $args = ['limit' => 10, 'offset' => 5];
        $expectedCriteria = []; // No userId filter for admin when not specified

        $this->smsHistoryRepository->findByCriteria($expectedCriteria, 10, 5)
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $result = $this->resolver->resolveSmsHistory($args, null);
        $this->assertIsArray($result);
    }

    /**
     * Test resolveSmsHistory as admin with specific userId filter.
     * @test
     */
    public function resolveSmsHistoryAsAdminWithUserId(): void
    {
        // Mock admin user
        $this->userProphecy->isAdmin()->willReturn(true);
        $this->authService->getCurrentUser()->willReturn($this->userProphecy->reveal());

        $args = ['userId' => 2]; // Admin querying for user 2
        $expectedCriteria = ['userId' => 2];

        $this->smsHistoryRepository->findByCriteria($expectedCriteria, 100, 0)
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $result = $this->resolver->resolveSmsHistory($args, null);
        $this->assertIsArray($result);
    }

    /**
     * Test resolveSmsHistory throws exception when not authenticated.
     * @test
     */
    public function resolveSmsHistoryThrowsIfNotAuthenticated(): void
    {
        $this->authService->getCurrentUser()->willReturn(null); // Simulate not logged in

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("User not authenticated");

        $this->resolver->resolveSmsHistory([], null);
    }

    /**
     * Test resolveSmsHistory throws exception when non-admin tries to access other user's history.
     * @test
     */
    public function resolveSmsHistoryThrowsIfNonAdminAccessesOtherUser(): void
    {
        // Non-admin user (ID 1) trying to access history for user ID 2
        $args = ['userId' => 2];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Permission denied");

        $this->resolver->resolveSmsHistory($args, null);
    }

    // ==================================
    // Tests for mutateSendSMS
    // ==================================

    /**
     * Test mutateSendSMS successfully.
     * @test
     */
    public function mutateSendSMSSuccessfully(): void
    {
        $userId = 1;
        $phoneNumber = '+2250777104936';
        $message = 'Mutation test message';
        $args = ['phoneNumber' => $phoneNumber, 'message' => $message];
        $serviceResponse = ['outboundSMSMessageRequest' => ['senderName' => 'TestSender', /* ... other data */]];
        $formattedResponse = ['status' => 'SENT', 'details' => 'Mock details']; // Example formatted response

        // Ensure user is authenticated
        $this->authService->getCurrentUser()->shouldBeCalled()->willReturn($this->userProphecy->reveal());
        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);

        // Expect SMSService call
        $this->smsService->sendSMS($phoneNumber, $message, $userId)
            ->shouldBeCalledOnce()
            ->willReturn($serviceResponse);

        // Expect Formatter call
        $this->formatter->formatSmsSendResult($serviceResponse, $phoneNumber)
            ->shouldBeCalledOnce()
            ->willReturn($formattedResponse);

        // Act
        $result = $this->resolver->mutateSendSMS($args, null);

        // Assert
        $this->assertSame($formattedResponse, $result);
    }

    /**
     * Test mutateSendSMS throws exception when not authenticated.
     * @test
     */
    public function mutateSendSMSThrowsIfNotAuthenticated(): void
    {
        $args = ['phoneNumber' => '+2250101010101', 'message' => 'Test'];
        $this->authService->getCurrentUser()->shouldBeCalled()->willReturn(null); // Simulate not logged in

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("User not authenticated");

        // Act
        $this->resolver->mutateSendSMS($args, null);

        // Verify service was not called
        $this->smsService->sendSMS(Argument::any(), Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * Test mutateSendSMS handles exceptions from SMSService.
     * @test
     */
    public function mutateSendSMSHandlesServiceException(): void
    {
        $userId = 1;
        $phoneNumber = '+2250777104936';
        $message = 'Mutation test message';
        $args = ['phoneNumber' => $phoneNumber, 'message' => $message];
        $errorMessage = "Crédits SMS insuffisants.";

        // Ensure user is authenticated
        $this->authService->getCurrentUser()->shouldBeCalled()->willReturn($this->userProphecy->reveal());
        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);

        // Expect SMSService call to throw an exception
        $this->smsService->sendSMS($phoneNumber, $message, $userId)
            ->shouldBeCalledOnce()
            ->willThrow(new Exception($errorMessage));

        // Expect logger call
        $this->logger->error(Argument::containingString($errorMessage), Argument::type('array'))->shouldBeCalled();

        // Expect the resolver to re-throw the exception (or handle it as per its logic)
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($errorMessage);

        // Act
        try {
            $this->resolver->mutateSendSMS($args, null);
        } catch (Exception $e) {
            // Verify formatter was not called
            $this->formatter->formatSmsSendResult(Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
            throw $e; // Re-throw for PHPUnit
        }
    }

    // ==================================
    // Tests for mutateSendBulkSMS
    // ==================================

    /**
     * Test mutateSendBulkSMS successfully.
     * @test
     */
    public function mutateSendBulkSMSSuccessfully(): void
    {
        $userId = 1;
        $phoneNumbers = ['+2250101010101', '+2250202020202'];
        $message = 'Bulk mutation test';
        $args = ['phoneNumbers' => $phoneNumbers, 'message' => $message];
        $serviceResponse = [ // Example structure from SMSServiceTest
            $phoneNumbers[0] => ['status' => 'success', 'response' => ['...']],
            $phoneNumbers[1] => ['status' => 'success', 'response' => ['...']],
        ];
        $formattedResponse = ['status' => 'COMPLETED', 'summary' => ['total' => 2, 'successful' => 2, 'failed' => 0], 'results' => [/* formatted results */]];

        // Auth check
        $this->authService->getCurrentUser()->shouldBeCalled()->willReturn($this->userProphecy->reveal());
        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);

        // Service call
        $this->smsService->sendBulkSMS($phoneNumbers, $message, $userId)
            ->shouldBeCalledOnce()
            ->willReturn($serviceResponse);

        // Formatter call
        $this->formatter->formatBulkSmsResult($serviceResponse)
            ->shouldBeCalledOnce()
            ->willReturn($formattedResponse);

        // Act
        $result = $this->resolver->mutateSendBulkSMS($args, null);

        // Assert
        $this->assertSame($formattedResponse, $result);
    }

    /**
     * Test mutateSendBulkSMS throws exception when not authenticated.
     * @test
     */
    public function mutateSendBulkSMSThrowsIfNotAuthenticated(): void
    {
        $args = ['phoneNumbers' => ['+2250101010101'], 'message' => 'Test'];
        $this->authService->getCurrentUser()->shouldBeCalled()->willReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("User not authenticated");

        $this->resolver->mutateSendBulkSMS($args, null);
        $this->smsService->sendBulkSMS(Argument::any(), Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * Test mutateSendBulkSMS handles exceptions from SMSService.
     * @test
     */
    public function mutateSendBulkSMSHandlesServiceException(): void
    {
        $userId = 1;
        $phoneNumbers = ['+2250101010101'];
        $message = 'Bulk mutation test';
        $args = ['phoneNumbers' => $phoneNumbers, 'message' => $message];
        $errorMessage = "Crédits SMS insuffisants pour envoyer à tous les numéros.";

        // Auth check
        $this->authService->getCurrentUser()->shouldBeCalled()->willReturn($this->userProphecy->reveal());
        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);

        // Service call throws exception
        $this->smsService->sendBulkSMS($phoneNumbers, $message, $userId)
            ->shouldBeCalledOnce()
            ->willThrow(new Exception($errorMessage));

        // Logger call
        $this->logger->error(Argument::containingString($errorMessage), Argument::type('array'))->shouldBeCalled();

        // Expect exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($errorMessage);

        // Act
        try {
            $this->resolver->mutateSendBulkSMS($args, null);
        } catch (Exception $e) {
            $this->formatter->formatBulkSmsResult(Argument::any())->shouldNotHaveBeenCalled();
            throw $e;
        }
    }

    // ==================================
    // Tests for mutateSendSMSToSegment
    // ==================================

    /**
     * Test mutateSendSMSToSegment successfully.
     * @test
     */
    public function mutateSendSMSToSegmentSuccessfully(): void
    {
        $userId = 1;
        $segmentId = 5;
        $message = 'Segment mutation test';
        $args = ['segmentId' => $segmentId, 'message' => $message];
        $serviceResponse = [ // Example structure from SMSServiceTest
            '+2250303030303' => ['status' => 'success', 'response' => ['...']],
            '+2250404040404' => ['status' => 'success', 'response' => ['...']],
        ];
        $formattedResponse = ['status' => 'COMPLETED', 'summary' => ['total' => 2, 'successful' => 2, 'failed' => 0], 'results' => [/* formatted results */]];

        // Auth check
        $this->authService->getCurrentUser()->shouldBeCalled()->willReturn($this->userProphecy->reveal());
        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);

        // Service call
        $this->smsService->sendSMSToSegment($segmentId, $message, $userId)
            ->shouldBeCalledOnce()
            ->willReturn($serviceResponse);

        // Formatter call
        $this->formatter->formatBulkSmsResult($serviceResponse) // Assuming same formatter is used
            ->shouldBeCalledOnce()
            ->willReturn($formattedResponse);

        // Act
        $result = $this->resolver->mutateSendSMSToSegment($args, null);

        // Assert
        $this->assertSame($formattedResponse, $result);
    }

    /**
     * Test mutateSendSMSToSegment throws exception when not authenticated.
     * @test
     */
    public function mutateSendSMSToSegmentThrowsIfNotAuthenticated(): void
    {
        $args = ['segmentId' => 1, 'message' => 'Test'];
        $this->authService->getCurrentUser()->shouldBeCalled()->willReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("User not authenticated");

        $this->resolver->mutateSendSMSToSegment($args, null);
        $this->smsService->sendSMSToSegment(Argument::any(), Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * Test mutateSendSMSToSegment handles exceptions from SMSService.
     * @test
     */
    public function mutateSendSMSToSegmentHandlesServiceException(): void
    {
        $userId = 1;
        $segmentId = 5;
        $message = 'Segment mutation test';
        $args = ['segmentId' => $segmentId, 'message' => $message];
        $errorMessage = "Aucun numéro de téléphone trouvé pour ce segment.";

        // Auth check
        $this->authService->getCurrentUser()->shouldBeCalled()->willReturn($this->userProphecy->reveal());
        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);

        // Service call throws exception
        $this->smsService->sendSMSToSegment($segmentId, $message, $userId)
            ->shouldBeCalledOnce()
            ->willThrow(new Exception($errorMessage));

        // Logger call
        $this->logger->error(Argument::containingString($errorMessage), Argument::type('array'))->shouldBeCalled();

        // Expect exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($errorMessage);

        // Act
        try {
            $this->resolver->mutateSendSMSToSegment($args, null);
        } catch (Exception $e) {
            $this->formatter->formatBulkSmsResult(Argument::any())->shouldNotHaveBeenCalled();
            throw $e;
        }
    }

    // ==================================
    // Tests for mutateSendToAllContacts
    // ==================================

    /**
     * Test mutateSendToAllContacts successfully.
     * @test
     */
    public function mutateSendToAllContactsSuccessfully(): void
    {
        $userId = 1;
        $message = 'All contacts mutation test';
        $args = ['message' => $message];
        $serviceResponse = [ // Example structure from SMSServiceTest
            'status' => 'COMPLETED',
            'summary' => ['total' => 2, 'successful' => 2, 'failed' => 0],
            'results' => [
                ['phoneNumber' => '+2250505050505', 'status' => 'SENT', 'response' => ['...']],
                ['phoneNumber' => '+2250606060606', 'status' => 'SENT', 'response' => ['...']],
            ]
        ];
        // Assuming the service response is already formatted correctly for this mutation
        $formattedResponse = $serviceResponse;

        // Auth check
        $this->authService->getCurrentUser()->shouldBeCalled()->willReturn($this->userProphecy->reveal());
        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);

        // Service call
        $this->smsService->sendToAllContacts($userId, $message)
            ->shouldBeCalledOnce()
            ->willReturn($serviceResponse);

        // No separate formatter call expected if service returns the final structure

        // Act
        $result = $this->resolver->mutateSendToAllContacts($args, null);

        // Assert
        $this->assertSame($formattedResponse, $result);
    }

    /**
     * Test mutateSendToAllContacts throws exception when not authenticated.
     * @test
     */
    public function mutateSendToAllContactsThrowsIfNotAuthenticated(): void
    {
        $args = ['message' => 'Test'];
        $this->authService->getCurrentUser()->shouldBeCalled()->willReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("User not authenticated");

        $this->resolver->mutateSendToAllContacts($args, null);
        $this->smsService->sendToAllContacts(Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * Test mutateSendToAllContacts handles exceptions from SMSService.
     * @test
     */
    public function mutateSendToAllContactsHandlesServiceException(): void
    {
        $userId = 1;
        $message = 'All contacts mutation test';
        $args = ['message' => $message];
        $errorMessage = "Aucun contact trouvé pour cet utilisateur.";

        // Auth check
        $this->authService->getCurrentUser()->shouldBeCalled()->willReturn($this->userProphecy->reveal());
        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);

        // Service call throws exception
        $this->smsService->sendToAllContacts($userId, $message)
            ->shouldBeCalledOnce()
            ->willThrow(new Exception($errorMessage));

        // Logger call
        $this->logger->error(Argument::containingString($errorMessage), Argument::type('array'))->shouldBeCalled();

        // Expect exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($errorMessage);

        // Act
        $this->resolver->mutateSendToAllContacts($args, null);
    }
}
