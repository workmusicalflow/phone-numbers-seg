<?php

namespace App\Services\Interfaces;

use App\Models\PhoneNumber;

/**
 * Interface for the Chain of Responsibility pattern for phone number segmentation.
 * Each handler in the chain processes a specific aspect of the phone number segmentation.
 */
interface SegmentationHandlerInterface
{
    /**
     * Set the next handler in the chain.
     *
     * @param SegmentationHandlerInterface $handler The next handler
     * @return SegmentationHandlerInterface The next handler (for chaining)
     */
    public function setNext(SegmentationHandlerInterface $handler): SegmentationHandlerInterface;

    /**
     * Handle the phone number segmentation.
     * Each handler processes its specific aspect and then passes the phone number
     * to the next handler in the chain.
     *
     * @param PhoneNumber $phoneNumber The phone number to process
     * @return PhoneNumber The processed phone number
     */
    public function handle(PhoneNumber $phoneNumber): PhoneNumber;
}
