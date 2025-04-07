<?php

namespace Tests\Services;

use App\Models\PhoneNumber;
use App\Models\Segment;
use App\Repositories\PhoneNumberRepository;
use App\Repositories\TechnicalSegmentRepository;
use App\Services\ChainOfResponsibilityPhoneSegmentationService;
use App\Services\Factories\SegmentationHandlerFactory;
use App\Services\Handlers\CountryCodeHandler;
use App\Services\Interfaces\PhoneNumberValidatorInterface;
use App\Services\Interfaces\SegmentationHandlerInterface;
use PHPUnit\Framework\TestCase;

class ChainOfResponsibilityPhoneSegmentationServiceTest extends TestCase
{
    private $validator;
    private $handlerFactory;
    private $phoneNumberRepository;
    private $segmentRepository;
    private $service;
    private $handlerChain;

    protected function setUp(): void
    {
        // Create mocks
        $this->validator = $this->createMock(PhoneNumberValidatorInterface::class);
        $this->handlerFactory = $this->createMock(SegmentationHandlerFactory::class);
        $this->phoneNumberRepository = $this->createMock(PhoneNumberRepository::class);
        $this->segmentRepository = $this->createMock(TechnicalSegmentRepository::class);
        $this->handlerChain = $this->createMock(SegmentationHandlerInterface::class);

        // Create the service
        $this->service = new ChainOfResponsibilityPhoneSegmentationService(
            $this->validator,
            $this->handlerFactory,
            $this->phoneNumberRepository,
            $this->segmentRepository
        );
    }

    public function testSegmentPhoneNumberWithValidNumber()
    {
        // Create a phone number
        $phoneNumber = new PhoneNumber('+2250707070707');

        // Configure mocks
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($phoneNumber)
            ->willReturn(true);

        $this->handlerFactory->expects($this->once())
            ->method('createHandlerChain')
            ->willReturn($this->handlerChain);

        // Create a segment for testing
        $segment = new Segment(Segment::TYPE_COUNTRY_CODE, '225');
        $phoneNumberWithSegment = clone $phoneNumber;
        $phoneNumberWithSegment->addTechnicalSegment($segment);

        $this->handlerChain->expects($this->once())
            ->method('handle')
            ->with($phoneNumber)
            ->willReturn($phoneNumberWithSegment);

        // Call the method
        $result = $this->service->segmentPhoneNumber($phoneNumber);

        // Assert
        $this->assertSame($phoneNumberWithSegment, $result);
        $this->assertCount(1, $result->getTechnicalSegments());
        $this->assertEquals(Segment::TYPE_COUNTRY_CODE, $result->getTechnicalSegments()[0]->getSegmentType());
        $this->assertEquals('225', $result->getTechnicalSegments()[0]->getValue());
    }

    public function testSegmentPhoneNumberWithInvalidNumber()
    {
        // Create a phone number
        $phoneNumber = new PhoneNumber('invalid');

        // Configure mocks
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($phoneNumber)
            ->willReturn(false);

        // The handler chain should not be created for invalid numbers
        $this->handlerFactory->expects($this->never())
            ->method('createHandlerChain');

        // Call the method
        $result = $this->service->segmentPhoneNumber($phoneNumber);

        // Assert
        $this->assertSame($phoneNumber, $result);
        $this->assertCount(0, $result->getTechnicalSegments());
    }
}
