<?php

namespace App\Services;

use App\Entities\Contact; // Use Doctrine Entity
use App\Entities\PhoneNumber; // Use Doctrine Entity
use App\Repositories\Interfaces\ContactRepositoryInterface; // Use interface
use App\Repositories\Interfaces\PhoneNumberRepositoryInterface; // Use interface
use App\Repositories\Interfaces\ContactGroupMembershipRepositoryInterface; // Add membership repository
use App\Services\Interfaces\PhoneSegmentationServiceInterface;
use Psr\Log\LoggerInterface; // Import LoggerInterface

/**
 * Service for importing phone numbers from CSV files or arrays.
 */
class CSVImportService
{
    /**
     * @var PhoneNumberRepositoryInterface
     */
    private PhoneNumberRepositoryInterface $phoneNumberRepository;

    /**
     * @var PhoneSegmentationServiceInterface
     */
    private PhoneSegmentationServiceInterface $segmentationService;

    /**
     * @var ContactRepositoryInterface
     */
    private ContactRepositoryInterface $contactRepository;

    /**
     * @var ContactGroupMembershipRepositoryInterface
     */
    private ContactGroupMembershipRepositoryInterface $membershipRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var array Current import options
     */
    private array $options = [];

    /**
     * @var int Maximum number of phone numbers to process in a single batch
     */
    private const MAX_BATCH_SIZE = 5000;

    /**
     * @var array Validation errors
     */
    private array $errors = [];

    /**
     * @var array Detailed errors with line numbers and values
     */
    private array $detailedErrors = [];

    /**
     * @var int Maximum number of detailed errors to collect
     */
    private int $maxDetailedErrors = 20;

    /**
     * @var int Current line being processed
     */
    private int $currentLine = 0;

    /**
     * @var array Import statistics
     */
    private array $stats = [
        'total' => 0,
        'valid' => 0,
        'invalid' => 0,
        'duplicates' => 0,
        'processed' => 0,
        'contactsCreated' => 0,
        'contactsDuplicates' => 0,
        'groupAssignments' => 0,
        'groupAssignmentErrors' => 0
    ];

    /**
     * Constructor
     * 
     * @param PhoneNumberRepositoryInterface $phoneNumberRepository
     * @param PhoneSegmentationServiceInterface $segmentationService
     * @param ContactRepositoryInterface $contactRepository
     * @param ContactGroupMembershipRepositoryInterface $membershipRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        PhoneNumberRepositoryInterface $phoneNumberRepository,
        PhoneSegmentationServiceInterface $segmentationService,
        ContactRepositoryInterface $contactRepository,
        ContactGroupMembershipRepositoryInterface $membershipRepository,
        LoggerInterface $logger // Inject Logger
    ) {
        $this->phoneNumberRepository = $phoneNumberRepository;
        $this->segmentationService = $segmentationService;
        $this->contactRepository = $contactRepository;
        $this->membershipRepository = $membershipRepository;
        $this->logger = $logger; // Store Logger
    }

    /**
     * Import phone numbers from a CSV file
     * 
     * @param string $filePath Path to the CSV file
     * @param array $options Import options
     * @return array Import results
     */
    public function importFromFile(string $filePath, array $options = []): array
    {
        $this->logger->info("Début de l'importation CSV depuis le fichier", ['filePath' => $filePath, 'options' => $options]);

        // Reset statistics, errors and line counter
        $this->resetStats();
        $this->currentLine = 0;
        $this->detailedErrors = [];

        // Check if file exists
        if (!file_exists($filePath)) {
            $errorMessage = "Fichier non trouvé: {$filePath}";
            $this->logger->error($errorMessage);
            $this->errors[] = $errorMessage;
            return $this->getResults();
        }

        // Check file extension - more permissive, accepts files without extension
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $this->logger->debug("Extension du fichier détectée", ['extension' => $extension]);
        // Allow empty extension or 'csv'
        if ($extension && strtolower($extension) !== 'csv') {
            $errorMessage = "Format de fichier invalide. Seuls les fichiers CSV (ou sans extension) sont supportés. Extension détectée: {$extension}";
            $this->logger->error($errorMessage, ['filePath' => $filePath]);
            $this->errors[] = $errorMessage;
            return $this->getResults();
        }

        // Set default options
        $this->options = array_merge([
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
            'hasHeader' => true,
            'phoneColumn' => 0,
            'civilityColumn' => -1, // -1 means not specified
            'firstNameColumn' => -1, // -1 means not specified
            'nameColumn' => -1, // -1 means not specified
            'companyColumn' => -1, // -1 means not specified
            'sectorColumn' => -1, // -1 means not specified
            'notesColumn' => -1, // -1 means not specified
            'emailColumn' => -1, // -1 means not specified
            'skipInvalid' => true,
            'batchSize' => 200, // Reduced default batch size
            'segmentImmediately' => true,
            'createContacts' => true, // Create contacts by default
            'userId' => null, // User ID to associate contacts with
            'defaultUserId' => 2, // Default to AfricaQSHE (ID 2)
            'groupIds' => [] // Array of group IDs to assign contacts to
        ], $options);
        $this->logger->debug("Options d'importation finales", ['options' => $this->options]);

        try {
            $this->logger->info("Ouverture du fichier CSV", ['filePath' => $filePath]);
            // Open the file
            $handle = fopen($filePath, 'r');
            if ($handle === false) {
                $errorMessage = "Échec de l'ouverture du fichier: {$filePath}";
                $this->logger->error($errorMessage);
                $this->errors[] = $errorMessage;
                return $this->getResults();
            }

            // Skip header if needed
            if ($this->options['hasHeader']) {
                $this->logger->debug("Saut de l'en-tête");
                fgetcsv($handle, 0, $this->options['delimiter'], $this->options['enclosure'], $this->options['escape']);
                $this->currentLine = 1; // Set to 1 after skipping header
            } else {
                $this->logger->debug("Pas d'en-tête à sauter");
                $this->currentLine = 0;
            }

            $batch = [];
            // $lineNumber = $this->options['hasHeader'] ? 2 : 1; // lineNumber is redundant with currentLine
            // $this->currentLine = $lineNumber - 1; // Initialize current line - already done above

            $this->logger->info("Début du traitement des lignes du fichier");
            // Process each line
            while (($data = fgetcsv($handle, 0, $this->options['delimiter'], $this->options['enclosure'], $this->options['escape'])) !== false) {
                $this->stats['total']++;
                $this->currentLine++; // Increment line counter for each processed line
                $this->logger->debug("Traitement de la ligne", ['lineNumber' => $this->currentLine]);

                // Validate column indices against actual data
                $columnCount = count($data);
                $phoneColumnIndex = $this->options['phoneColumn'];

                // Check if phone column index is valid
                if ($phoneColumnIndex < 0 || $phoneColumnIndex >= $columnCount) {
                    $errorMessage = "Index de colonne téléphone invalide: {$phoneColumnIndex}. Le fichier n'a que {$columnCount} colonnes.";
                    $this->logger->warning($errorMessage, ['lineNumber' => $this->currentLine]);
                    if (count($this->detailedErrors) < $this->maxDetailedErrors) {
                        $this->detailedErrors[] = [
                            'line' => $this->currentLine,
                            'value' => 'N/A',
                            'message' => $errorMessage
                        ];
                    }
                    $this->stats['invalid']++;
                    // $lineNumber++; // Redundant
                    continue;
                }

                // Get phone number from the specified column
                $phoneNumber = isset($data[$phoneColumnIndex]) ? trim($data[$phoneColumnIndex]) : '';

                // Validate phone number
                if (empty($phoneNumber)) {
                    $errorMessage = "Numéro de téléphone vide";
                    $this->logger->warning($errorMessage, ['lineNumber' => $this->currentLine]);
                    $this->stats['invalid']++;
                    if (!$this->options['skipInvalid']) {
                        $this->errors[] = "Ligne {$this->currentLine}: Numéro de téléphone vide";
                    }

                    // Add detailed error
                    if (count($this->detailedErrors) < $this->maxDetailedErrors) {
                        $this->detailedErrors[] = [
                            'line' => $this->currentLine,
                            'value' => 'empty',
                            'message' => $errorMessage
                        ];
                    }

                    // $lineNumber++; // Redundant
                    continue;
                }

                // Normalize phone number
                $this->logger->debug("Normalisation du numéro", ['lineNumber' => $this->currentLine, 'original' => $phoneNumber]);
                $normalizedNumber = $this->normalizePhoneNumber($phoneNumber);
                if ($normalizedNumber === null) {
                    $errorMessage = "Format de numéro invalide";
                    $this->logger->warning($errorMessage, ['lineNumber' => $this->currentLine, 'value' => $phoneNumber]);
                    $this->stats['invalid']++;
                    if (!$this->options['skipInvalid']) {
                        $this->errors[] = "Ligne {$this->currentLine}: Format de numéro invalide: {$phoneNumber}";
                    }

                    // Add detailed error
                    if (count($this->detailedErrors) < $this->maxDetailedErrors) {
                        $this->detailedErrors[] = [
                            'line' => $this->currentLine,
                            'value' => $phoneNumber,
                            'message' => $errorMessage
                        ];
                    }

                    // $lineNumber++; // Redundant
                    continue;
                }
                $this->logger->debug("Numéro normalisé", ['lineNumber' => $this->currentLine, 'normalized' => $normalizedNumber]);

                // Check for duplicates in the current batch
                if (in_array($normalizedNumber, array_column($batch, 'number'))) {
                    $errorMessage = "Doublon détecté dans le fichier (batch actuel)";
                    $this->logger->warning($errorMessage, ['lineNumber' => $this->currentLine, 'value' => $normalizedNumber]);
                    $this->stats['duplicates']++;
                    if (!$this->options['skipInvalid']) {
                        $this->errors[] = "Ligne {$this->currentLine}: Doublon détecté dans le fichier: {$normalizedNumber}";
                    }

                    // Add detailed error
                    if (count($this->detailedErrors) < $this->maxDetailedErrors) {
                        $this->detailedErrors[] = [
                            'line' => $this->currentLine,
                            'value' => $normalizedNumber,
                            'message' => $errorMessage
                        ];
                    }

                    // $lineNumber++; // Redundant
                    continue;
                }

                // Extract additional fields if columns are specified
                $additionalFields = [
                    'civility' => $this->getColumnValue($data, $this->options['civilityColumn'], $columnCount),
                    'firstName' => $this->getColumnValue($data, $this->options['firstNameColumn'], $columnCount),
                    'name' => $this->getColumnValue($data, $this->options['nameColumn'], $columnCount),
                    'company' => $this->getColumnValue($data, $this->options['companyColumn'], $columnCount),
                    'sector' => $this->getColumnValue($data, $this->options['sectorColumn'], $columnCount),
                    'notes' => $this->getColumnValue($data, $this->options['notesColumn'], $columnCount),
                    'email' => $this->getColumnValue($data, $this->options['emailColumn'], $columnCount)
                ];
                $this->logger->debug("Champs additionnels extraits", ['lineNumber' => $this->currentLine, 'fields' => $additionalFields]);

                // Add to batch with additional fields
                $batch[] = [
                    'number' => $normalizedNumber,
                    'fields' => $additionalFields
                ];
                $this->stats['valid']++;

                // Process batch if it reaches the maximum size
                if (count($batch) >= $this->options['batchSize']) {
                    $this->logger->info("Traitement du batch", ['batchSize' => count($batch), 'currentLine' => $this->currentLine]);
                    $this->processBatch($batch, $this->options['segmentImmediately']);
                    $batch = []; // Reset batch
                }

                // $lineNumber++; // Redundant
            }
            $this->logger->info("Fin du traitement des lignes");

            // Process remaining batch
            if (!empty($batch)) {
                $this->logger->info("Traitement du batch final", ['batchSize' => count($batch)]);
                $this->processBatch($batch, $this->options['segmentImmediately']);
            }

            $this->logger->info("Fermeture du fichier CSV", ['filePath' => $filePath]);
            fclose($handle);
        } catch (\Exception $e) {
            $errorMessage = "Erreur lors du traitement du fichier CSV: " . $e->getMessage();
            $this->logger->error($errorMessage, ['filePath' => $filePath, 'lineNumber' => $this->currentLine, 'exception' => $e]);
            $this->errors[] = $errorMessage;

            // Add detailed error for exception
            if (count($this->detailedErrors) < $this->maxDetailedErrors) {
                $this->detailedErrors[] = [
                    'line' => $this->currentLine,
                    'value' => 'N/A',
                    'message' => "Erreur système: " . $e->getMessage()
                ];
            }
        }

        $this->logger->info("Importation depuis fichier terminée", ['filePath' => $filePath, 'results' => $this->getResults()]);
        return $this->getResults();
    }

    /**
     * Import phone numbers from an array
     * 
     * @param array $numbers Array of phone numbers
     * @param array $options Import options
     * @return array Import results
     */
    public function importFromArray(array $numbers, array $options = []): array
    {
        $this->logger->info("Début de l'importation depuis un tableau", ['count' => count($numbers), 'options' => $options]);
        // Reset statistics and errors
        $this->resetStats();

        // Set default options
        $this->options = array_merge([
            'skipInvalid' => true,
            'batchSize' => self::MAX_BATCH_SIZE,
            'segmentImmediately' => true,
            'createContacts' => true, // Create contacts by default
            'userId' => null, // User ID to associate contacts with
            'defaultUserId' => 2, // Default to AfricaQSHE (ID 2)
            'groupIds' => [] // Array of group IDs to assign contacts to
        ], $options);
        $this->logger->debug("Options d'importation finales (tableau)", ['options' => $this->options]);

        try {
            $batch = [];
            $this->stats['total'] = count($numbers);
            $this->logger->info("Début du traitement des numéros du tableau");

            // Process each number
            foreach ($numbers as $index => $phoneNumber) {
                $this->logger->debug("Traitement de l'index", ['index' => $index]);
                $phoneNumber = trim($phoneNumber);

                // Validate phone number
                if (empty($phoneNumber)) {
                    $errorMessage = "Numéro de téléphone vide";
                    $this->logger->warning($errorMessage, ['index' => $index]);
                    $this->stats['invalid']++;
                    if (!$this->options['skipInvalid']) {
                        $this->errors[] = "Index {$index}: Numéro de téléphone vide";
                    }
                    continue;
                }

                // Normalize phone number
                $this->logger->debug("Normalisation du numéro", ['index' => $index, 'original' => $phoneNumber]);
                $normalizedNumber = $this->normalizePhoneNumber($phoneNumber);
                if ($normalizedNumber === null) {
                    $errorMessage = "Format de numéro invalide";
                    $this->logger->warning($errorMessage, ['index' => $index, 'value' => $phoneNumber]);
                    $this->stats['invalid']++;
                    if (!$this->options['skipInvalid']) {
                        $this->errors[] = "Index {$index}: Format de numéro invalide: {$phoneNumber}";
                    }
                    continue;
                }
                $this->logger->debug("Numéro normalisé", ['index' => $index, 'normalized' => $normalizedNumber]);

                // Check for duplicates in the current batch
                if (in_array($normalizedNumber, array_column($batch, 'number'))) {
                    $errorMessage = "Doublon détecté dans le tableau (batch actuel)";
                    $this->logger->warning($errorMessage, ['index' => $index, 'value' => $normalizedNumber]);
                    $this->stats['duplicates']++;
                    if (!$this->options['skipInvalid']) {
                        $this->errors[] = "Index {$index}: Doublon détecté dans le tableau: {$normalizedNumber}";
                    }
                    continue;
                }

                // Add to batch with empty additional fields
                $batch[] = [
                    'number' => $normalizedNumber,
                    'fields' => [
                        'civility' => null,
                        'firstName' => null,
                        'name' => null,
                        'company' => null,
                        'sector' => null,
                        'notes' => null,
                        'email' => null
                    ]
                ];
                $this->stats['valid']++;

                // Process batch if it reaches the maximum size
                if (count($batch) >= $this->options['batchSize']) {
                    $this->logger->info("Traitement du batch (tableau)", ['batchSize' => count($batch), 'currentIndex' => $index]);
                    $this->processBatch($batch, $this->options['segmentImmediately']);
                    $batch = []; // Reset batch
                }
            }
            $this->logger->info("Fin du traitement des numéros du tableau");

            // Process remaining batch
            if (!empty($batch)) {
                $this->logger->info("Traitement du batch final (tableau)", ['batchSize' => count($batch)]);
                $this->processBatch($batch, $this->options['segmentImmediately']);
            }
        } catch (\Exception $e) {
            $errorMessage = "Erreur lors du traitement des numéros du tableau: " . $e->getMessage();
            $this->logger->error($errorMessage, ['exception' => $e]);
            $this->errors[] = $errorMessage;
        }

        $this->logger->info("Importation depuis tableau terminée", ['results' => $this->getResults()]);
        return $this->getResults();
    }

    /**
     * Helper method to safely get a value from a column
     * 
     * @param array $data Row data
     * @param int $columnIndex Column index
     * @param int $columnCount Total number of columns
     * @return string|null Column value or null if not available
     */
    private function getColumnValue(array $data, int $columnIndex, int $columnCount): ?string
    {
        if ($columnIndex >= 0 && $columnIndex < $columnCount && isset($data[$columnIndex])) {
            $value = trim($data[$columnIndex]);
            return !empty($value) ? $value : null;
        }
        return null;
    }

    /**
     * Process a batch of phone numbers
     * 
     * @param array $batch Array of normalized phone numbers with additional fields
     * @param bool $segment Whether to segment the numbers immediately
     * @return void
     */
    private function processBatch(array $batch, bool $segment = true): void
    {
        $this->logger->debug("Début du traitement du batch", ['count' => count($batch), 'segment' => $segment]);
        // Store phone numbers in the database
        foreach ($batch as $item) {
            try {
                $number = $item['number'];
                $fields = $item['fields'];
                $this->logger->debug("Traitement de l'élément du batch", ['number' => $number, 'fields' => $fields]);

                // Check if the number already exists in the database
                $this->logger->debug("Vérification de l'existence du numéro", ['number' => $number]);
                $existingNumber = $this->phoneNumberRepository->findByNumber($number);
                $phoneNumber = null;

                if ($existingNumber === null) {
                    $this->logger->info("Numéro non trouvé, création...", ['number' => $number]);
                    // Create a new phone number entity with additional fields
                    $phoneNumber = new PhoneNumber(); // Instantiate Doctrine Entity
                    $phoneNumber->setNumber($number);
                    $phoneNumber->setCivility($fields['civility']);
                    $phoneNumber->setFirstName($fields['firstName']);
                    $phoneNumber->setName($fields['name']);
                    $phoneNumber->setCompany($fields['company']);
                    $phoneNumber->setSector($fields['sector']);
                    $phoneNumber->setNotes($fields['notes']);
                    // Note: Email field is collected but PhoneNumber entity doesn't have email property
                    // If needed in the future, extend the PhoneNumber entity to include email

                    $this->phoneNumberRepository->save($phoneNumber); // Save Doctrine Entity
                    $this->logger->info("Nouveau numéro enregistré", ['number' => $number, 'id' => $phoneNumber->getId()]);

                    // Segment the number if requested
                    if ($segment) {
                        $this->logger->debug("Segmentation du nouveau numéro", ['number' => $number]);
                        $this->segmentationService->segmentPhoneNumber($phoneNumber);
                        $this->logger->debug("Segmentation terminée", ['number' => $number]);
                    }
                } else {
                    $this->logger->info("Numéro déjà existant", ['number' => $number, 'id' => $existingNumber->getId()]);
                    // Number already exists, increment duplicates count
                    $this->stats['duplicates']++;
                    $phoneNumber = $existingNumber;

                    // Add detailed error for duplicate
                    if (count($this->detailedErrors) < $this->maxDetailedErrors) {
                        $this->detailedErrors[] = [
                            'line' => 'unknown', // Line number not available in batch context
                            'value' => $number,
                            'message' => "Numéro déjà existant dans la base de données"
                        ];
                    }

                    // Optionally update existing record with new information if provided
                    $updated = false;
                    $this->logger->debug("Vérification des champs à mettre à jour pour le numéro existant", ['number' => $number, 'fields' => $fields]);
                    if (!empty(array_filter($fields, function ($value) {
                        return $value !== null;
                    }))) {
                        if ($fields['civility'] !== null && $existingNumber->getCivility() === null) {
                            $existingNumber->setCivility($fields['civility']);
                            $updated = true;
                        }
                        if ($fields['firstName'] !== null && $existingNumber->getFirstName() === null) {
                            $existingNumber->setFirstName($fields['firstName']);
                            $updated = true;
                        }
                        if ($fields['name'] !== null && $existingNumber->getName() === null) {
                            $existingNumber->setName($fields['name']);
                            $updated = true;
                        }
                        if ($fields['company'] !== null && $existingNumber->getCompany() === null) {
                            $existingNumber->setCompany($fields['company']);
                            $updated = true;
                        }
                        if ($fields['sector'] !== null && $existingNumber->getSector() === null) {
                            $existingNumber->setSector($fields['sector']);
                            $updated = true;
                        }
                        if ($fields['notes'] !== null && $existingNumber->getNotes() === null) {
                            $existingNumber->setNotes($fields['notes']);
                            $updated = true;
                        }

                        if ($updated) {
                            $this->logger->info("Mise à jour du numéro existant", ['number' => $number, 'id' => $existingNumber->getId()]);
                            $this->phoneNumberRepository->save($existingNumber);
                        } else {
                            $this->logger->debug("Aucune mise à jour nécessaire pour le numéro existant", ['number' => $number]);
                        }
                    }
                }

                // Create contact if option is enabled and we have a user ID
                $createContacts = $this->options['createContacts'] ?? true;
                $userId = $this->options['userId'] ?? $this->options['defaultUserId'] ?? null;

                if ($createContacts && $userId && $phoneNumber) {
                    $this->logger->debug("Tentative de création/vérification du contact", ['number' => $number, 'userId' => $userId]);
                    try {
                        // Check if a contact with this number exists already for this user using findByCriteria
                        $this->logger->debug("Vérification de l'existence du contact via findByCriteria", ['number' => $number, 'userId' => $userId]);
                        $existingContacts = $this->contactRepository->findByCriteria(
                            ['phoneNumber' => $number, 'userId' => $userId],
                            1 // Limit to 1, we only need to know if it exists
                        );
                        $contactExists = !empty($existingContacts);

                        if (!$contactExists) {
                            $this->logger->info("Contact non trouvé, création...", ['number' => $number, 'userId' => $userId]);
                            // Prepare the name for the contact
                            $firstName = $fields['firstName'] ?? '';
                            $lastName = $fields['name'] ?? '';
                            $name = trim("$firstName $lastName");

                            if (empty($name)) {
                                // Generate a name from the number if no name is provided
                                $lastDigits = substr($number, -6);
                                $name = "Contact " . $lastDigits;
                                $this->logger->debug("Nom du contact généré à partir du numéro", ['name' => $name]);
                            }

                            // Create the contact entity
                            $contact = new Contact(); // Instantiate Doctrine Entity
                            $contact->setUserId($userId);
                            $contact->setName($name);
                            $contact->setPhoneNumber($number);
                            $contact->setEmail($fields['email'] ?? null);
                            $contact->setNotes($fields['notes'] ?? null);
                            $contact->setCreatedAt(new \DateTime()); // Assuming createdAt is set here or in save

                            $this->contactRepository->save($contact); // Save Doctrine Entity
                            $this->stats['contactsCreated']++;
                            $this->logger->info("Contact créé avec succès", ['number' => $number, 'userId' => $userId, 'contactId' => $contact->getId()]);
                        } else {
                            $this->logger->info("Contact déjà existant pour ce numéro et cet utilisateur", ['number' => $number, 'userId' => $userId]);
                            $this->stats['contactsDuplicates']++;
                        }
                    } catch (\Exception $e) {
                        // Log the error but continue processing
                        $errorMessage = "Erreur lors de la création du contact pour le numéro {$number}: " . $e->getMessage();
                        $this->logger->error($errorMessage, ['number' => $number, 'userId' => $userId, 'exception' => $e]);
                        // error_log("Error creating contact for number {$number}: " . $e->getMessage()); // Replaced by logger

                        // Add detailed error for contact creation exception
                        if (count($this->detailedErrors) < $this->maxDetailedErrors) {
                            $this->detailedErrors[] = [
                                'line' => 'unknown',
                                'value' => $number,
                                'message' => "Erreur lors de la création du contact: " . $e->getMessage()
                            ];
                        }
                    }
                } else {
                    $this->logger->debug("Création de contact désactivée ou userId/phoneNumber manquant", ['createContacts' => $createContacts, 'userId' => $userId, 'hasPhoneNumber' => ($phoneNumber !== null)]);
                }

                // Assign to groups if groupIds are specified
                $groupIds = $this->options['groupIds'] ?? [];
                if (!empty($groupIds) && $createContacts && $userId && $phoneNumber) {
                    $this->assignContactToGroups($number, $userId, $groupIds);
                }

                $this->stats['processed']++;
            } catch (\Exception $e) {
                $errorMessage = "Erreur lors du traitement du numéro {$item['number']}: " . $e->getMessage();
                $this->logger->error($errorMessage, ['number' => $item['number'], 'exception' => $e]);
                $this->errors[] = $errorMessage;

                // Add detailed error for processing exception
                if (count($this->detailedErrors) < $this->maxDetailedErrors) {
                    $this->detailedErrors[] = [
                        'line' => 'unknown', // Line number not available in batch context
                        'value' => $item['number'],
                        'message' => "Erreur de traitement: " . $e->getMessage()
                    ];
                }
            }
        }
        $this->logger->debug("Fin du traitement du batch", ['processedInBatch' => count($batch)]);
    }

    /**
     * Normalize a phone number
     * 
     * @param string $number Phone number to normalize
     * @return string|null Normalized phone number or null if invalid
     */
    private function normalizePhoneNumber(string $number): ?string
    {
        $originalNumber = $number; // Keep original for logging
        // Remove all non-numeric characters except the + sign
        $number = preg_replace('/[^0-9+]/', '', $number);

        // If the number is empty after cleaning, it's invalid
        if (empty($number)) {
            $this->logger->debug("Numéro vide après nettoyage", ['original' => $originalNumber]);
            return null;
        }

        // If the number starts with 00, replace with +
        if (strpos($number, '00') === 0) {
            $number = '+' . substr($number, 2);
            $this->logger->debug("Remplacement de '00' par '+'", ['original' => $originalNumber, 'result' => $number]);
        }

        // If the number doesn't start with +, add the default country code (225 for Côte d'Ivoire)
        if (strpos($number, '+') !== 0) {
            // Check if the number already has the country code without the +
            if (strpos($number, '225') === 0) {
                $number = '+' . $number;
                $this->logger->debug("Ajout de '+' au numéro commençant par 225", ['original' => $originalNumber, 'result' => $number]);
            } else {
                // Assume it's a local number needing the country code
                $number = '+225' . $number;
                $this->logger->debug("Ajout du code pays '+225'", ['original' => $originalNumber, 'result' => $number]);
            }
        }

        // Validate the number format (basic validation)
        if (!preg_match('/^\+[0-9]{6,15}$/', $number)) {
            $this->logger->warning("Format final du numéro invalide après normalisation", ['original' => $originalNumber, 'normalized' => $number]);
            return null;
        }

        $this->logger->debug("Normalisation réussie", ['original' => $originalNumber, 'normalized' => $number]);
        return $number;
    }

    /**
     * Assign a contact to multiple groups
     * 
     * @param string $phoneNumber The phone number of the contact
     * @param int $userId The user ID
     * @param array $groupIds Array of group IDs to assign the contact to
     * @return void
     */
    private function assignContactToGroups(string $phoneNumber, int $userId, array $groupIds): void
    {
        $this->logger->debug("Début de l'assignation aux groupes", ['phoneNumber' => $phoneNumber, 'userId' => $userId, 'groupIds' => $groupIds]);
        
        try {
            // Find the contact by phone number and user ID
            $contacts = $this->contactRepository->findByCriteria(
                ['phoneNumber' => $phoneNumber, 'userId' => $userId],
                1
            );
            
            if (empty($contacts)) {
                $this->logger->warning("Contact non trouvé pour l'assignation aux groupes", ['phoneNumber' => $phoneNumber, 'userId' => $userId]);
                $this->stats['groupAssignmentErrors']++;
                
                if (count($this->detailedErrors) < $this->maxDetailedErrors) {
                    $this->detailedErrors[] = [
                        'line' => 'unknown',
                        'value' => $phoneNumber,
                        'message' => "Contact non trouvé pour l'assignation aux groupes"
                    ];
                }
                return;
            }
            
            $contact = $contacts[0];
            $contactId = $contact->getId();
            
            // Assign to each group
            foreach ($groupIds as $groupId) {
                try {
                    $this->logger->debug("Assignation du contact au groupe", ['contactId' => $contactId, 'groupId' => $groupId]);
                    
                    // Check if the membership already exists
                    $existingMembership = $this->membershipRepository->findByContactIdAndGroupId($contactId, $groupId);
                    
                    if ($existingMembership === null) {
                        // Add the contact to the group
                        $success = $this->membershipRepository->addContactToGroup($contactId, $groupId);
                        
                        if ($success) {
                            $this->stats['groupAssignments']++;
                            $this->logger->info("Contact assigné au groupe avec succès", ['contactId' => $contactId, 'groupId' => $groupId]);
                        } else {
                            $this->stats['groupAssignmentErrors']++;
                            $this->logger->warning("Échec de l'assignation au groupe", ['contactId' => $contactId, 'groupId' => $groupId]);
                            
                            if (count($this->detailedErrors) < $this->maxDetailedErrors) {
                                $this->detailedErrors[] = [
                                    'line' => 'unknown',
                                    'value' => $phoneNumber,
                                    'message' => "Échec de l'assignation au groupe ID: {$groupId}"
                                ];
                            }
                        }
                    } else {
                        $this->logger->debug("Contact déjà assigné à ce groupe", ['contactId' => $contactId, 'groupId' => $groupId]);
                        // Consider this as successful (idempotent operation)
                        $this->stats['groupAssignments']++;
                    }
                } catch (\Exception $e) {
                    $this->stats['groupAssignmentErrors']++;
                    $errorMessage = "Erreur lors de l'assignation au groupe {$groupId}: " . $e->getMessage();
                    $this->logger->error($errorMessage, ['contactId' => $contactId, 'groupId' => $groupId, 'exception' => $e]);
                    
                    if (count($this->detailedErrors) < $this->maxDetailedErrors) {
                        $this->detailedErrors[] = [
                            'line' => 'unknown',
                            'value' => $phoneNumber,
                            'message' => $errorMessage
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            $this->stats['groupAssignmentErrors']++;
            $errorMessage = "Erreur générale lors de l'assignation aux groupes pour {$phoneNumber}: " . $e->getMessage();
            $this->logger->error($errorMessage, ['phoneNumber' => $phoneNumber, 'userId' => $userId, 'exception' => $e]);
            
            if (count($this->detailedErrors) < $this->maxDetailedErrors) {
                $this->detailedErrors[] = [
                    'line' => 'unknown',
                    'value' => $phoneNumber,
                    'message' => $errorMessage
                ];
            }
        }
        
        $this->logger->debug("Fin de l'assignation aux groupes", ['phoneNumber' => $phoneNumber]);
    }

    /**
     * Reset statistics and errors
     * 
     * @return void
     */
    private function resetStats(): void
    {
        $this->stats = [
            'total' => 0,
            'valid' => 0,
            'invalid' => 0,
            'duplicates' => 0,
            'processed' => 0,
            'contactsCreated' => 0,
            'contactsDuplicates' => 0,
            'groupAssignments' => 0,
            'groupAssignmentErrors' => 0
        ];
        $this->errors = [];
        $this->detailedErrors = [];
        $this->currentLine = 0;
    }

    /**
     * Get import results
     * 
     * @return array Import results
     */
    private function getResults(): array
    {
        return [
            'status' => empty($this->errors) ? 'success' : 'error',
            'stats' => $this->stats,
            'errors' => $this->errors,
            'detailedErrors' => $this->detailedErrors
        ];
    }
}
