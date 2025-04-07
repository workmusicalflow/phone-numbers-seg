<?php

namespace App\Services\Factories;

use App\Repositories\TechnicalSegmentRepository;
use App\Services\Handlers\CountryCodeHandler;
use App\Services\Handlers\OperatorCodeHandler;
use App\Services\Handlers\SubscriberNumberHandler;
use App\Services\Interfaces\SegmentationHandlerInterface;

/**
 * Factory for creating and configuring the chain of segmentation handlers.
 */
class SegmentationHandlerFactory
{
    /**
     * The technical segment repository.
     *
     * @var TechnicalSegmentRepository
     */
    private TechnicalSegmentRepository $segmentRepository;

    /**
     * Constructor.
     *
     * @param TechnicalSegmentRepository $segmentRepository The technical segment repository
     */
    public function __construct(TechnicalSegmentRepository $segmentRepository)
    {
        $this->segmentRepository = $segmentRepository;
    }

    /**
     * Create the chain of segmentation handlers.
     *
     * @return SegmentationHandlerInterface The first handler in the chain
     */
    public function createHandlerChain(): SegmentationHandlerInterface
    {
        // Create the handlers
        $countryCodeHandler = new CountryCodeHandler($this->segmentRepository);
        $operatorCodeHandler = new OperatorCodeHandler($this->segmentRepository);
        $subscriberNumberHandler = new SubscriberNumberHandler($this->segmentRepository);

        // Configure the chain
        $countryCodeHandler->setNext($operatorCodeHandler);
        $operatorCodeHandler->setNext($subscriberNumberHandler);

        // Return the first handler in the chain
        return $countryCodeHandler;
    }

    /**
     * Create a specific handler by type.
     *
     * @param string $type The type of handler to create
     * @return SegmentationHandlerInterface|null The handler or null if the type is not supported
     */
    public function createHandler(string $type): ?SegmentationHandlerInterface
    {
        switch ($type) {
            case 'country_code':
                return new CountryCodeHandler($this->segmentRepository);
            case 'operator_code':
                return new OperatorCodeHandler($this->segmentRepository);
            case 'subscriber_number':
                return new SubscriberNumberHandler($this->segmentRepository);
            default:
                return null;
        }
    }
}
