<?php

namespace App\Services;

use App\Models\PhoneNumber;
use App\Models\Segment;

/**
 * PhoneSegmentationService
 * 
 * Service for segmenting phone numbers
 */
class PhoneSegmentationService
{
    /**
     * Segment a phone number
     * 
     * @param PhoneNumber $phoneNumber
     * @return PhoneNumber
     */
    public function segmentPhoneNumber(PhoneNumber $phoneNumber): PhoneNumber
    {
        // Ensure the phone number is valid
        if (!$phoneNumber->isValid()) {
            throw new \InvalidArgumentException('Invalid phone number format');
        }

        // Get the normalized number
        $number = $phoneNumber->getNumber();

        // Extract country code
        $countryCode = $this->extractCountryCode($number);
        $phoneNumber->addTechnicalSegment(new Segment(Segment::TYPE_COUNTRY_CODE, $countryCode));

        // Extract operator code
        $operatorCode = $this->extractOperatorCode($number);
        $phoneNumber->addTechnicalSegment(new Segment(Segment::TYPE_OPERATOR_CODE, $operatorCode));

        // Extract subscriber number
        $subscriberNumber = $this->extractSubscriberNumber($number);
        $phoneNumber->addTechnicalSegment(new Segment(Segment::TYPE_SUBSCRIBER_NUMBER, $subscriberNumber));

        // Extract operator name (if known)
        $operatorName = $this->identifyOperator($operatorCode);
        if ($operatorName) {
            $phoneNumber->addTechnicalSegment(new Segment(Segment::TYPE_OPERATOR_NAME, $operatorName));
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
            '01' => 'Orange CI',
            '02' => 'Orange CI',
            '03' => 'Orange CI',
            '04' => 'Orange CI',
            '05' => 'MTN CI',
            '06' => 'MTN CI',
            '07' => 'MTN CI',
            '08' => 'MTN CI',
            '09' => 'Moov Africa',
            '40' => 'Moov Africa',
            '41' => 'Moov Africa',
            '42' => 'Moov Africa',
            '43' => 'Moov Africa',
            '44' => 'Moov Africa',
            '45' => 'Moov Africa',
            '46' => 'Moov Africa',
            '47' => 'Moov Africa',
            '48' => 'Moov Africa',
            '49' => 'Moov Africa',
            '50' => 'Moov Africa',
            '51' => 'Moov Africa',
            '52' => 'Moov Africa',
            '53' => 'Moov Africa',
            '54' => 'Moov Africa',
            '55' => 'Moov Africa',
            '56' => 'Moov Africa',
            '57' => 'Moov Africa',
            '58' => 'Moov Africa',
            '59' => 'Moov Africa',
            '60' => 'Moov Africa',
            '61' => 'Moov Africa',
            '62' => 'Moov Africa',
            '63' => 'Moov Africa',
            '64' => 'Moov Africa',
            '65' => 'Moov Africa',
            '66' => 'Moov Africa',
            '67' => 'Moov Africa',
            '68' => 'Moov Africa',
            '69' => 'Moov Africa',
            '70' => 'Orange CI',
            '71' => 'Orange CI',
            '72' => 'Orange CI',
            '73' => 'Orange CI',
            '74' => 'Orange CI',
            '75' => 'Orange CI',
            '76' => 'Orange CI',
            '77' => 'Orange CI',
            '78' => 'Orange CI',
            '79' => 'Orange CI',
        ];

        return $operatorMap[$operatorCode] ?? null;
    }
}
