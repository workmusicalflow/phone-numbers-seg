<?php

namespace Tests\Services;

use App\Exceptions\BatchProcessingException;
use App\Exceptions\RepositoryException;
use App\Models\PhoneNumber;
use App\Repositories\PhoneNumberRepository;
use App\Repositories\TechnicalSegmentRepository;
use App\Services\BatchSegmentationService;
use App\Services\Formatters\BatchResultFormatter;
use App\Services\Formatters\BatchResultFormatterInterface;
use App\Services\Interfaces\BatchSegmentationServiceInterface;
use App\Services\Interfaces\PhoneSegmentationServiceInterface;
use PHPUnit\Framework\TestCase;

class BatchSegmentationServiceTest extends TestCase
{
    /**
     * @var BatchSegmentationServiceInterface
     */
    private BatchSegmentationServiceInterface $service;

    /**
     * @var PhoneSegmentationServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $segmentationService;

    /**
     * @var PhoneNumberRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $phoneNumberRepository;

    /**
     * @var TechnicalSegmentRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $technicalSegmentRepository;

    /**
     * @var BatchResultFormatterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resultFormatter;

    /**
     * Set up the test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for dependencies
        $this->segmentationService = $this->createMock(PhoneSegmentationServiceInterface::class);
        $this->phoneNumberRepository = $this->createMock(PhoneNumberRepository::class);
        $this->technicalSegmentRepository = $this->createMock(TechnicalSegmentRepository::class);
        $this->resultFormatter = $this->createMock(BatchResultFormatterInterface::class);

        // Create the service with mocked dependencies
        $this->service = new BatchSegmentationService(
            $this->segmentationService,
            $this->phoneNumberRepository,
            $this->technicalSegmentRepository,
            $this->resultFormatter
        );
    }

    /**
     * Test processing multiple valid phone numbers
     */
    public function testProcessValidPhoneNumbers()
    {
        $phoneNumbers = [
            '+2250777104936',
            '002250177104936',
            '0577104936'
        ];

        // Create sample phone number objects
        $phoneNumber1 = new PhoneNumber('+2250777104936');
        $phoneNumber2 = new PhoneNumber('+2250177104936');
        $phoneNumber3 = new PhoneNumber('+2250577104936');

        // Configure mocks
        $this->segmentationService->expects($this->exactly(3))
            ->method('segmentPhoneNumber')
            ->willReturnOnConsecutiveCalls($phoneNumber1, $phoneNumber2, $phoneNumber3);

        // Execute the method
        $result = $this->service->processPhoneNumbers($phoneNumbers);

        // Check that all phone numbers were processed successfully
        $this->assertCount(3, $result['results']);
        $this->assertCount(0, $result['errors']);

        // Check that the phone numbers were segmented correctly
        $this->assertSame($phoneNumber1, $result['results'][0]);
        $this->assertSame($phoneNumber2, $result['results'][1]);
        $this->assertSame($phoneNumber3, $result['results'][2]);
    }

    /**
     * Test processing a mix of valid and invalid phone numbers
     */
    public function testProcessMixedPhoneNumbers()
    {
        $phoneNumbers = [
            '+2250777104936',  // Valid
            '123456789',       // Invalid
            '0577104936'       // Valid
        ];

        // Create sample phone number objects
        $phoneNumber1 = new PhoneNumber('+2250777104936');
        $phoneNumber3 = new PhoneNumber('+2250577104936');

        // Configure mocks
        $this->segmentationService->expects($this->exactly(2))
            ->method('segmentPhoneNumber')
            ->willReturnOnConsecutiveCalls($phoneNumber1, $phoneNumber3);

        // Execute the method
        $result = $this->service->processPhoneNumbers($phoneNumbers);

        // Check that only valid phone numbers were processed successfully
        $this->assertCount(2, $result['results']);
        $this->assertCount(1, $result['errors']);

        // Check that the first phone number was segmented correctly
        $this->assertSame($phoneNumber1, $result['results'][0]);

        // Check that the second phone number was marked as an error
        $this->assertEquals('123456789', $result['errors'][1]['number']);
        $this->assertStringContainsString('Invalid phone number format', $result['errors'][1]['error']);

        // Check that the third phone number was segmented correctly
        $this->assertSame($phoneNumber3, $result['results'][2]);
    }

    /**
     * Test processing and saving phone numbers
     */
    public function testProcessAndSavePhoneNumbers()
    {
        $phoneNumbers = [
            '+2250777104936',
            '002250177104936'
        ];

        // Create sample phone number objects
        $phoneNumber1 = new PhoneNumber('+2250777104936');
        $phoneNumber2 = new PhoneNumber('+2250177104936');

        // Configure mocks
        $this->segmentationService->expects($this->exactly(2))
            ->method('segmentPhoneNumber')
            ->willReturnOnConsecutiveCalls($phoneNumber1, $phoneNumber2);

        $this->phoneNumberRepository->expects($this->exactly(2))
            ->method('findByNumber')
            ->willReturn(null);

        $this->phoneNumberRepository->expects($this->exactly(2))
            ->method('save')
            ->willReturnOnConsecutiveCalls($phoneNumber1, $phoneNumber2);

        // Execute the method
        $result = $this->service->processAndSavePhoneNumbers($phoneNumbers);

        // Check that all phone numbers were processed and saved successfully
        $this->assertCount(2, $result['results']);
        $this->assertCount(0, $result['errors']);

        // Check that the phone numbers were saved correctly
        $this->assertSame($phoneNumber1, $result['results'][0]);
        $this->assertSame($phoneNumber2, $result['results'][1]);
    }

    /**
     * Test handling of repository exceptions
     */
    public function testRepositoryExceptionHandling()
    {
        $phoneNumbers = [
            '+2250777104936'
        ];

        // Create sample phone number object
        $phoneNumber = new PhoneNumber('+2250777104936');

        // Configure mocks
        $this->segmentationService->expects($this->once())
            ->method('segmentPhoneNumber')
            ->willReturn($phoneNumber);

        $this->phoneNumberRepository->expects($this->once())
            ->method('findByNumber')
            ->willReturn(null);

        $this->phoneNumberRepository->expects($this->once())
            ->method('save')
            ->willThrowException(new \Exception('Database error'));

        // Expect a RepositoryException to be thrown
        $this->expectException(RepositoryException::class);
        $this->expectExceptionMessage('Failed to save phone number: Database error');

        // Execute the method
        $this->service->processAndSavePhoneNumbers($phoneNumbers);
    }

    /**
     * Test handling of all phone numbers failing
     */
    public function testAllPhoneNumbersFailing()
    {
        $phoneNumbers = [
            '123',  // Invalid
            '456'   // Invalid
        ];

        // Expect a BatchProcessingException to be thrown
        $this->expectException(BatchProcessingException::class);
        $this->expectExceptionMessage('All phone numbers failed to process');

        // Execute the method
        $this->service->processPhoneNumbers($phoneNumbers);
    }
}
