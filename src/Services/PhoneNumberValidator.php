<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\PhoneNumber;
use App\Services\Interfaces\PhoneNumberValidatorInterface;

/**
 * Validator for phone numbers
 */
class PhoneNumberValidator implements PhoneNumberValidatorInterface
{
    /**
     * Validate a phone number object
     * 
     * @param PhoneNumber $phoneNumber
     * @return bool
     */
    public function validate(PhoneNumber $phoneNumber): bool
    {
        return $this->isValid($phoneNumber->getNumber());
    }

    /**
     * Validate a phone number string
     * 
     * @param string $number
     * @return bool
     */
    public function isValid(string $number): bool
    {
        // Check if the number is empty
        if (empty($number)) {
            return false;
        }

        // Check if the number starts with a plus sign
        if (strpos($number, '+') !== 0) {
            return false;
        }

        // Check if the number contains only digits after the plus sign
        if (!preg_match('/^\+[0-9]+$/', $number)) {
            return false;
        }

        // Check if the number has a valid length (international numbers are typically 7-15 digits)
        $digitsOnly = substr($number, 1); // Remove the plus sign
        if (strlen($digitsOnly) < 7 || strlen($digitsOnly) > 15) {
            return false;
        }

        return true;
    }

    /**
     * Validate a batch of phone numbers
     * 
     * @param array $numbers
     * @return array Associative array with valid and invalid numbers
     */
    public function validateBatch(array $numbers): array
    {
        $result = [
            'valid' => [],
            'invalid' => []
        ];

        foreach ($numbers as $number) {
            if ($this->isValid($number)) {
                $result['valid'][] = $number;
            } else {
                $result['invalid'][] = $number;
            }
        }

        return $result;
    }

    /**
     * Validate a phone number for SMS sending
     * 
     * @param string $number
     * @return bool
     */
    public function isValidForSMS(string $number): bool
    {
        // For now, we use the same validation as isValid
        // In a real implementation, we might add additional checks specific to SMS
        // For example, checking if it's a mobile number
        return $this->isValid($number);
    }

    /**
     * Validate data for creating a phone number
     * 
     * @param array $data
     * @return array Validated data
     * @throws ValidationException
     */
    public function validateCreate(array $data): array
    {
        if (!isset($data['number'])) {
            throw new ValidationException('Phone number is required');
        }

        if (!$this->isValid($data['number'])) {
            throw new ValidationException('Invalid phone number format');
        }

        // Return the validated data
        return $data;
    }

    /**
     * Validate data for updating a phone number
     * 
     * @param int $id
     * @param array $data
     * @return array Validated data
     * @throws ValidationException
     */
    public function validateUpdate(int $id, array $data): array
    {
        if (isset($data['number']) && !$this->isValid($data['number'])) {
            throw new ValidationException('Invalid phone number format');
        }

        // Return the validated data
        return $data;
    }

    /**
     * Validate data for deleting a phone number
     * 
     * @param int $id
     * @return array Validated data
     * @throws ValidationException
     */
    public function validateDelete(int $id): array
    {
        // No specific validation for deletion
        // In a real implementation, we might check if the phone number can be deleted
        // For example, checking if it's not referenced by other entities
        return ['id' => $id];
    }
}
