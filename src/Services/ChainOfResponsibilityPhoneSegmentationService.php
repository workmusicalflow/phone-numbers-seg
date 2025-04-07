<?php

namespace App\Services;

use App\Models\PhoneNumber;
use App\Repositories\PhoneNumberRepository;
use App\Repositories\TechnicalSegmentRepository;
use App\Services\Factories\SegmentationHandlerFactory;
use App\Services\Interfaces\PhoneNumberValidatorInterface;
use App\Services\Interfaces\PhoneSegmentationServiceInterface;

/**
 * Implementation of the phone segmentation service using the Chain of Responsibility pattern.
 */
class ChainOfResponsibilityPhoneSegmentationService implements PhoneSegmentationServiceInterface
{
    /**
     * The phone number validator.
     *
     * @var PhoneNumberValidatorInterface
     */
    private PhoneNumberValidatorInterface $validator;

    /**
     * The segmentation handler factory.
     *
     * @var SegmentationHandlerFactory
     */
    private SegmentationHandlerFactory $handlerFactory;

    /**
     * The phone number repository.
     *
     * @var PhoneNumberRepository
     */
    private PhoneNumberRepository $phoneNumberRepository;

    /**
     * The technical segment repository.
     *
     * @var TechnicalSegmentRepository
     */
    private TechnicalSegmentRepository $segmentRepository;

    /**
     * Constructor.
     *
     * @param PhoneNumberValidatorInterface $validator The phone number validator
     * @param SegmentationHandlerFactory $handlerFactory The segmentation handler factory
     * @param PhoneNumberRepository $phoneNumberRepository The phone number repository
     * @param TechnicalSegmentRepository $segmentRepository The technical segment repository
     */
    public function __construct(
        PhoneNumberValidatorInterface $validator,
        SegmentationHandlerFactory $handlerFactory,
        PhoneNumberRepository $phoneNumberRepository,
        TechnicalSegmentRepository $segmentRepository
    ) {
        $this->validator = $validator;
        $this->handlerFactory = $handlerFactory;
        $this->phoneNumberRepository = $phoneNumberRepository;
        $this->segmentRepository = $segmentRepository;
    }

    /**
     * Segment a phone number.
     *
     * @param PhoneNumber $phoneNumber The phone number to segment
     * @return PhoneNumber The segmented phone number
     */
    public function segmentPhoneNumber(PhoneNumber $phoneNumber): PhoneNumber
    {
        // Validate the phone number
        if (!$this->validator->validate($phoneNumber)) {
            return $phoneNumber;
        }

        // Process the phone number through the chain of handlers
        $handlerChain = $this->handlerFactory->createHandlerChain();
        $phoneNumber = $handlerChain->handle($phoneNumber);

        return $phoneNumber;
    }

    /**
     * Save a phone number and its segments to the database.
     *
     * @param PhoneNumber $phoneNumber The phone number to save
     * @return PhoneNumber The saved phone number
     */
    private function savePhoneNumber(PhoneNumber $phoneNumber): PhoneNumber
    {
        // Save the phone number
        $savedPhoneNumber = $this->phoneNumberRepository->save($phoneNumber);

        // Save the segments
        foreach ($phoneNumber->getTechnicalSegments() as $segment) {
            $segment->setPhoneNumberId($savedPhoneNumber->getId());
            $this->segmentRepository->save($segment);
        }

        return $savedPhoneNumber;
    }
}
