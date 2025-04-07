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
     * Test civility getter and setter
     */
    public function testCivilityGetterAndSetter()
    {
        $phoneNumber = new PhoneNumber('+2250777104936');
        $this->assertNull($phoneNumber->getCivility());

        $phoneNumber->setCivility('M.');
        $this->assertEquals('M.', $phoneNumber->getCivility());
    }

    /**
     * Test firstName getter and setter
     */
    public function testFirstNameGetterAndSetter()
    {
        $phoneNumber = new PhoneNumber('+2250777104936');
        $this->assertNull($phoneNumber->getFirstName());

        $phoneNumber->setFirstName('John');
        $this->assertEquals('John', $phoneNumber->getFirstName());
    }

    /**
     * Test phone number to array conversion
     */
    public function testToArray()
    {
        $phoneNumber = new PhoneNumber(
            '+2250777104936',
            1,
            'M.',
            'John',
            'Doe',
            'ACME Corp',
            'Technology',
            'Important client',
            '2023-01-01 00:00:00'
        );
        $array = $phoneNumber->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(1, $array['id']);
        $this->assertEquals('+2250777104936', $array['number']);
        $this->assertEquals('M.', $array['civility']);
        $this->assertEquals('John', $array['firstName']);
        $this->assertEquals('Doe', $array['name']);
        $this->assertEquals('ACME Corp', $array['company']);
        $this->assertEquals('Technology', $array['sector']);
        $this->assertEquals('Important client', $array['notes']);
        $this->assertEquals('2023-01-01 00:00:00', $array['dateAdded']);
        $this->assertIsArray($array['technicalSegments']);
        $this->assertIsArray($array['customSegments']);
    }
}
