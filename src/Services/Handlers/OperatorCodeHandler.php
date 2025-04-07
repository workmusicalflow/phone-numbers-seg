<?php

namespace App\Services\Handlers;

use App\Models\PhoneNumber;
use App\Models\Segment;
use App\Repositories\TechnicalSegmentRepository;

/**
 * Handler for extracting and validating the operator code from a phone number.
 */
class OperatorCodeHandler extends AbstractSegmentationHandler
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
     * Process the phone number to extract and validate the operator code.
     *
     * @param PhoneNumber $phoneNumber The phone number to process
     * @return PhoneNumber The processed phone number with operator code segment
     */
    protected function process(PhoneNumber $phoneNumber): PhoneNumber
    {
        $number = $phoneNumber->getNumber();

        // For Ivory Coast numbers, the operator code is typically the first 2 digits after the country code
        if (preg_match('/^\+225(\d{2})/', $number, $matches)) {
            $operatorCode = $matches[1];

            // Create a new segment for the operator code
            $segment = new Segment(Segment::TYPE_OPERATOR_CODE, $operatorCode);

            // Determine the operator name based on the operator code
            $operatorName = $this->determineOperatorName($operatorCode);
            if ($operatorName) {
                $operatorNameSegment = new Segment(Segment::TYPE_OPERATOR_NAME, $operatorName);
                $phoneNumber->addTechnicalSegment($operatorNameSegment);
            }

            // Add the segment to the phone number
            $phoneNumber->addTechnicalSegment($segment);
        }

        return $phoneNumber;
    }

    /**
     * Determine the operator name based on the operator code.
     * This is a simplified implementation for Ivory Coast operators.
     *
     * @param string $operatorCode The operator code
     * @return string|null The operator name or null if unknown
     */
    private function determineOperatorName(string $operatorCode): ?string
    {
        // Simplified mapping for Ivory Coast operators
        $operatorMap = [
            '07' => 'Orange CI',
            '08' => 'Orange CI',
            '09' => 'Orange CI',
            '01' => 'MTN CI',
            '05' => 'MTN CI',
            '06' => 'MTN CI',
            '03' => 'Moov CI',
            '04' => 'Moov CI',
        ];

        return $operatorMap[$operatorCode] ?? null;
    }
}
