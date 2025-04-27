<?php

namespace App\Services\Interfaces;

use App\Entities\PhoneNumber; // Use Doctrine Entity

/**
 * Interface for phone number segmentation strategies
 */
interface SegmentationStrategyInterface
{
    /**
     * Segment a phone number
     * 
     * @param PhoneNumber $phoneNumber // Use Doctrine Entity
     * @return PhoneNumber // Return Doctrine Entity
     */
    public function segment(PhoneNumber $phoneNumber): PhoneNumber; // Use Doctrine Entity
}
