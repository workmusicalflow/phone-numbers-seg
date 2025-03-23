<?php

namespace Tests\Services;

use App\Models\PhoneNumber;
use App\Services\PhoneSegmentationService;
use PHPUnit\Framework\TestCase;

class PhoneSegmentationServiceTest extends TestCase
{
    /**
     * @var PhoneSegmentationService
     */
    private PhoneSegmentationService $service;

    /**
     * Set up the test
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PhoneSegmentationService();
    }

    /**
     * Test segmentation with international format (+225)
     */
    public function testSegmentationWithPlusPrefix()
    {
        $phoneNumber = new PhoneNumber('+2250777104936');
        $result = $this->service->segmentPhoneNumber($phoneNumber);

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
        $this->assertEquals('MTN CI', $segments[3]->getValue());
    }

    /**
     * Test segmentation with international format (00225)
     */
    public function testSegmentationWith00Prefix()
    {
        $phoneNumber = new PhoneNumber('002250777104936');
        $result = $this->service->segmentPhoneNumber($phoneNumber);

        $segments = $result->getSegments();
        $this->assertCount(4, $segments);

        // Check country code segment
        $this->assertEquals('country_code', $segments[0]->getSegmentType());
        $this->assertEquals('225', $segments[0]->getValue());
    }

    /**
     * Test segmentation with local format
     */
    public function testSegmentationWithLocalFormat()
    {
        $phoneNumber = new PhoneNumber('0777104936');
        $result = $this->service->segmentPhoneNumber($phoneNumber);

        $segments = $result->getSegments();
        $this->assertCount(4, $segments);

        // Check country code segment
        $this->assertEquals('country_code', $segments[0]->getSegmentType());
        $this->assertEquals('225', $segments[0]->getValue());
    }

    /**
     * Test segmentation with invalid number
     */
    public function testSegmentationWithInvalidNumber()
    {
        $this->expectException(\InvalidArgumentException::class);

        $phoneNumber = new PhoneNumber('+123456789');
        $this->service->segmentPhoneNumber($phoneNumber);
    }

    /**
     * Test segmentation with different operator codes
     */
    public function testSegmentationWithDifferentOperators()
    {
        // Test Orange CI
        $phoneNumber = new PhoneNumber('+2250177104936');
        $result = $this->service->segmentPhoneNumber($phoneNumber);
        $segments = $result->getSegments();
        $this->assertEquals('operator_name', $segments[3]->getSegmentType());
        $this->assertEquals('Orange CI', $segments[3]->getValue());

        // Test MTN CI
        $phoneNumber = new PhoneNumber('+2250577104936');
        $result = $this->service->segmentPhoneNumber($phoneNumber);
        $segments = $result->getSegments();
        $this->assertEquals('operator_name', $segments[3]->getSegmentType());
        $this->assertEquals('MTN CI', $segments[3]->getValue());

        // Test Moov Africa
        $phoneNumber = new PhoneNumber('+2250977104936');
        $result = $this->service->segmentPhoneNumber($phoneNumber);
        $segments = $result->getSegments();
        $this->assertEquals('operator_name', $segments[3]->getSegmentType());
        $this->assertEquals('Moov Africa', $segments[3]->getValue());
    }
}
