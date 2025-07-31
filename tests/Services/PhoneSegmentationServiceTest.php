<?php

namespace Tests\Services;

use App\Entities\PhoneNumber; // Use Doctrine Entity
use App\Entities\Segment; // Use Doctrine Entity
use App\Services\Factories\SegmentationStrategyFactory;
use App\Services\Interfaces\PhoneNumberValidatorInterface;
use App\Services\Interfaces\SegmentationStrategyInterface;
use App\Services\PhoneSegmentationService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait; // Use Prophecy
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Test class for PhoneSegmentationService
 *
 * @covers \App\Services\PhoneSegmentationService
 */
class PhoneSegmentationServiceTest extends TestCase
{
    use ProphecyTrait; // Use Prophecy Trait

    private ObjectProphecy $validatorProphecy;
    private ObjectProphecy $strategyFactoryProphecy;
    private ObjectProphecy $strategyProphecy;
    private PhoneSegmentationService $service;

    /**
     * Set up the test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create prophecies
        $this->validatorProphecy = $this->prophesize(PhoneNumberValidatorInterface::class);
        $this->strategyFactoryProphecy = $this->prophesize(SegmentationStrategyFactory::class);
        $this->strategyProphecy = $this->prophesize(SegmentationStrategyInterface::class);

        // Configure validator prophecy to return true by default
        $this->validatorProphecy->isValid(Argument::type('string'))->willReturn(true);

        // Configure strategy factory prophecy to return the strategy prophecy
        $this->strategyFactoryProphecy->getStrategy(Argument::type('string'))->willReturn($this->strategyProphecy->reveal());

        // Create the service with revealed prophecies
        $this->service = new PhoneSegmentationService(
            $this->validatorProphecy->reveal(),
            $this->strategyFactoryProphecy->reveal()
        );
    }

    /**
     * Test segmentation with a valid phone number
     * @test
     */
    public function segmentationWithValidNumber(): void
    {
        $numberString = '+2250777104936';
        $countryCode = '225';
        // Create a real PhoneNumber entity for input
        $phoneNumber = new PhoneNumber();
        $phoneNumber->setNumber($numberString); // Use the setter which normalizes

        // Create a real PhoneNumber entity for the expected result
        $segmentedPhoneNumber = new PhoneNumber();
        $segmentedPhoneNumber->setNumber($numberString); // Start with the same number
        // Add segments (using real Segment entities)
        $segmentedPhoneNumber->addTechnicalSegment((new Segment())->setSegmentType(Segment::TYPE_COUNTRY_CODE)->setValue('225'));
        $segmentedPhoneNumber->addTechnicalSegment((new Segment())->setSegmentType(Segment::TYPE_OPERATOR_CODE)->setValue('07'));
        $segmentedPhoneNumber->addTechnicalSegment((new Segment())->setSegmentType(Segment::TYPE_SUBSCRIBER_NUMBER)->setValue('77104936'));
        $segmentedPhoneNumber->addTechnicalSegment((new Segment())->setSegmentType(Segment::TYPE_OPERATOR_NAME)->setValue('Orange CI'));


        // Configure the validator prophecy
        $this->validatorProphecy->isValid($numberString)->shouldBeCalledOnce()->willReturn(true);

        // Configure the strategy factory prophecy
        $this->strategyFactoryProphecy->getStrategy($countryCode)->shouldBeCalledOnce()->willReturn($this->strategyProphecy->reveal());

        // Configure the strategy prophecy to return the segmented phone number
        // Use Argument::exact($phoneNumber) to ensure the correct entity instance is passed
        $this->strategyProphecy->segment(Argument::exact($phoneNumber))->shouldBeCalledOnce()->willReturn($segmentedPhoneNumber);

        // Call the service
        $result = $this->service->segmentPhoneNumber($phoneNumber);

        // Assert that the result is the segmented phone number
        $this->assertSame($segmentedPhoneNumber, $result);

        // Check the segments (using the entity's getter)
        $segments = $result->getTechnicalSegments(); // Use the correct getter
        $this->assertCount(4, $segments);

        // Check country code segment
        $this->assertEquals(Segment::TYPE_COUNTRY_CODE, $segments[0]->getSegmentType());
        $this->assertEquals('225', $segments[0]->getValue());

        // Check operator code segment
        $this->assertEquals(Segment::TYPE_OPERATOR_CODE, $segments[1]->getSegmentType());
        $this->assertEquals('07', $segments[1]->getValue());

        // Check subscriber number segment
        $this->assertEquals(Segment::TYPE_SUBSCRIBER_NUMBER, $segments[2]->getSegmentType());
        $this->assertEquals('77104936', $segments[2]->getValue());

        // Check operator name segment
        $this->assertEquals(Segment::TYPE_OPERATOR_NAME, $segments[3]->getSegmentType());
        $this->assertEquals('Orange CI', $segments[3]->getValue());
    }

    /**
     * Test segmentation with an invalid number
     * @test
     */
    public function segmentationWithInvalidNumber(): void
    {
        $invalidNumberString = '+123456789';
        // Create a real PhoneNumber entity
        $phoneNumber = new PhoneNumber();
        $phoneNumber->setNumber($invalidNumberString);

        // Configure the validator prophecy to return false for this test
        $this->validatorProphecy->isValid($invalidNumberString)->shouldBeCalledOnce()->willReturn(false);

        // Expect an exception
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid phone number format: ' . $invalidNumberString);

        // Call the service
        $this->service->segmentPhoneNumber($phoneNumber);

        // The strategy factory should not be called
        $this->strategyFactoryProphecy->getStrategy(Argument::any())->shouldNotHaveBeenCalled();
        $this->strategyProphecy->segment(Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * Test that the correct country code is extracted and passed to the factory.
     * @dataProvider countryCodeExtractionProvider
     * @test
     */
    public function countryCodeExtraction(string $inputNumber, string $expectedCountryCode): void
    {
        // Create a real PhoneNumber entity
        $phoneNumber = new PhoneNumber();
        $phoneNumber->setNumber($inputNumber); // Use setter for normalization

        // Configure validator
        $this->validatorProphecy->isValid($phoneNumber->getNumber())->shouldBeCalledOnce()->willReturn(true);

        // Configure the strategy factory to expect a call with the correct country code
        $this->strategyFactoryProphecy->getStrategy($expectedCountryCode)->shouldBeCalledOnce()->willReturn($this->strategyProphecy->reveal());

        // Configure the strategy mock (return value doesn't matter much here)
        $this->strategyProphecy->segment($phoneNumber)->shouldBeCalledOnce()->willReturn($phoneNumber); // Return input for simplicity

        // Call the service
        $this->service->segmentPhoneNumber($phoneNumber);
    }

    /**
     * Data provider for country code extraction tests.
     */
    public static function countryCodeExtractionProvider(): array
    {
        return [
            'International with +' => ['+2250777104936', '225'],
            'International with 00' => ['002250777104936', '225'], // Normalization happens in entity setter
            'Local CI (Implicit 225)' => ['0777104936', '225'], // Normalization happens in entity setter
            'Another Country +' => ['+33612345678', '336'],
            'Another Country 00' => ['00447123456789', '447'], // Normalization happens in entity setter
        ];
    }

    /**
     * Test segmentation when the strategy itself throws an exception.
     * @test
     */
    public function segmentationHandlesStrategyException(): void
    {
        $numberString = '+2250102030405';
        $countryCode = '225';
        $errorMessage = "Strategy failed!";
        $phoneNumber = new PhoneNumber();
        $phoneNumber->setNumber($numberString);

        // Configure validator
        $this->validatorProphecy->isValid($numberString)->shouldBeCalledOnce()->willReturn(true);

        // Configure factory
        $this->strategyFactoryProphecy->getStrategy($countryCode)->shouldBeCalledOnce()->willReturn($this->strategyProphecy->reveal());

        // Configure strategy to throw an exception
        $this->strategyProphecy->segment($phoneNumber)->shouldBeCalledOnce()->willThrow(new \RuntimeException($errorMessage));

        // Expect the same exception to bubble up
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($errorMessage);

        // Call the service
        $this->service->segmentPhoneNumber($phoneNumber);
    }

    /**
     * Test segmentation uses default country code when extraction is ambiguous.
     * @test
     */
    public function segmentationUsesDefaultCountryCodeForAmbiguousNumber(): void
    {
        // This number format won't match '+' or '00' prefix, and isn't a typical local '0' start
        // The PhoneNumber entity's normalizeNumber might still change it,
        // but the service's extractCountryCode logic relies on the normalized format.
        // Let's assume normalization results in a number the service defaults for.
        $ambiguousNumberString = '8881234567'; // Example of a number not starting with +, 00, or 0
        $defaultCountryCode = '225'; // Expected default

        $phoneNumber = new PhoneNumber();
        $phoneNumber->setNumber($ambiguousNumberString); // Normalization happens here

        // Assume validator passes the (potentially normalized) number
        $this->validatorProphecy->isValid($phoneNumber->getNumber())->shouldBeCalledOnce()->willReturn(true);

        // Expect factory to be called with the default country code
        $this->strategyFactoryProphecy->getStrategy($defaultCountryCode)->shouldBeCalledOnce()->willReturn($this->strategyProphecy->reveal());

        // Configure strategy mock
        $this->strategyProphecy->segment($phoneNumber)->shouldBeCalledOnce()->willReturn($phoneNumber);

        // Call the service
        $this->service->segmentPhoneNumber($phoneNumber);
    }
}
