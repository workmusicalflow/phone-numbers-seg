<?php

namespace App\Services\Handlers;

use App\Models\PhoneNumber;
use App\Models\Segment;
use App\Repositories\TechnicalSegmentRepository;

/**
 * Handler for extracting and validating the country code from a phone number.
 */
class CountryCodeHandler extends AbstractSegmentationHandler
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
     * Process the phone number to extract and validate the country code.
     *
     * @param PhoneNumber $phoneNumber The phone number to process
     * @return PhoneNumber The processed phone number with country code segment
     */
    protected function process(PhoneNumber $phoneNumber): PhoneNumber
    {
        $number = $phoneNumber->getNumber();

        // Extract country code (assuming the number is in international format +XXX...)
        if (preg_match('/^\+(\d{1,3})/', $number, $matches)) {
            $countryCode = $matches[1];

            // Create a new segment for the country code
            $segment = new Segment(Segment::TYPE_COUNTRY_CODE, $countryCode);

            // Save the segment to the repository if needed
            // $this->segmentRepository->save($segment);

            // Add the segment to the phone number as a technical segment
            $phoneNumber->addTechnicalSegment($segment);
        }

        return $phoneNumber;
    }
}
