<?php

namespace App\Controllers;

use App\Models\PhoneNumber;
use App\Models\CustomSegment;
use App\Repositories\PhoneNumberRepository;
use App\Repositories\TechnicalSegmentRepository;
use App\Repositories\CustomSegmentRepository;
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
     * @var TechnicalSegmentRepository
     */
    private TechnicalSegmentRepository $technicalSegmentRepository;

    /**
     * @var CustomSegmentRepository
     */
    private CustomSegmentRepository $customSegmentRepository;

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
        $this->technicalSegmentRepository = new TechnicalSegmentRepository($db);
        $this->customSegmentRepository = new CustomSegmentRepository($db);
        $this->phoneNumberRepository = new PhoneNumberRepository(
            $db,
            $this->technicalSegmentRepository,
            $this->customSegmentRepository
        );
        $this->phoneSegmentationService = new PhoneSegmentationService();
        $this->batchSegmentationService = new BatchSegmentationService(
            $this->phoneSegmentationService,
            $this->phoneNumberRepository,
            $this->technicalSegmentRepository
        );
    }

    /**
     * Index action - list all phone numbers
     * 
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function index(int $limit = 100, int $offset = 0): array
    {
        $phoneNumbers = $this->phoneNumberRepository->findAll($limit, $offset);
        $count = $this->phoneNumberRepository->countAll();

        return [
            'phoneNumbers' => array_map(function ($phoneNumber) {
                return $phoneNumber->toArray();
            }, $phoneNumbers),
            'total' => $count,
            'limit' => $limit,
            'offset' => $offset
        ];
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

        return $phoneNumber->toArray();
    }

    /**
     * Create action - create a new phone number
     * 
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        if (!isset($data['number'])) {
            throw new \InvalidArgumentException('Phone number is required');
        }

        // Create the phone number
        $phoneNumber = new PhoneNumber(
            $data['number'],
            null,
            $data['name'] ?? null,
            $data['company'] ?? null,
            $data['sector'] ?? null,
            $data['notes'] ?? null
        );

        // Check if the phone number is valid
        if (!$phoneNumber->isValid()) {
            throw new \InvalidArgumentException('Invalid phone number format');
        }

        // Check if the phone number already exists
        $existingPhoneNumber = $this->phoneNumberRepository->findByNumber($phoneNumber->getNumber());
        if ($existingPhoneNumber) {
            throw new \InvalidArgumentException('Phone number already exists');
        }

        // Segment the phone number
        $phoneNumber = $this->phoneSegmentationService->segmentPhoneNumber($phoneNumber);

        // Add to custom segments if specified
        if (isset($data['segments']) && is_array($data['segments'])) {
            foreach ($data['segments'] as $segmentId) {
                $segment = $this->customSegmentRepository->findById((int)$segmentId);
                if ($segment) {
                    $phoneNumber->addCustomSegment($segment);
                }
            }
        }

        // Save the phone number (this will also save segments)
        $phoneNumber = $this->phoneNumberRepository->save($phoneNumber);

        return $phoneNumber->toArray();
    }

    /**
     * Update action - update an existing phone number
     * 
     * @param int $id
     * @param array $data
     * @return array|null
     */
    public function update(int $id, array $data): ?array
    {
        // Find the phone number
        $phoneNumber = $this->phoneNumberRepository->findById($id);
        if (!$phoneNumber) {
            return null;
        }

        // Update the phone number fields
        if (isset($data['number'])) {
            $phoneNumber->setNumber($data['number']);

            // Check if the phone number is valid
            if (!$phoneNumber->isValid()) {
                throw new \InvalidArgumentException('Invalid phone number format');
            }

            // Re-segment the phone number if the number changed
            $phoneNumber = $this->phoneSegmentationService->segmentPhoneNumber($phoneNumber);
        }

        if (isset($data['name'])) {
            $phoneNumber->setName($data['name']);
        }

        if (isset($data['company'])) {
            $phoneNumber->setCompany($data['company']);
        }

        if (isset($data['sector'])) {
            $phoneNumber->setSector($data['sector']);
        }

        if (isset($data['notes'])) {
            $phoneNumber->setNotes($data['notes']);
        }

        // Update custom segments if specified
        if (isset($data['segments']) && is_array($data['segments'])) {
            $segments = [];
            foreach ($data['segments'] as $segmentId) {
                $segment = $this->customSegmentRepository->findById((int)$segmentId);
                if ($segment) {
                    $segments[] = $segment;
                }
            }
            $phoneNumber->setCustomSegments($segments);
        }

        // Save the phone number (this will also save segments)
        $phoneNumber = $this->phoneNumberRepository->save($phoneNumber);

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
        // The foreign key constraints will handle deleting related segments
        return $this->phoneNumberRepository->delete($id);
    }

    /**
     * Segment action - segment a phone number without saving it
     * 
     * @param string $number
     * @param string|null $civility
     * @param string|null $firstName
     * @param string|null $name
     * @param string|null $company
     * @return array
     */
    public function segment(
        string $number,
        ?string $civility = null,
        ?string $firstName = null,
        ?string $name = null,
        ?string $company = null
    ): array {
        $phoneNumber = new PhoneNumber(
            $number,
            null,
            $name,
            $company,
            null,
            null,
            $civility,
            $firstName
        );

        // Check if the phone number is valid
        if (!$phoneNumber->isValid()) {
            throw new \InvalidArgumentException('Invalid phone number format');
        }

        // Segment the phone number
        $phoneNumber = $this->phoneSegmentationService->segmentPhoneNumber($phoneNumber);

        return $phoneNumber->toArray();
    }

    /**
     * Get all custom segments
     * 
     * @return array
     */
    public function getCustomSegments(): array
    {
        $segments = $this->customSegmentRepository->findAll();
        return array_map(function ($segment) {
            return $segment->toArray();
        }, $segments);
    }

    /**
     * Get a specific custom segment
     * 
     * @param int $id
     * @return array|null
     */
    public function getCustomSegment(int $id): ?array
    {
        $segment = $this->customSegmentRepository->findById($id);
        if (!$segment) {
            return null;
        }

        return $segment->toArray(true);
    }

    /**
     * Create a custom segment
     * 
     * @param string $name
     * @param string|null $description
     * @return array
     */
    public function createCustomSegment(string $name, ?string $description = null): array
    {
        // Check if the segment already exists
        $existingSegment = $this->customSegmentRepository->findByName($name);
        if ($existingSegment) {
            throw new \InvalidArgumentException('Segment with this name already exists');
        }

        $segment = new CustomSegment($name, $description);
        $segment = $this->customSegmentRepository->save($segment);

        return $segment->toArray();
    }

    /**
     * Update a custom segment
     * 
     * @param int $id
     * @param string $name
     * @param string|null $description
     * @return array|null
     */
    public function updateCustomSegment(int $id, string $name, ?string $description = null): ?array
    {
        $segment = $this->customSegmentRepository->findById($id);
        if (!$segment) {
            return null;
        }

        // Check if the name is already used by another segment
        $existingSegment = $this->customSegmentRepository->findByName($name);
        if ($existingSegment && $existingSegment->getId() !== $id) {
            throw new \InvalidArgumentException('Segment with this name already exists');
        }

        $segment->setName($name);
        $segment->setDescription($description);

        $segment = $this->customSegmentRepository->save($segment);

        return $segment->toArray();
    }

    /**
     * Delete a custom segment
     * 
     * @param int $id
     * @return bool
     */
    public function deleteCustomSegment(int $id): bool
    {
        return $this->customSegmentRepository->delete($id);
    }

    /**
     * Add a phone number to a custom segment
     * 
     * @param int $phoneNumberId
     * @param int $segmentId
     * @return bool
     */
    public function addPhoneNumberToSegment(int $phoneNumberId, int $segmentId): bool
    {
        // Check if the phone number exists
        $phoneNumber = $this->phoneNumberRepository->findById($phoneNumberId);
        if (!$phoneNumber) {
            throw new \InvalidArgumentException('Phone number not found');
        }

        // Check if the segment exists
        $segment = $this->customSegmentRepository->findById($segmentId);
        if (!$segment) {
            throw new \InvalidArgumentException('Segment not found');
        }

        return $this->customSegmentRepository->addPhoneNumberToSegment($phoneNumberId, $segmentId);
    }

    /**
     * Remove a phone number from a custom segment
     * 
     * @param int $phoneNumberId
     * @param int $segmentId
     * @return bool
     */
    public function removePhoneNumberFromSegment(int $phoneNumberId, int $segmentId): bool
    {
        return $this->customSegmentRepository->removePhoneNumberFromSegment($phoneNumberId, $segmentId);
    }

    /**
     * Find phone numbers by custom segment
     * 
     * @param int $segmentId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findPhoneNumbersBySegment(int $segmentId, int $limit = 100, int $offset = 0): array
    {
        $phoneNumbers = $this->phoneNumberRepository->findByCustomSegment($segmentId, $limit, $offset);
        $count = $this->phoneNumberRepository->countByCustomSegment($segmentId);

        return [
            'phoneNumbers' => array_map(function ($phoneNumber) {
                return $phoneNumber->toArray();
            }, $phoneNumbers),
            'total' => $count,
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    /**
     * Search phone numbers
     * 
     * @param string $query
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function searchPhoneNumbers(string $query, int $limit = 100, int $offset = 0): array
    {
        $phoneNumbers = $this->phoneNumberRepository->search($query, $limit, $offset);

        return [
            'phoneNumbers' => array_map(function ($phoneNumber) {
                return $phoneNumber->toArray();
            }, $phoneNumbers),
            'limit' => $limit,
            'offset' => $offset
        ];
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
