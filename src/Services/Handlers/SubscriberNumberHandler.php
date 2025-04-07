<?php

namespace App\Services\Handlers;

use App\Models\PhoneNumber;
use App\Models\Segment;
use App\Repositories\TechnicalSegmentRepository;

/**
 * Handler for extracting and validating the subscriber number from a phone number.
 */
class SubscriberNumberHandler extends AbstractSegmentationHandler
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
     * Process the phone number to extract and validate the subscriber number.
     *
     * @param PhoneNumber $phoneNumber The phone number to process
     * @return PhoneNumber The processed phone number with subscriber number segment
     */
    protected function process(PhoneNumber $phoneNumber): PhoneNumber
    {
        $number = $phoneNumber->getNumber();

        // For Ivory Coast numbers, the subscriber number is typically the last 8 digits
        if (preg_match('/^\+225\d{2}(\d{8})$/', $number, $matches)) {
            $subscriberNumber = $matches[1];

            // Create a new segment for the subscriber number
            $segment = new Segment(Segment::TYPE_SUBSCRIBER_NUMBER, $subscriberNumber);

            // Add the segment to the phone number
            $phoneNumber->addTechnicalSegment($segment);
        }

        return $phoneNumber;
    }
}
