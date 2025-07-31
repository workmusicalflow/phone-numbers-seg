<?php

namespace Tests\Services;

use App\Services\SMSHistoryService;
use App\Repositories\Interfaces\SMSHistoryRepositoryInterface;
use App\Entities\SMSHistory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;
use DateTime;
use Psr\Log\LoggerInterface; // Added LoggerInterface for consistency if needed later

/**
 * Test class for SMSHistoryService
 *
 * @covers \App\Services\SMSHistoryService
 */
class SMSHistoryServiceTest extends TestCase
{
    use ProphecyTrait;

    private $smsHistoryRepository;
    private $service;
    // private $logger; // Uncomment if logger is added to the service

    protected function setUp(): void
    {
        $this->smsHistoryRepository = $this->prophesize(SMSHistoryRepositoryInterface::class);
        // $this->logger = $this->prophesize(LoggerInterface::class); // Uncomment if logger is added

        // Pass mocked dependencies to the constructor
        $this->service = new SMSHistoryService(
            $this->smsHistoryRepository->reveal()
            // $this->logger->reveal() // Uncomment if logger is added
        );
    }

    /**
     * Test recording SMS history.
     * @test
     */
    public function recordSMSHistorySuccessfully(): void
    {
        $phoneNumber = '+2250102030405';
        $message = 'Test message';
        $status = 'SENT';
        $senderAddress = 'TestSender';
        $senderName = 'Test Name';
        $messageId = 'msg123';
        $errorMessage = null;
        $phoneNumberId = 1;
        $segmentId = 2;

        // Mock the entity that will be returned by the save method
        $savedHistory = $this->prophesize(SMSHistory::class);
        $savedHistory->getId()->willReturn(101); // Example ID
        // Mock other getters if needed for assertions on the returned object
        $savedHistory->getPhoneNumber()->willReturn($phoneNumber);
        $savedHistory->getMessage()->willReturn($message);
        $savedHistory->getStatus()->willReturn($status);
        $savedHistory->getCreatedAt()->willReturn(new DateTime());


        // Expect the save method to be called once with an SMSHistory entity
        $this->smsHistoryRepository->save(Argument::type(SMSHistory::class))
            ->shouldBeCalledOnce()
            ->will(function ($args) use ($savedHistory, $phoneNumber, $message, $status, $senderAddress, $senderName, $messageId, $errorMessage, $phoneNumberId, $segmentId) {
                // Basic check if the passed entity has the correct data (optional but good)
                $entity = $args[0];
                $this->assertInstanceOf(SMSHistory::class, $entity);
                $this->assertEquals($phoneNumber, $entity->getPhoneNumber());
                $this->assertEquals($message, $entity->getMessage());
                $this->assertEquals($status, $entity->getStatus());
                $this->assertEquals($senderAddress, $entity->getSenderAddress());
                $this->assertEquals($senderName, $entity->getSenderName());
                $this->assertEquals($messageId, $entity->getMessageId());
                $this->assertEquals($errorMessage, $entity->getErrorMessage());
                $this->assertEquals($phoneNumberId, $entity->getPhoneNumberId());
                $this->assertEquals($segmentId, $entity->getSegmentId());
                $this->assertInstanceOf(DateTime::class, $entity->getCreatedAt());

                return $savedHistory->reveal(); // Return the mocked saved entity
            });

        $result = $this->service->recordSMSHistory(
            $phoneNumber,
            $message,
            $status,
            $senderAddress,
            $senderName,
            $messageId,
            $errorMessage,
            $phoneNumberId,
            $segmentId
        );

        $this->assertInstanceOf(SMSHistory::class, $result);
        $this->assertEquals(101, $result->getId()); // Check if the returned object is the one from the repo mock
    }

    /**
     * Test getting history by phone number.
     * @test
     */
    public function getHistoryByPhoneNumber(): void
    {
        $phoneNumber = '+2250708091011';
        $limit = 10;
        $offset = 0;

        $this->smsHistoryRepository->findByPhoneNumber($phoneNumber, $limit, $offset)
            ->shouldBeCalledOnce()
            ->willReturn([]); // Return empty array for simplicity

        $result = $this->service->getHistoryByPhoneNumber($phoneNumber, $limit, $offset);
        $this->assertIsArray($result);
    }

    // Add more tests for other methods like:
    // - updateSegmentIdForPhoneNumbers
    // - getHistoryByStatus
    // - getHistoryBySegmentId
    // - getAllHistory
    // - getHistoryByUserId
    // - getHistoryCount
    // - getHistoryCountByUserId
}
