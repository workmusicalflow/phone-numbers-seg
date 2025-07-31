<?php

namespace App\Services;

use App\Exceptions\BatchProcessingException;
use App\Exceptions\RepositoryException;
use App\Entities\PhoneNumber; // Use Doctrine Entity
use App\Repositories\Interfaces\PhoneNumberRepositoryInterface; // Use interface
use App\Repositories\Interfaces\TechnicalSegmentRepositoryInterface; // Use interface
use App\Services\Formatters\BatchResultFormatterInterface;
use App\Services\Interfaces\BatchSegmentationServiceInterface;
use App\Services\Interfaces\PhoneSegmentationServiceInterface;
use App\Services\Interfaces\PhoneNumberValidatorInterface; // Corrected use statement
use Psr\Log\LoggerInterface; // Import LoggerInterface

/**
 * BatchSegmentationService
 * 
 * Service for batch processing of phone numbers
 */
class BatchSegmentationService implements BatchSegmentationServiceInterface
{
    /**
     * @var PhoneSegmentationServiceInterface
     */
    private PhoneSegmentationServiceInterface $segmentationService;

    /**
     * @var PhoneNumberRepositoryInterface
     */
    private PhoneNumberRepositoryInterface $phoneNumberRepository;

    /**
     * @var TechnicalSegmentRepositoryInterface
     */
    private TechnicalSegmentRepositoryInterface $technicalSegmentRepository;

    /**
     * @var PhoneNumberValidatorInterface
     */
    private PhoneNumberValidatorInterface $validator; // Add validator property

    /**
     * @var BatchResultFormatterInterface
     */
    private BatchResultFormatterInterface $resultFormatter;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Constructor
     * 
     * @param PhoneSegmentationServiceInterface $segmentationService
     * @param PhoneNumberRepositoryInterface $phoneNumberRepository
     * @param TechnicalSegmentRepositoryInterface $technicalSegmentRepository
     * @param PhoneNumberValidatorInterface $validator // Inject validator
     * @param BatchResultFormatterInterface $resultFormatter
     * @param LoggerInterface $logger
     */
    public function __construct(
        PhoneSegmentationServiceInterface $segmentationService,
        PhoneNumberRepositoryInterface $phoneNumberRepository, // Use interface
        TechnicalSegmentRepositoryInterface $technicalSegmentRepository, // Use interface
        PhoneNumberValidatorInterface $validator, // Inject validator
        BatchResultFormatterInterface $resultFormatter,
        LoggerInterface $logger // Inject Logger
    ) {
        $this->segmentationService = $segmentationService;
        $this->phoneNumberRepository = $phoneNumberRepository;
        $this->technicalSegmentRepository = $technicalSegmentRepository;
        $this->validator = $validator; // Store validator
        $this->resultFormatter = $resultFormatter;
        $this->logger = $logger; // Store Logger
    }

    /**
     * Process multiple phone numbers without saving to database
     * 
     * @param array $phoneNumbers Array of phone number strings
     * @return array Array of segmented PhoneNumber objects
     * @throws BatchProcessingException If there are errors during processing
     */
    public function processPhoneNumbers(array $phoneNumbers): array
    {
        $this->logger->info("Début du traitement par lot (sans sauvegarde)", ['count' => count($phoneNumbers)]);
        $results = [];
        $errors = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($phoneNumbers as $index => $number) {
            $this->logger->debug("Traitement du numéro (sans sauvegarde)", ['index' => $index, 'number' => $number]);
            try {
                // Validate the raw number string first
                if (!$this->validator->isValid($number)) { // Assuming validator is injected and has isValid method
                    $errorMessage = 'Format de numéro invalide';
                    $this->logger->warning($errorMessage, ['index' => $index, 'number' => $number]);
                    $errors[$index] = [
                        'number' => $number,
                        'error' => $errorMessage
                    ];
                    $failureCount++;
                    continue;
                }
                $this->logger->debug("Numéro valide, création de l'entité et segmentation...", ['index' => $index, 'number' => $number]);

                // Create the Doctrine Entity
                $phoneNumberEntity = new PhoneNumber();
                $phoneNumberEntity->setNumber($number); // Assuming a setter exists

                $segmentedPhoneNumber = $this->segmentationService->segmentPhoneNumber($phoneNumberEntity); // Pass the entity
                $results[$index] = $segmentedPhoneNumber; // Store the segmented entity
                $successCount++;
                $this->logger->debug("Segmentation réussie (sans sauvegarde)", ['index' => $index, 'number' => $number]);
            } catch (\Exception $e) {
                $errorMessage = "Échec du traitement (sans sauvegarde): " . $e->getMessage();
                $this->logger->error($errorMessage, ['index' => $index, 'number' => $number, 'exception' => $e]);
                $errors[$index] = [
                    'number' => $number,
                    'error' => $e->getMessage()
                ];
                $failureCount++;
            }
        }

        $processResults = [
            'results' => $results,
            'errors' => $errors
        ];
        $this->logger->info("Traitement par lot (sans sauvegarde) terminé", [
            'total' => count($phoneNumbers),
            'successful' => $successCount,
            'failed' => $failureCount
        ]);

        // If all numbers failed, throw an exception
        if (empty($results) && !empty($errors)) {
            $this->logger->error("Tous les numéros ont échoué au traitement (sans sauvegarde)");
            throw new BatchProcessingException(
                'Tous les numéros ont échoué au traitement',
                $errors
            );
        }

        return $processResults;
    }

    /**
     * Process and save multiple phone numbers to database
     * 
     * @param array $phoneNumbers Array of phone number strings
     * @return array Array of results and errors
     * @throws BatchProcessingException If there are errors during processing
     * @throws RepositoryException If there are errors during database operations
     */
    public function processAndSavePhoneNumbers(array $phoneNumbers): array
    {
        $this->logger->info("Début du traitement par lot (avec sauvegarde)", ['count' => count($phoneNumbers)]);
        $results = [];
        $errors = [];
        $successCount = 0;
        $failureCount = 0;
        $duplicateCount = 0;

        foreach ($phoneNumbers as $index => $number) {
            $this->logger->debug("Traitement du numéro (avec sauvegarde)", ['index' => $index, 'number' => $number]);
            try {
                // Validate the raw number string first
                if (!$this->validator->isValid($number)) { // Assuming validator is injected and has isValid method
                    $errorMessage = 'Format de numéro invalide';
                    $this->logger->warning($errorMessage, ['index' => $index, 'number' => $number]);
                    $errors[$index] = [
                        'number' => $number,
                        'error' => $errorMessage
                    ];
                    $failureCount++;
                    continue;
                }
                $this->logger->debug("Numéro valide, vérification existence...", ['index' => $index, 'number' => $number]);

                // Check if the phone number already exists using the raw number
                $existingPhoneNumber = $this->phoneNumberRepository->findByNumber($number);
                if ($existingPhoneNumber) {
                    $errorMessage = 'Numéro déjà existant';
                    $this->logger->info($errorMessage, ['index' => $index, 'number' => $number]);
                    $errors[$index] = [
                        'number' => $number,
                        'error' => $errorMessage
                    ];
                    $duplicateCount++; // Count as a specific type of failure/skip
                    continue;
                }
                $this->logger->debug("Numéro non existant, création de l'entité et segmentation...", ['index' => $index, 'number' => $number]);

                // Create the Doctrine Entity
                $phoneNumberEntity = new PhoneNumber();
                $phoneNumberEntity->setNumber($number); // Assuming a setter exists

                // Segment the phone number entity
                $segmentedPhoneNumber = $this->segmentationService->segmentPhoneNumber($phoneNumberEntity); // Pass the entity
                $this->logger->debug("Segmentation réussie, sauvegarde...", ['index' => $index, 'number' => $number]);

                // Save the segmented phone number entity
                try {
                    $savedPhoneNumber = $this->phoneNumberRepository->save($segmentedPhoneNumber);
                    $results[$index] = $savedPhoneNumber;
                    $successCount++;
                    $this->logger->info("Numéro sauvegardé avec succès", ['index' => $index, 'number' => $number, 'id' => $savedPhoneNumber->getId()]);
                } catch (\Exception $e) {
                    // Log the repository error specifically
                    $repoErrorMessage = 'Échec de la sauvegarde du numéro: ' . $e->getMessage();
                    $this->logger->error($repoErrorMessage, ['index' => $index, 'number' => $number, 'exception' => $e]);
                    // Throw a specific RepositoryException to signal DB issues
                    throw new RepositoryException(
                        $repoErrorMessage,
                        $e->getCode(),
                        $e
                    );
                }
            } catch (RepositoryException $e) {
                // Log and re-throw repository exceptions immediately
                $this->logger->critical("Erreur Repository lors du traitement par lot", ['index' => $index, 'number' => $number, 'exception' => $e]);
                throw $e;
            } catch (\Exception $e) {
                // Catch other general processing errors (e.g., from segmentation)
                $errorMessage = "Échec du traitement (avec sauvegarde): " . $e->getMessage();
                $this->logger->error($errorMessage, ['index' => $index, 'number' => $number, 'exception' => $e]);
                $errors[$index] = [
                    'number' => $number,
                    'error' => $e->getMessage()
                ];
                $failureCount++;
            }
        }

        $processResults = [
            'results' => $results,
            'errors' => $errors
            // Optionally add counts to the result: 'successful' => $successCount, 'failed' => $failureCount, 'duplicates' => $duplicateCount
        ];
        $this->logger->info("Traitement par lot (avec sauvegarde) terminé", [
            'total' => count($phoneNumbers),
            'successful' => $successCount,
            'failed' => $failureCount,
            'duplicates' => $duplicateCount
        ]);

        // If all numbers failed or were duplicates, throw an exception
        if (empty($results) && (!empty($errors) || $duplicateCount > 0)) {
            $this->logger->error("Tous les numéros ont échoué au traitement/sauvegarde ou étaient des doublons");
            throw new BatchProcessingException(
                'Tous les numéros ont échoué au traitement/sauvegarde ou étaient des doublons',
                $errors // Pass errors along
            );
        }

        return $processResults;
    }
}
