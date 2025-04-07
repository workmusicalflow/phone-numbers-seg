<?php

namespace App\GraphQL\Controllers;

use App\Models\PhoneNumber;
use App\Repositories\PhoneNumberRepository;
use App\Repositories\SegmentRepository;
use App\Services\Interfaces\PhoneSegmentationServiceInterface;
use App\Services\Validators\PhoneNumberValidator;
use App\Exceptions\ValidationException;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use Psr\Container\ContainerInterface;

/**
 * GraphQL controller for phone number operations
 */
class PhoneNumberController
{
    /**
     * @var PhoneNumberRepository
     */
    private $phoneNumberRepository;

    /**
     * @var SegmentRepository
     */
    private $segmentRepository;

    /**
     * @var PhoneSegmentationServiceInterface
     */
    private $phoneSegmentationService;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Constructor
     * 
     * @param PhoneNumberRepository $phoneNumberRepository
     * @param SegmentRepository $segmentRepository
     * @param PhoneSegmentationServiceInterface $phoneSegmentationService
     * @param ContainerInterface $container
     */
    public function __construct(
        PhoneNumberRepository $phoneNumberRepository,
        SegmentRepository $segmentRepository,
        PhoneSegmentationServiceInterface $phoneSegmentationService,
        ContainerInterface $container
    ) {
        $this->phoneNumberRepository = $phoneNumberRepository;
        $this->segmentRepository = $segmentRepository;
        $this->phoneSegmentationService = $phoneSegmentationService;
        $this->container = $container;
    }

    /**
     * Get a phone number by ID
     * 
     * @Query
     * @param int $id
     * @return PhoneNumber|null
     */
    public function phoneNumber(int $id): ?PhoneNumber
    {
        return $this->phoneNumberRepository->findById($id);
    }

    /**
     * Get a phone number by number
     * 
     * @Query
     * @param string $number
     * @return PhoneNumber|null
     */
    public function phoneNumberByNumber(string $number): ?PhoneNumber
    {
        return $this->phoneNumberRepository->findByNumber($number);
    }

    /**
     * Get all phone numbers
     * 
     * @Query
     * @param int $limit
     * @param int $offset
     * @return PhoneNumber[]
     */
    public function phoneNumbers(int $limit = 100, int $offset = 0): array
    {
        return $this->phoneNumberRepository->findAll($limit, $offset);
    }

    /**
     * Search phone numbers
     * 
     * @Query
     * @param string $query
     * @param int $limit
     * @param int $offset
     * @return PhoneNumber[]
     */
    public function searchPhoneNumbers(string $query, int $limit = 100, int $offset = 0): array
    {
        return $this->phoneNumberRepository->search($query, $limit, $offset);
    }

    /**
     * Create a new phone number
     * 
     * @Mutation
     * @param string $number
     * @param string $civility
     * @param string $firstName
     * @param string $name
     * @param string $company
     * @param string $sector
     * @param string $notes
     * @return PhoneNumber
     */
    public function createPhoneNumber(
        string $number,
        string $civility = '',
        string $firstName = '',
        string $name = '',
        string $company = '',
        string $sector = '',
        string $notes = ''
    ): PhoneNumber {
        // Valider les données
        $validator = $this->container->get(PhoneNumberValidator::class);

        try {
            $validatedData = $validator->validateCreate(
                $number,
                $civility,
                $firstName,
                $name,
                $company,
                $sector,
                $notes
            );
        } catch (ValidationException $e) {
            throw new \Exception($e->getMessage() . ': ' . json_encode($e->getErrors()));
        }

        // Créer le numéro de téléphone
        $phoneNumber = new PhoneNumber(
            null,
            $validatedData['number'],
            $validatedData['civility'],
            $validatedData['firstName'],
            $validatedData['name'],
            $validatedData['company'],
            $validatedData['sector'],
            $validatedData['notes']
        );

        // Segmenter le numéro
        $segments = $this->phoneSegmentationService->segmentPhoneNumber($phoneNumber->getNumber());

        // Sauvegarder le numéro
        $savedPhoneNumber = $this->phoneNumberRepository->save($phoneNumber);

        // Sauvegarder les segments
        foreach ($segments as $segment) {
            $segment->setPhoneNumberId($savedPhoneNumber->getId());
            $this->segmentRepository->save($segment);
        }

        return $savedPhoneNumber;
    }

    /**
     * Update a phone number
     * 
     * @Mutation
     * @param int $id
     * @param string $civility
     * @param string $firstName
     * @param string $name
     * @param string $company
     * @param string $sector
     * @param string $notes
     * @return PhoneNumber|null
     */
    public function updatePhoneNumber(
        int $id,
        string $civility = '',
        string $firstName = '',
        string $name = '',
        string $company = '',
        string $sector = '',
        string $notes = ''
    ): ?PhoneNumber {
        // Valider les données
        $validator = $this->container->get(PhoneNumberValidator::class);

        try {
            $validatedData = $validator->validateUpdate(
                $id,
                $civility,
                $firstName,
                $name,
                $company,
                $sector,
                $notes
            );
        } catch (ValidationException $e) {
            throw new \Exception($e->getMessage() . ': ' . json_encode($e->getErrors()));
        }

        // Récupérer le numéro de téléphone
        $phoneNumber = $this->phoneNumberRepository->findById($id);
        if (!$phoneNumber) {
            return null;
        }

        // Mettre à jour les champs
        $phoneNumber->setCivility($validatedData['civility']);
        $phoneNumber->setFirstName($validatedData['firstName']);
        $phoneNumber->setName($validatedData['name']);
        $phoneNumber->setCompany($validatedData['company']);
        $phoneNumber->setSector($validatedData['sector']);
        $phoneNumber->setNotes($validatedData['notes']);

        // Sauvegarder les modifications
        return $this->phoneNumberRepository->save($phoneNumber);
    }

    /**
     * Delete a phone number
     * 
     * @Mutation
     * @param int $id
     * @return bool
     */
    public function deletePhoneNumber(int $id): bool
    {
        // Valider les données
        $validator = $this->container->get(PhoneNumberValidator::class);

        try {
            $validatedData = $validator->validateDelete($id);
        } catch (ValidationException $e) {
            throw new \Exception($e->getMessage() . ': ' . json_encode($e->getErrors()));
        }

        return $this->phoneNumberRepository->delete($validatedData['id']);
    }

    /**
     * Segment a phone number without saving it
     * 
     * @Query
     * @param string $number
     * @return array
     */
    public function segmentPhoneNumber(string $number): array
    {
        // Valider le format du numéro
        if (empty($number)) {
            throw new \Exception("Le numéro de téléphone est requis");
        }

        try {
            return $this->phoneSegmentationService->segmentPhoneNumber($number);
        } catch (\Exception $e) {
            throw new \Exception("Erreur lors de la segmentation du numéro: " . $e->getMessage());
        }
    }

    /**
     * Get phone numbers by segment
     * 
     * @Query
     * @param string $segmentType
     * @param string $segmentValue
     * @param int $limit
     * @param int $offset
     * @return PhoneNumber[]
     */
    public function phoneNumbersBySegment(
        string $segmentType,
        string $segmentValue,
        int $limit = 100,
        int $offset = 0
    ): array {
        return $this->phoneNumberRepository->findBySegment($segmentType, $segmentValue, $limit, $offset);
    }

    /**
     * Count phone numbers by segment
     * 
     * @Query
     * @param string $segmentType
     * @param string $segmentValue
     * @return int
     */
    public function countPhoneNumbersBySegment(string $segmentType, string $segmentValue): int
    {
        return $this->phoneNumberRepository->countBySegment($segmentType, $segmentValue);
    }

    /**
     * Get segment statistics
     * 
     * @Query
     * @param string $segmentType
     * @return array
     */
    public function segmentStatistics(string $segmentType): array
    {
        return $this->segmentRepository->getStatistics($segmentType);
    }
}
