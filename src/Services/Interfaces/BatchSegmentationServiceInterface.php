<?php

namespace App\Services\Interfaces;

/**
 * Interface for batch segmentation service
 */
interface BatchSegmentationServiceInterface
{
    /**
     * Process multiple phone numbers without saving to database
     * 
     * @param array $phoneNumbers Array of phone number strings
     * @return array Array of segmented PhoneNumber objects
     */
    public function processPhoneNumbers(array $phoneNumbers): array;

    /**
     * Process and save multiple phone numbers to database
     * 
     * @param array $phoneNumbers Array of phone number strings
     * @return array Array of results and errors
     */
    public function processAndSavePhoneNumbers(array $phoneNumbers): array;
}
