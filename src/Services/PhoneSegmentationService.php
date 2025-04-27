<?php

namespace App\Services;

use App\Entities\PhoneNumber; // Use Doctrine Entity
use App\Services\Factories\SegmentationStrategyFactory;
use App\Services\Interfaces\PhoneNumberValidatorInterface;
use App\Services\Interfaces\PhoneSegmentationServiceInterface;
use InvalidArgumentException;

/**
 * PhoneSegmentationService
 * 
 * Service for segmenting phone numbers
 */
class PhoneSegmentationService implements PhoneSegmentationServiceInterface
{
    /**
     * @var PhoneNumberValidatorInterface
     */
    private PhoneNumberValidatorInterface $validator;

    /**
     * @var SegmentationStrategyFactory
     */
    private SegmentationStrategyFactory $strategyFactory;

    /**
     * Constructor
     * 
     * @param PhoneNumberValidatorInterface $validator
     * @param SegmentationStrategyFactory $strategyFactory
     */
    public function __construct(
        PhoneNumberValidatorInterface $validator,
        SegmentationStrategyFactory $strategyFactory
    ) {
        $this->validator = $validator;
        $this->strategyFactory = $strategyFactory;
    }

    /**
     * Segment a phone number
     * 
     * @param PhoneNumber $phoneNumber // Use Doctrine Entity
     * @return PhoneNumber // Return Doctrine Entity
     * @throws InvalidArgumentException If the phone number is invalid
     */
    public function segmentPhoneNumber(PhoneNumber $phoneNumber): PhoneNumber // Use Doctrine Entity
    {
        // Validate the phone number string using the validator interface method
        if (!$this->validator->isValid($phoneNumber->getNumber())) {
            throw new InvalidArgumentException('Invalid phone number format: ' . $phoneNumber->getNumber());
        }

        // Extract country code to determine the strategy
        $countryCode = $this->extractCountryCode($phoneNumber->getNumber());

        // Get the appropriate strategy and segment the number
        $strategy = $this->strategyFactory->getStrategy($countryCode);
        return $strategy->segment($phoneNumber);
    }

    /**
     * Extract the country code from a phone number
     * 
     * @param string $number
     * @return string
     */
    private function extractCountryCode(string $number): string
    {
        // Simple extraction for now, can be improved
        if (strpos($number, '+') === 0) {
            return substr($number, 1, 3);
        }

        return '225'; // Default to Côte d'Ivoire
    }
}
