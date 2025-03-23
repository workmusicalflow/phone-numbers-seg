<?php

namespace Tests\Models;

use App\Models\Segment;
use PHPUnit\Framework\TestCase;

class SegmentTest extends TestCase
{
    /**
     * Test segment creation with all parameters
     */
    public function testCreationWithAllParameters()
    {
        $segment = new Segment('country_code', '225', 1, 1);

        $this->assertEquals('country_code', $segment->getSegmentType());
        $this->assertEquals('225', $segment->getValue());
        $this->assertEquals(1, $segment->getPhoneNumberId());
        $this->assertEquals(1, $segment->getId());
    }

    /**
     * Test segment creation with minimal parameters
     */
    public function testCreationWithMinimalParameters()
    {
        $segment = new Segment('operator_code', '07');

        $this->assertEquals('operator_code', $segment->getSegmentType());
        $this->assertEquals('07', $segment->getValue());
        $this->assertEquals(0, $segment->getPhoneNumberId());
        $this->assertNull($segment->getId());
    }

    /**
     * Test segment setters
     */
    public function testSetters()
    {
        $segment = new Segment('country_code', '225');

        $segment->setId(2);
        $segment->setPhoneNumberId(3);
        $segment->setSegmentType('operator_name');
        $segment->setValue('Orange CI');

        $this->assertEquals(2, $segment->getId());
        $this->assertEquals(3, $segment->getPhoneNumberId());
        $this->assertEquals('operator_name', $segment->getSegmentType());
        $this->assertEquals('Orange CI', $segment->getValue());
    }

    /**
     * Test segment to array conversion
     */
    public function testToArray()
    {
        $segment = new Segment('country_code', '225', 1, 1);
        $array = $segment->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(1, $array['id']);
        $this->assertEquals(1, $array['phoneNumberId']);
        $this->assertEquals('country_code', $array['segmentType']);
        $this->assertEquals('225', $array['value']);
    }
}
