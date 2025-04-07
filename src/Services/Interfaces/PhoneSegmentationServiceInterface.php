<?php

namespace App\Services\Interfaces;

use App\Models\PhoneNumber;

/**
 * Interface for phone segmentation services
 */
interface PhoneSegmentationServiceInterface
{
    /**
     * Segment a phone number
     * 
     * @param PhoneNumber $phoneNumber
     * @return PhoneNumber
     */
    public function segmentPhoneNumber(PhoneNumber $phoneNumber): PhoneNumber;
}
