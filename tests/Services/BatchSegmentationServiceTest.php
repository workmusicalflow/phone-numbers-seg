<?php

namespace Tests\Services;

use App\Models\PhoneNumber;
use App\Services\BatchSegmentationService;
use App\Services\PhoneSegmentationService;
use PHPUnit\Framework\TestCase;

class BatchSegmentationServiceTest extends TestCase
{
    /**
     * @var BatchSegmentationService
     */
    private BatchSegmentationService $service;

    /**
     * @var PhoneSegmentationService
     */
    private PhoneSegmentationService $segmentationService;

    /**
     * Set up the test
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->segmentationService = new PhoneSegmentationService();
        $this->service = new BatchSegmentationService($this->segmentationService);
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

        $result = $this->service->processPhoneNumbers($phoneNumbers);

        // Check that all phone numbers were processed successfully
        $this->assertCount(3, $result['results']);
        $this->assertCount(0, $result['errors']);

        // Check that the first phone number was segmented correctly
        $this->assertInstanceOf(PhoneNumber::class, $result['results'][0]);
        $this->assertEquals('+2250777104936', $result['results'][0]->getNumber());
        $this->assertCount(4, $result['results'][0]->getSegments());

        // Check that the second phone number was segmented correctly
        $this->assertInstanceOf(PhoneNumber::class, $result['results'][1]);
        $this->assertEquals('+2250177104936', $result['results'][1]->getNumber());
        $this->assertCount(4, $result['results'][1]->getSegments());

        // Check that the third phone number was segmented correctly
        $this->assertInstanceOf(PhoneNumber::class, $result['results'][2]);
        $this->assertEquals('+2250577104936', $result['results'][2]->getNumber());
        $this->assertCount(4, $result['results'][2]->getSegments());
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

        $result = $this->service->processPhoneNumbers($phoneNumbers);

        // Check that only valid phone numbers were processed successfully
        $this->assertCount(2, $result['results']);
        $this->assertCount(1, $result['errors']);

        // Check that the first phone number was segmented correctly
        $this->assertInstanceOf(PhoneNumber::class, $result['results'][0]);
        $this->assertEquals('+2250777104936', $result['results'][0]->getNumber());

        // Check that the second phone number was marked as an error
        $this->assertEquals('123456789', $result['errors'][1]['number']);
        $this->assertStringContainsString('Invalid phone number format', $result['errors'][1]['error']);

        // Check that the third phone number was segmented correctly
        $this->assertInstanceOf(PhoneNumber::class, $result['results'][2]);
        $this->assertEquals('+2250577104936', $result['results'][2]->getNumber());
    }

    /**
     * Test formatting results for API response
     */
    public function testFormatResults()
    {
        // Create a sample result
        $phoneNumber1 = new PhoneNumber('+2250777104936');
        $phoneNumber1 = $this->segmentationService->segmentPhoneNumber($phoneNumber1);

        $phoneNumber2 = new PhoneNumber('+2250177104936');
        $phoneNumber2 = $this->segmentationService->segmentPhoneNumber($phoneNumber2);

        $processResults = [
            'results' => [
                0 => $phoneNumber1,
                2 => $phoneNumber2
            ],
            'errors' => [
                1 => [
                    'number' => '123456789',
                    'error' => 'Invalid phone number format'
                ]
            ]
        ];

        $formattedResults = $this->service->formatResults($processResults);

        // Check the structure of the formatted results
        $this->assertArrayHasKey('results', $formattedResults);
        $this->assertArrayHasKey('errors', $formattedResults);
        $this->assertArrayHasKey('summary', $formattedResults);

        // Check the summary
        $this->assertEquals(3, $formattedResults['summary']['total']);
        $this->assertEquals(2, $formattedResults['summary']['successful']);
        $this->assertEquals(1, $formattedResults['summary']['failed']);

        // Check the results
        $this->assertIsArray($formattedResults['results'][0]);
        $this->assertEquals('+2250777104936', $formattedResults['results'][0]['number']);
        $this->assertIsArray($formattedResults['results'][0]['segments']);

        // Check the errors
        $this->assertEquals('123456789', $formattedResults['errors'][1]['number']);
        $this->assertEquals('Invalid phone number format', $formattedResults['errors'][1]['error']);
    }
}
