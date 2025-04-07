<?php

namespace App\Services\Interfaces;

use App\Models\PhoneNumber;

/**
 * Interface for phone number segmentation strategies
 */
interface SegmentationStrategyInterface
{
    /**
     * Segment a phone number
     * 
     * @param PhoneNumber $phoneNumber
     * @return PhoneNumber
     */
    public function segment(PhoneNumber $phoneNumber): PhoneNumber;
}
