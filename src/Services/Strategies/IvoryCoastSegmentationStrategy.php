<?php

namespace App\Services\Strategies;

use App\Entities\PhoneNumber; // Use Doctrine Entity
use App\Entities\Segment; // Use Doctrine Entity
use App\Services\Interfaces\SegmentationStrategyInterface;

/**
 * Strategy for segmenting Ivory Coast phone numbers
 */
class IvoryCoastSegmentationStrategy implements SegmentationStrategyInterface
{
    /**
     * Segment a phone number
     * 
     * @param PhoneNumber $phoneNumber // Use Doctrine Entity
     * @return PhoneNumber // Return Doctrine Entity
     */
    public function segment(PhoneNumber $phoneNumber): PhoneNumber // Use Doctrine Entity
    {
        // Get the normalized number
        $number = $phoneNumber->getNumber();

        // Extract country code
        $countryCode = $this->extractCountryCode($number);
        $phoneNumber->addTechnicalSegment(new Segment(Segment::TYPE_COUNTRY_CODE, $countryCode)); // Use Doctrine Entity

        // Extract operator code
        $operatorCode = $this->extractOperatorCode($number);
        $phoneNumber->addTechnicalSegment(new Segment(Segment::TYPE_OPERATOR_CODE, $operatorCode)); // Use Doctrine Entity

        // Extract subscriber number
        $subscriberNumber = $this->extractSubscriberNumber($number);
        $phoneNumber->addTechnicalSegment(new Segment(Segment::TYPE_SUBSCRIBER_NUMBER, $subscriberNumber)); // Use Doctrine Entity

        // Extract operator name (if known)
        $operatorName = $this->identifyOperator($operatorCode);
        if ($operatorName) {
            $phoneNumber->addTechnicalSegment(new Segment(Segment::TYPE_OPERATOR_NAME, $operatorName)); // Use Doctrine Entity
        }

        return $phoneNumber;
    }

    /**
     * Extract the country code from a phone number
     * 
     * @param string $number
     * @return string
     */
    private function extractCountryCode(string $number): string
    {
        // For Côte d'Ivoire, the country code is always 225
        return '225';
    }

    /**
     * Extract the operator code from a phone number
     * 
     * @param string $number
     * @return string
     */
    private function extractOperatorCode(string $number): string
    {
        // The operator code is the first two digits after the country code
        // +225 XX XXXXXXXX
        return substr($number, 4, 2);
    }

    /**
     * Extract the subscriber number from a phone number
     * 
     * @param string $number
     * @return string
     */
    private function extractSubscriberNumber(string $number): string
    {
        // The subscriber number is the last 8 digits
        // +225 XX XXXXXXXX
        return substr($number, 6);
    }

    /**
     * Identify the operator based on the operator code
     * 
     * @param string $operatorCode
     * @return string|null
     */
    private function identifyOperator(string $operatorCode): ?string
    {
        // Map of operator codes to operator names for Côte d'Ivoire
        $operatorMap = [
            '07' => 'Orange CI',
            '05' => 'MTN CI',
            '01' => 'Moov Africa',
        ];

        return $operatorMap[$operatorCode] ?? null;
    }
}
