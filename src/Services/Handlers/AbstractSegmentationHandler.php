<?php

namespace App\Services\Handlers;

use App\Models\PhoneNumber;
use App\Services\Interfaces\SegmentationHandlerInterface;

/**
 * Abstract base class for segmentation handlers in the Chain of Responsibility pattern.
 * Provides the basic implementation for chaining handlers together.
 */
abstract class AbstractSegmentationHandler implements SegmentationHandlerInterface
{
    /**
     * The next handler in the chain.
     *
     * @var SegmentationHandlerInterface|null
     */
    protected ?SegmentationHandlerInterface $nextHandler = null;

    /**
     * {@inheritdoc}
     */
    public function setNext(SegmentationHandlerInterface $handler): SegmentationHandlerInterface
    {
        $this->nextHandler = $handler;
        return $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(PhoneNumber $phoneNumber): PhoneNumber
    {
        // Process the phone number with the current handler
        $processedPhoneNumber = $this->process($phoneNumber);

        // If there's a next handler, pass the processed phone number to it
        if ($this->nextHandler) {
            return $this->nextHandler->handle($processedPhoneNumber);
        }

        // Otherwise, return the processed phone number
        return $processedPhoneNumber;
    }

    /**
     * Process the phone number with the specific logic of this handler.
     * This method must be implemented by concrete handlers.
     *
     * @param PhoneNumber $phoneNumber The phone number to process
     * @return PhoneNumber The processed phone number
     */
    abstract protected function process(PhoneNumber $phoneNumber): PhoneNumber;
}
