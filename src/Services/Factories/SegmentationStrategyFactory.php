<?php

namespace App\Services\Factories;

use App\Services\Interfaces\SegmentationStrategyInterface;
use App\Services\Strategies\IvoryCoastSegmentationStrategy;
use InvalidArgumentException;

/**
 * Factory for creating segmentation strategies based on country code
 */
class SegmentationStrategyFactory
{
    /**
     * Get the appropriate segmentation strategy for a country code
     * 
     * @param string $countryCode
     * @return SegmentationStrategyInterface
     * @throws InvalidArgumentException If no strategy is available for the country code
     */
    public function getStrategy(string $countryCode): SegmentationStrategyInterface
    {
        // For now, we only support Ivory Coast (225)
        if ($countryCode === '225') {
            return new IvoryCoastSegmentationStrategy();
        }

        // Default to Ivory Coast strategy for now
        // In the future, we can add more strategies for other countries
        return new IvoryCoastSegmentationStrategy();
    }
}
