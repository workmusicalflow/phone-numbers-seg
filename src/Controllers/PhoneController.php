<?php

namespace App\Controllers;

use App\Models\PhoneNumber;
use App\Repositories\PhoneNumberRepository;
use App\Repositories\SegmentRepository;
use App\Services\BatchSegmentationService;
use App\Services\PhoneSegmentationService;
use PDO;

/**
 * PhoneController
 * 
 * Controller for phone number operations
 */
class PhoneController
{
    /**
     * @var PhoneNumberRepository
     */
    private PhoneNumberRepository $phoneNumberRepository;

    /**
     * @var SegmentRepository
     */
    private SegmentRepository $segmentRepository;

    /**
     * @var PhoneSegmentationService
     */
    private PhoneSegmentationService $phoneSegmentationService;

    /**
     * @var BatchSegmentationService
     */
    private BatchSegmentationService $batchSegmentationService;

    /**
     * Constructor
     * 
     * @param PDO $db
     */
    public function __construct(PDO $db)
    {
        $this->phoneNumberRepository = new PhoneNumberRepository($db);
        $this->segmentRepository = new SegmentRepository($db);
        $this->phoneSegmentationService = new PhoneSegmentationService();
        $this->batchSegmentationService = new BatchSegmentationService(
            $this->phoneSegmentationService,
            $this->phoneNumberRepository,
            $this->segmentRepository
        );
    }

    /**
     * Index action - list all phone numbers
     * 
     * @return array
     */
    public function index(): array
    {
        $phoneNumbers = $this->phoneNumberRepository->findAll();
        $result = [];

        foreach ($phoneNumbers as $phoneNumber) {
            $segments = $this->segmentRepository->findByPhoneNumberId($phoneNumber->getId());
            $phoneNumber->setSegments($segments);
            $result[] = $phoneNumber->toArray();
        }

        return $result;
    }

    /**
     * Show action - get a specific phone number
     * 
     * @param int $id
     * @return array|null
     */
    public function show(int $id): ?array
    {
        $phoneNumber = $this->phoneNumberRepository->findById($id);
        if (!$phoneNumber) {
            return null;
        }

        $segments = $this->segmentRepository->findByPhoneNumberId($phoneNumber->getId());
        $phoneNumber->setSegments($segments);

        return $phoneNumber->toArray();
    }

    /**
     * Create action - create a new phone number
     * 
     * @param string $number
     * @return array
     */
    public function create(string $number): array
    {
        // Create and save the phone number
        $phoneNumber = new PhoneNumber($number);

        // Check if the phone number is valid
        if (!$phoneNumber->isValid()) {
            throw new \InvalidArgumentException('Invalid phone number format');
        }

        // Check if the phone number already exists
        $existingPhoneNumber = $this->phoneNumberRepository->findByNumber($phoneNumber->getNumber());
        if ($existingPhoneNumber) {
            throw new \InvalidArgumentException('Phone number already exists');
        }

        // Save the phone number
        $phoneNumber = $this->phoneNumberRepository->save($phoneNumber);

        // Segment the phone number
        $phoneNumber = $this->phoneSegmentationService->segmentPhoneNumber($phoneNumber);

        // Save the segments
        foreach ($phoneNumber->getSegments() as $segment) {
            $segment->setPhoneNumberId($phoneNumber->getId());
            $this->segmentRepository->save($segment);
        }

        return $phoneNumber->toArray();
    }

    /**
     * Update action - update an existing phone number
     * 
     * @param int $id
     * @param string $number
     * @return array|null
     */
    public function update(int $id, string $number): ?array
    {
        // Find the phone number
        $phoneNumber = $this->phoneNumberRepository->findById($id);
        if (!$phoneNumber) {
            return null;
        }

        // Update the phone number
        $phoneNumber->setNumber($number);

        // Check if the phone number is valid
        if (!$phoneNumber->isValid()) {
            throw new \InvalidArgumentException('Invalid phone number format');
        }

        // Save the phone number
        $phoneNumber = $this->phoneNumberRepository->save($phoneNumber);

        // Delete existing segments
        $this->segmentRepository->deleteByPhoneNumberId($phoneNumber->getId());

        // Segment the phone number
        $phoneNumber = $this->phoneSegmentationService->segmentPhoneNumber($phoneNumber);

        // Save the segments
        foreach ($phoneNumber->getSegments() as $segment) {
            $segment->setPhoneNumberId($phoneNumber->getId());
            $this->segmentRepository->save($segment);
        }

        return $phoneNumber->toArray();
    }

    /**
     * Delete action - delete a phone number
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        // Delete segments first (should be handled by foreign key constraint, but just to be safe)
        $this->segmentRepository->deleteByPhoneNumberId($id);

        // Delete the phone number
        return $this->phoneNumberRepository->delete($id);
    }

    /**
     * Segment action - segment a phone number without saving it
     * 
     * @param string $number
     * @return array
     */
    public function segment(string $number): array
    {
        $phoneNumber = new PhoneNumber($number);

        // Check if the phone number is valid
        if (!$phoneNumber->isValid()) {
            throw new \InvalidArgumentException('Invalid phone number format');
        }

        // Segment the phone number
        $phoneNumber = $this->phoneSegmentationService->segmentPhoneNumber($phoneNumber);

        return $phoneNumber->toArray();
    }

    /**
     * Batch segment action - segment multiple phone numbers without saving them
     * 
     * @param array $numbers
     * @return array
     */
    public function batchSegment(array $numbers): array
    {
        if (empty($numbers)) {
            throw new \InvalidArgumentException('No phone numbers provided');
        }

        $result = $this->batchSegmentationService->processPhoneNumbers($numbers);
        return $this->batchSegmentationService->formatResults($result);
    }

    /**
     * Batch create action - create multiple phone numbers
     * 
     * @param array $numbers
     * @return array
     */
    public function batchCreate(array $numbers): array
    {
        if (empty($numbers)) {
            throw new \InvalidArgumentException('No phone numbers provided');
        }

        $result = $this->batchSegmentationService->processAndSavePhoneNumbers($numbers);
        return $this->batchSegmentationService->formatResults($result);
    }
}
