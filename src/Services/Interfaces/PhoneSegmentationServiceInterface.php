<?php

namespace App\Services\Interfaces;

use App\Entities\PhoneNumber; // Use Doctrine Entity

/**
 * Interface for phone segmentation services
 */
interface PhoneSegmentationServiceInterface
{
    /**
     * Segment a phone number
     * 
     * @param PhoneNumber $phoneNumber // Use Doctrine Entity
     * @return PhoneNumber // Return Doctrine Entity
     */
    public function segmentPhoneNumber(PhoneNumber $phoneNumber): PhoneNumber; // Use Doctrine Entity
}
