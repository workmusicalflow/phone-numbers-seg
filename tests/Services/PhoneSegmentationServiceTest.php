<?php

namespace Tests\Services;

use App\Models\PhoneNumber;
use App\Models\Segment;
use App\Services\Factories\SegmentationStrategyFactory;
use App\Services\Interfaces\PhoneNumberValidatorInterface;
use App\Services\Interfaces\SegmentationStrategyInterface;
use App\Services\PhoneSegmentationService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PhoneSegmentationServiceTest extends TestCase
{
    /**
     * @var PhoneSegmentationService
     */
    private PhoneSegmentationService $service;

    /**
     * @var PhoneNumberValidatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $validatorMock;

    /**
     * @var SegmentationStrategyFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $strategyFactoryMock;

    /**
     * @var SegmentationStrategyInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $strategyMock;

    /**
     * Set up the test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks
        $this->validatorMock = $this->createMock(PhoneNumberValidatorInterface::class);
        $this->strategyFactoryMock = $this->createMock(SegmentationStrategyFactory::class);
        $this->strategyMock = $this->createMock(SegmentationStrategyInterface::class);

        // Configure validator mock to return true by default
        $this->validatorMock->method('validate')->willReturn(true);

        // Configure strategy factory mock to return the strategy mock
        $this->strategyFactoryMock->method('getStrategy')->willReturn($this->strategyMock);

        // Create the service with mocks
        $this->service = new PhoneSegmentationService(
            $this->validatorMock,
            $this->strategyFactoryMock
        );
    }

    /**
     * Test segmentation with a valid phone number
     */
    public function testSegmentationWithValidNumber()
    {
        // Create a phone number
        $phoneNumber = new PhoneNumber('+2250777104936');

        // Create a segmented phone number to return from the strategy
        $segmentedPhoneNumber = clone $phoneNumber;
        $segmentedPhoneNumber->addTechnicalSegment(new Segment(Segment::TYPE_COUNTRY_CODE, '225'));
        $segmentedPhoneNumber->addTechnicalSegment(new Segment(Segment::TYPE_OPERATOR_CODE, '07'));
        $segmentedPhoneNumber->addTechnicalSegment(new Segment(Segment::TYPE_SUBSCRIBER_NUMBER, '77104936'));
        $segmentedPhoneNumber->addTechnicalSegment(new Segment(Segment::TYPE_OPERATOR_NAME, 'Orange CI'));

        // Configure the strategy mock to return the segmented phone number
        $this->strategyMock->method('segment')->willReturn($segmentedPhoneNumber);

        // Configure the strategy factory to expect a call with the country code
        $this->strategyFactoryMock->expects($this->once())
            ->method('getStrategy')
            ->with('225');

        // Call the service
        $result = $this->service->segmentPhoneNumber($phoneNumber);

        // Assert that the result is the segmented phone number
        $this->assertSame($segmentedPhoneNumber, $result);

        // Check the segments
        $segments = $result->getSegments();
        $this->assertCount(4, $segments);

        // Check country code segment
        $this->assertEquals('country_code', $segments[0]->getSegmentType());
        $this->assertEquals('225', $segments[0]->getValue());

        // Check operator code segment
        $this->assertEquals('operator_code', $segments[1]->getSegmentType());
        $this->assertEquals('07', $segments[1]->getValue());

        // Check subscriber number segment
        $this->assertEquals('subscriber_number', $segments[2]->getSegmentType());
        $this->assertEquals('77104936', $segments[2]->getValue());

        // Check operator name segment
        $this->assertEquals('operator_name', $segments[3]->getSegmentType());
        $this->assertEquals('Orange CI', $segments[3]->getValue());
    }

    /**
     * Test segmentation with an invalid number
     */
    public function testSegmentationWithInvalidNumber()
    {
        // Configure the validator mock to return false for this test
        $this->validatorMock->method('validate')->willReturn(false);

        // Expect an exception
        $this->expectException(InvalidArgumentException::class);

        // Create a phone number
        $phoneNumber = new PhoneNumber('+123456789');

        // Call the service
        $this->service->segmentPhoneNumber($phoneNumber);

        // The strategy factory should not be called
        $this->strategyFactoryMock->expects($this->never())->method('getStrategy');
    }

    /**
     * Test that the correct country code is extracted
     */
    public function testCountryCodeExtraction()
    {
        // Create phone numbers with different formats
        $phoneNumber1 = new PhoneNumber('+2250777104936'); // International format with +
        $phoneNumber2 = new PhoneNumber('002250777104936'); // International format with 00
        $phoneNumber3 = new PhoneNumber('0777104936'); // Local format

        // Configure the strategy factory to expect calls with the country code
        $this->strategyFactoryMock->expects($this->exactly(3))
            ->method('getStrategy')
            ->withConsecutive(['225'], ['225'], ['225']);

        // Call the service for each phone number
        $this->service->segmentPhoneNumber($phoneNumber1);
        $this->service->segmentPhoneNumber($phoneNumber2);
        $this->service->segmentPhoneNumber($phoneNumber3);
    }
}
