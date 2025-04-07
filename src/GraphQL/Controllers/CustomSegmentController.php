<?php

namespace App\GraphQL\Controllers;

use App\Models\CustomSegment;
use App\Repositories\CustomSegmentRepository;
use App\Repositories\PhoneNumberRepository;
use App\Services\Interfaces\CustomSegmentMatcherInterface;
use App\Services\Validators\CustomSegmentValidator;
use App\Exceptions\ValidationException;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Right;

/**
 * Contrôleur GraphQL pour les segments personnalisés
 */
class CustomSegmentController
{
    /**
     * @var CustomSegmentRepository
     */
    private $customSegmentRepository;

    /**
     * @var PhoneNumberRepository
     */
    private $phoneNumberRepository;

    /**
     * @var CustomSegmentMatcherInterface
     */
    private $customSegmentMatcher;

    /**
     * @var CustomSegmentValidator
     */
    private $customSegmentValidator;

    /**
     * Constructeur
     * 
     * @param CustomSegmentRepository $customSegmentRepository
     * @param PhoneNumberRepository $phoneNumberRepository
     * @param CustomSegmentMatcherInterface $customSegmentMatcher
     * @param CustomSegmentValidator $customSegmentValidator
     */
    public function __construct(
        CustomSegmentRepository $customSegmentRepository,
        PhoneNumberRepository $phoneNumberRepository,
        CustomSegmentMatcherInterface $customSegmentMatcher,
        CustomSegmentValidator $customSegmentValidator
    ) {
        $this->customSegmentRepository = $customSegmentRepository;
        $this->phoneNumberRepository = $phoneNumberRepository;
        $this->customSegmentMatcher = $customSegmentMatcher;
        $this->customSegmentValidator = $customSegmentValidator;
    }

    /**
     * Récupérer un segment personnalisé par son ID
     * 
     * @Query
     * @param int $id
     * @return CustomSegment
     */
    public function customSegment(int $id): ?CustomSegment
    {
        return $this->customSegmentRepository->findById($id);
    }

    /**
     * Récupérer tous les segments personnalisés
     * 
     * @Query
     * @return CustomSegment[]
     */
    public function customSegments(): array
    {
        return $this->customSegmentRepository->findAll();
    }

    /**
     * Créer un nouveau segment personnalisé
     * 
     * @Mutation
     * @param string $name
     * @param string $pattern
     * @param string $description
     * @return CustomSegment
     */
    public function createCustomSegment(
        string $name,
        string $pattern,
        string $description = ''
    ): CustomSegment {
        try {
            // Valider les données
            $validatedData = $this->customSegmentValidator->validateCreateWithPattern(
                $name,
                $pattern,
                $description
            );

            // Créer le segment personnalisé
            $customSegment = new CustomSegment(
                $validatedData['name'],
                $validatedData['pattern'],
                $validatedData['description']
            );

            // Sauvegarder le segment personnalisé
            return $this->customSegmentRepository->save($customSegment);
        } catch (ValidationException $e) {
            throw new \Exception($e->getMessage() . ': ' . json_encode($e->getErrors()));
        }
    }

    /**
     * Mettre à jour un segment personnalisé
     * 
     * @Mutation
     * @param int $id
     * @param string $name
     * @param string $pattern
     * @param string $description
     * @return CustomSegment
     */
    public function updateCustomSegment(
        int $id,
        string $name,
        string $pattern,
        string $description = ''
    ): CustomSegment {
        try {
            // Valider les données
            $validatedData = $this->customSegmentValidator->validateUpdate($id, [
                'name' => $name,
                'pattern' => $pattern,
                'description' => $description
            ]);

            // Récupérer le segment personnalisé
            $customSegment = $this->customSegmentRepository->findById($validatedData['id']);
            if (!$customSegment) {
                throw new \Exception("Segment personnalisé non trouvé");
            }

            // Mettre à jour le segment personnalisé
            $customSegment->setName($validatedData['name']);
            $customSegment->setPattern($validatedData['pattern']);
            $customSegment->setDescription($validatedData['description']);

            // Sauvegarder le segment personnalisé
            return $this->customSegmentRepository->save($customSegment);
        } catch (ValidationException $e) {
            throw new \Exception($e->getMessage() . ': ' . json_encode($e->getErrors()));
        }
    }

    /**
     * Supprimer un segment personnalisé
     * 
     * @Mutation
     * @param int $id
     * @return bool
     */
    public function deleteCustomSegment(int $id): bool
    {
        try {
            // Valider les données
            $validatedData = $this->customSegmentValidator->validateDelete($id);

            // Supprimer le segment personnalisé
            return $this->customSegmentRepository->delete($validatedData['id']);
        } catch (ValidationException $e) {
            throw new \Exception($e->getMessage() . ': ' . json_encode($e->getErrors()));
        }
    }

    /**
     * Valider une expression régulière
     * 
     * @Query
     * @param string $pattern
     * @return bool
     */
    public function validateRegex(string $pattern): bool
    {
        return $this->customSegmentValidator->validateRegex($pattern);
    }

    /**
     * Tester un numéro de téléphone avec un segment personnalisé
     * 
     * @Query
     * @param int $segmentId
     * @param string $phoneNumber
     * @return bool
     */
    public function testPhoneNumberWithSegment(int $segmentId, string $phoneNumber): bool
    {
        // Récupérer le segment personnalisé
        $customSegment = $this->customSegmentRepository->findById($segmentId);
        if (!$customSegment) {
            throw new \Exception("Segment personnalisé non trouvé");
        }

        // Tester le numéro de téléphone
        return $this->customSegmentMatcher->matches($phoneNumber, $customSegment->getPattern());
    }

    /**
     * Récupérer les numéros de téléphone correspondant à un segment personnalisé
     * 
     * @Query
     * @param int $segmentId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function phoneNumbersMatchingSegment(
        int $segmentId,
        int $limit = 100,
        int $offset = 0
    ): array {
        // Récupérer le segment personnalisé
        $customSegment = $this->customSegmentRepository->findById($segmentId);
        if (!$customSegment) {
            throw new \Exception("Segment personnalisé non trouvé");
        }

        // Récupérer tous les numéros de téléphone
        $phoneNumbers = $this->phoneNumberRepository->findAll($limit, $offset);

        // Filtrer les numéros de téléphone qui correspondent au segment
        $matchingPhoneNumbers = [];
        foreach ($phoneNumbers as $phoneNumber) {
            if ($this->customSegmentMatcher->matches($phoneNumber->getNumber(), $customSegment->getPattern())) {
                $matchingPhoneNumbers[] = $phoneNumber;
            }
        }

        return $matchingPhoneNumbers;
    }

    /**
     * Compter les numéros de téléphone correspondant à un segment personnalisé
     * 
     * @Query
     * @param int $segmentId
     * @return int
     */
    public function countPhoneNumbersMatchingSegment(int $segmentId): int
    {
        // Récupérer le segment personnalisé
        $customSegment = $this->customSegmentRepository->findById($segmentId);
        if (!$customSegment) {
            throw new \Exception("Segment personnalisé non trouvé");
        }

        // Récupérer tous les numéros de téléphone
        $phoneNumbers = $this->phoneNumberRepository->findAll();

        // Compter les numéros de téléphone qui correspondent au segment
        $count = 0;
        foreach ($phoneNumbers as $phoneNumber) {
            if ($this->customSegmentMatcher->matches($phoneNumber->getNumber(), $customSegment->getPattern())) {
                $count++;
            }
        }

        return $count;
    }
}
