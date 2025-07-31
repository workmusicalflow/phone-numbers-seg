<?php

use DI\Container;
use function DI\factory;

/**
 * Factory definitions for Dependency Injection Container
 */
return [
    // Factory for segmentation strategies
    \App\Services\Factories\SegmentationStrategyFactory::class => factory(function () {
        return new \App\Services\Factories\SegmentationStrategyFactory();
    }),

    // Add other factories if needed
];
