<?php

namespace Tests\Models;

use App\Models\PhoneNumber;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    /**
     * Test phone number normalization with international format (+225)
     */
    public function testNormalizationWithPlusPrefix()
    {
        $phoneNumber = new PhoneNumber('+2250777104936');
        $this->assertEquals('+2250777104936', $phoneNumber->getNumber());
    }

    /**
     * Test phone number normalization with international format (00225)
     */
    public function testNormalizationWith00Prefix()
    {
        $phoneNumber = new PhoneNumber('002250777104936');
        $this->assertEquals('+2250777104936', $phoneNumber->getNumber());
    }

    /**
     * Test phone number normalization with local format
     */
    public function testNormalizationWithLocalFormat()
    {
        $phoneNumber = new PhoneNumber('0777104936');
        $this->assertEquals('+2250777104936', $phoneNumber->getNumber());
    }

    /**
     * Test phone number validation with valid number
     */
    public function testValidationWithValidNumber()
    {
        $phoneNumber = new PhoneNumber('+2250777104936');
        $this->assertTrue($phoneNumber->isValid());
    }

    /**
     * Test phone number validation with invalid number
     */
    public function testValidationWithInvalidNumber()
    {
        $phoneNumber = new PhoneNumber('+123456789');
        $this->assertFalse($phoneNumber->isValid());
    }

    /**
     * Test phone number to array conversion
     */
    public function testToArray()
    {
        $phoneNumber = new PhoneNumber('+2250777104936', 1, '2023-01-01 00:00:00');
        $array = $phoneNumber->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(1, $array['id']);
        $this->assertEquals('+2250777104936', $array['number']);
        $this->assertEquals('2023-01-01 00:00:00', $array['dateAdded']);
        $this->assertIsArray($array['segments']);
    }
}
