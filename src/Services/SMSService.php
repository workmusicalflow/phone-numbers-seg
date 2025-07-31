<?php

namespace App\Services;

use App\Repositories\Interfaces\CustomSegmentRepositoryInterface;
use App\Repositories\Interfaces\PhoneNumberRepositoryInterface;
use App\Repositories\Interfaces\SMSHistoryRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\ContactRepositoryInterface; // Import ContactRepository interface
use App\Entities\SMSHistory;
use App\Entities\Contact; // Import Contact entity
use App\Services\Interfaces\OrangeAPIClientInterface;
use App\Services\Interfaces\SMSQueueServiceInterface;
use Exception; // Use base Exception
use RuntimeException; // Use RuntimeException for specific errors
use Psr\Log\LoggerInterface; // Import LoggerInterface

/**
 * SMSService
 * 
 * Service for sending SMS messages using the Orange API client.
 */
class SMSService
{
    private OrangeAPIClientInterface $orangeApiClient; // Inject the client
    private ?PhoneNumberRepositoryInterface $phoneNumberRepository;
    private ?CustomSegmentRepositoryInterface $customSegmentRepository;
    private ?SMSHistoryRepositoryInterface $smsHistoryRepository;
    private ?UserRepositoryInterface $userRepository;
    private ?ContactRepositoryInterface $contactRepository; // Add ContactRepository property
    private ?SMSQueueServiceInterface $smsQueueService; // Add SMSQueueService
    private LoggerInterface $logger; // Add Logger property

    /**
     * Constructor
     * 
     * @param OrangeAPIClientInterface $orangeApiClient
     * @param LoggerInterface $logger
     * @param PhoneNumberRepositoryInterface|null $phoneNumberRepository
     * @param CustomSegmentRepositoryInterface|null $customSegmentRepository
     * @param SMSHistoryRepositoryInterface|null $smsHistoryRepository
     * @param UserRepositoryInterface|null $userRepository
     * @param ContactRepositoryInterface|null $contactRepository
     * @param SMSQueueServiceInterface|null $smsQueueService
     */
    public function __construct(
        OrangeAPIClientInterface $orangeApiClient,
        LoggerInterface $logger,
        ?PhoneNumberRepositoryInterface $phoneNumberRepository = null,
        ?CustomSegmentRepositoryInterface $customSegmentRepository = null,
        ?SMSHistoryRepositoryInterface $smsHistoryRepository = null,
        ?UserRepositoryInterface $userRepository = null,
        ?ContactRepositoryInterface $contactRepository = null,
        ?SMSQueueServiceInterface $smsQueueService = null
    ) {
        $this->orangeApiClient = $orangeApiClient;
        $this->logger = $logger;
        $this->phoneNumberRepository = $phoneNumberRepository;
        $this->customSegmentRepository = $customSegmentRepository;
        $this->smsHistoryRepository = $smsHistoryRepository;
        $this->userRepository = $userRepository;
        $this->contactRepository = $contactRepository;
        $this->smsQueueService = $smsQueueService;
    }

    // getAccessToken method removed as it's handled by OrangeAPIClient

    /**
     * Send an SMS to a single phone number
     * 
     * @param string $receiverNumber Receiver phone number
     * @param string $message SMS message
     * @param int|null $userId ID of the user sending the SMS
     * @param int|null $segmentId Optional Segment ID to associate with the history
     * @return array API response from OrangeAPIClient
     * @throws RuntimeException If the SMS cannot be sent or user checks fail
     */
    public function sendSMS(string $receiverNumber, string $message, ?int $userId = null, ?int $segmentId = null): array
    {
        $this->logger->info("Tentative d'envoi SMS", [
            'receiver' => $receiverNumber,
            'userId' => $userId,
            'segmentId' => $segmentId
        ]);

        // Vérifier les crédits de l'utilisateur si un userId est fourni
        $user = null;
        if ($userId !== null && $this->userRepository !== null) {
            $this->logger->debug("Vérification des crédits pour l'utilisateur #{$userId}");
            $user = $this->userRepository->findById($userId);
            if ($user === null) {
                $this->logger->error("Utilisateur #{$userId} non trouvé pour la vérification des crédits SMS.");
                throw new RuntimeException("Utilisateur #{$userId} non trouvé pour la vérification des crédits SMS.");
            }

            // Check SMS Limit if set
            $limit = $user->getSmsLimit();
            if ($limit !== null && $limit <= 0) { // Check if limit exists and is restrictive (e.g., 0)
                $this->logger->warning("Échec de l'envoi SMS pour l'utilisateur #{$userId} : Limite d'envoi SMS atteinte ou nulle.", [
                    'userId' => $userId,
                    'limit' => $limit,
                    'receiver' => $receiverNumber
                ]);
                throw new RuntimeException("Limite d'envoi SMS atteinte ou nulle (Limite: {$limit}).");
            }

            // Check if the user has at least 1 credit to send this SMS
            if ($user->getSmsCredit() < 1) {
                $this->logger->warning("Échec de l'envoi SMS pour l'utilisateur #{$userId} : Crédits SMS insuffisants", [
                    'userId' => $userId,
                    'credits' => $user->getSmsCredit(),
                    'receiver' => $receiverNumber
                ]);
                // Log this specific failure reason using standard logger
                throw new RuntimeException("Crédits SMS insuffisants pour envoyer ce message. Solde actuel: {$user->getSmsCredit()}.");
            }
            $this->logger->debug("Vérification des crédits réussie pour l'utilisateur #{$userId}", ['credits' => $user->getSmsCredit()]);
        } else {
            $this->logger->debug("Aucun userId fourni, pas de vérification de crédits.");
        }

        $normalizedReceiverNumber = $this->normalizePhoneNumber($receiverNumber);
        $originalNumber = preg_replace('/^tel:/', '', $normalizedReceiverNumber);
        $senderAddress = $this->orangeApiClient->getSenderAddress(); // Get from injected client
        $senderName = $this->orangeApiClient->getSenderName();     // Get from injected client
        $responseData = [];
        $isSuccess = false;
        $errorMessage = null;
        $messageId = null;

        try {
            $this->logger->info("Appel de l'API Orange pour envoyer SMS", [
                'receiver' => $normalizedReceiverNumber,
                'senderAddress' => $senderAddress,
                'senderName' => $senderName
            ]);
            // Use the injected Orange API client to send the SMS
            $responseData = $this->orangeApiClient->sendSMS($normalizedReceiverNumber, $message);
            $isSuccess = true; // Assume success if no exception is thrown by the client
            $this->logger->info("Réponse de l'API Orange reçue (Succès)", ['response' => $responseData]);

            // Extract message ID if available in the response
            if (isset($responseData['outboundSMSMessageRequest']['resourceURL'])) {
                $resourceUrl = $responseData['outboundSMSMessageRequest']['resourceURL'];
                $messageId = substr($resourceUrl, strrpos($resourceUrl, '/') + 1);
                $this->logger->debug("Message ID extrait de la réponse API", ['messageId' => $messageId]);
            } else {
                $this->logger->warning("Impossible d'extraire le message ID de la réponse API", ['response' => $responseData]);
            }
        } catch (RuntimeException $e) {
            $isSuccess = false;
            $errorMessage = $e->getMessage();
            $this->logger->error("Échec de l'appel API Orange", [
                'receiver' => $normalizedReceiverNumber,
                'error' => $errorMessage
            ]);
            // Re-throw the exception after logging attempt
            // throw $e; // Decide whether to re-throw or just log and return failure indicator

        } finally {
            // Always attempt to log, regardless of success or failure
            if ($this->smsHistoryRepository !== null) {
                $this->logger->debug("Tentative d'enregistrement de l'historique SMS", [
                    'receiver' => $originalNumber,
                    'status' => $isSuccess ? 'SENT' : 'FAILED',
                    'userId' => $userId,
                    'segmentId' => $segmentId
                ]);
                $phoneNumberId = null;
                if ($this->phoneNumberRepository !== null) {
                    $phoneNumber = $this->phoneNumberRepository->findByNumber($originalNumber);
                    if ($phoneNumber !== null) {
                        $phoneNumberId = $phoneNumber->getId();
                    }
                }

                $smsHistory = new SMSHistory();
                $smsHistory->setPhoneNumber($originalNumber);
                $smsHistory->setMessage($message);
                $smsHistory->setStatus($isSuccess ? 'SENT' : 'FAILED');
                $smsHistory->setSenderAddress($senderAddress);
                $smsHistory->setSenderName($senderName);
                $smsHistory->setPhoneNumberId($phoneNumberId);
                $smsHistory->setMessageId($messageId);
                $smsHistory->setErrorMessage($errorMessage);
                $smsHistory->setSegmentId($segmentId); // Set segmentId if provided
                $smsHistory->setUserId($userId);
                $smsHistory->setCreatedAt(new \DateTime());
                try {
                    $this->smsHistoryRepository->save($smsHistory);
                    $this->logger->info("Historique SMS enregistré avec succès", ['historyId' => $smsHistory->getId()]);
                } catch (Exception $logException) {
                    // Log the logging error itself, but don't let it stop the process
                    // error_log("Failed to save SMS history: " . $logException->getMessage()); // Replaced by logger
                    $this->logger->error("Échec de l'enregistrement de l'historique SMS", [
                        'error' => $logException->getMessage(),
                        'receiver' => $originalNumber,
                        'userId' => $userId
                    ]);
                }
            } else {
                $this->logger->warning("SMSHistoryRepository non disponible, impossible d'enregistrer l'historique.");
            }
        }

        // If sending failed, throw the original exception now after logging attempt
        if (!$isSuccess && $errorMessage !== null) {
            $this->logger->info("Levée d'exception après échec de l'envoi SMS", ['receiver' => $receiverNumber, 'userId' => $userId]);
            throw new RuntimeException($errorMessage);
        }

        // Décompter les crédits si l'envoi a réussi (API call succeeded) et qu'un utilisateur est spécifié
        if ($isSuccess && $userId !== null && $this->userRepository !== null && $user !== null) {
            $this->logger->debug("Tentative de déduction de crédit pour l'utilisateur #{$userId}", ['currentCredits' => $user->getSmsCredit()]);
            try {
                $newCreditBalance = $user->getSmsCredit() - 1;
                $user->setSmsCredit($newCreditBalance);
                // Si le crédit atteint 0, mettre aussi la limite à 0
                if ($newCreditBalance === 0) {
                    $this->logger->info("Le crédit de l'utilisateur #{$userId} a atteint 0, mise à jour de smsLimit à 0.");
                    $user->setSmsLimit(0);
                }
                $this->userRepository->save($user);
                $this->logger->info("Crédit déduit avec succès pour l'utilisateur #{$userId}", ['newBalance' => $newCreditBalance]);
            } catch (Exception $creditException) {
                // Log error deducting credits, but don't fail the whole operation as the SMS was sent.
                // Critical log as it affects billing/usage tracking.
                // error_log("CRITICAL: Échec de la déduction de crédit pour l'utilisateur #{$userId} après envoi SMS réussi. Erreur: " . $creditException->getMessage()); // Replaced by logger
                $this->logger->critical("Échec de la déduction de crédit pour l'utilisateur #{$userId} après envoi SMS réussi.", [
                    'userId' => $userId,
                    'error' => $creditException->getMessage()
                ]);
                // Optionally, trigger an alert or add to a reconciliation queue.
            }
        } else {
            $this->logger->debug("Pas de déduction de crédit nécessaire (échec envoi, pas d'userId, ou userRepository non disponible).");
        }

        // Return the response data from the API client on success
        $this->logger->info("Envoi SMS terminé avec succès", ['receiver' => $receiverNumber, 'userId' => $userId]);
        return $responseData;
    }

    /**
     * Send an SMS to multiple phone numbers
     * 
     * @param array $receiverNumbers Array of receiver phone numbers
     * @param string $message SMS message
     * @param int|null $userId ID of the user sending the SMS
     * @param int|null $segmentId Optional Segment ID to associate with the history
     * @param bool $useQueue Whether to use the queue (true) or send directly (false)
     * @return array Results for each number ['phoneNumber' => ['status' => 'success|error', 'message' => '...', 'response' => mixed]]
     */
    public function sendBulkSMS(array $receiverNumbers, string $message, ?int $userId = null, ?int $segmentId = null, bool $useQueue = true): array
    {
        $receiverNumbers = array_unique($receiverNumbers);
        $count = count($receiverNumbers);

        $this->logger->info("Début de l'envoi en masse", [
            'count' => $count,
            'userId' => $userId,
            'segmentId' => $segmentId,
            'useQueue' => $useQueue
        ]);

        // User and credit check
        $user = null;
        if ($userId !== null && $this->userRepository !== null) {
            $this->logger->debug("Vérification des crédits pour l'envoi en masse (Utilisateur #{$userId})");
            $user = $this->userRepository->findById($userId);
            if ($user === null) {
                $this->logger->error("Utilisateur #{$userId} non trouvé pour l'envoi en masse.");
                throw new RuntimeException("Utilisateur non trouvé");
            }
            $requiredCredits = $count;
            if ($user->getSmsCredit() < $requiredCredits) {
                $this->logger->warning("Crédits SMS insuffisants pour l'envoi en masse", [
                    'userId' => $userId,
                    'required' => $requiredCredits,
                    'available' => $user->getSmsCredit()
                ]);
                throw new RuntimeException("Crédits SMS insuffisants pour l'envoi en masse ({$user->getSmsCredit()} disponibles, {$requiredCredits} requis).");
            }
            $this->logger->debug("Vérification des crédits pour l'envoi en masse réussie", ['userId' => $userId, 'credits' => $user->getSmsCredit()]);
        } else {
            $this->logger->debug("Aucun userId fourni, pas de vérification de crédits pour l'envoi en masse.");
        }

        // If we should use the queue and SMSQueueService is available
        if ($useQueue && isset($this->smsQueueService)) {
            $this->logger->info("Utilisation du service de file d'attente pour l'envoi en masse", [
                'count' => $count
            ]);

            try {
                // Enqueue all messages
                $batchId = $this->smsQueueService->enqueueBulk(
                    $receiverNumbers,
                    $message,
                    $userId,
                    $segmentId
                );

                $this->logger->info("SMS en masse mis en file d'attente avec succès", [
                    'batchId' => $batchId,
                    'count' => $count
                ]);

                // Return success status for all numbers
                $results = [];
                foreach ($receiverNumbers as $number) {
                    $results[$number] = [
                        'status' => 'queued',
                        'message' => 'Mis en file d\'attente pour envoi',
                        'batchId' => $batchId
                    ];
                }

                return $results;
            } catch (Exception $e) {
                $this->logger->error("Échec de la mise en file d'attente pour l'envoi en masse", [
                    'error' => $e->getMessage()
                ]);
                // Fall back to direct sending if queue fails
                $this->logger->warning("Passage à l'envoi direct suite à l'échec de la mise en file d'attente");
            }
        }

        // Direct sending if queue is not available or failed
        $results = [];
        $historyEntities = []; // Array to collect history entities
        $successCount = 0;
        $failureCount = 0;

        foreach ($receiverNumbers as $number) {
            $originalNumber = $number; // Keep original for key
            try {
                // Call sendSMS directly
                $sendResult = $this->sendSMS($number, $message, $userId, $segmentId);
                $results[$originalNumber] = [
                    'status' => 'success',
                    'message' => 'Envoyé (Vérifier statut API)',
                    'response' => $sendResult['response'] // API response
                ];
                if ($sendResult['history'] instanceof SMSHistory) {
                    $historyEntities[] = $sendResult['history']; // Collect history entity
                }
                $successCount++;
            } catch (Exception $e) {
                // Handle exception and create failed history entry
                $failedHistory = null;
                if ($this->smsHistoryRepository !== null) {
                    $failedHistory = new SMSHistory();
                    $failedHistory->setPhoneNumber($originalNumber);
                    $failedHistory->setMessage($message);
                    $failedHistory->setStatus('FAILED');
                    $failedHistory->setSenderAddress($this->orangeApiClient->getSenderAddress());
                    $failedHistory->setSenderName($this->orangeApiClient->getSenderName());
                    $failedHistory->setErrorMessage($e->getMessage());
                    $failedHistory->setSegmentId($segmentId);
                    $failedHistory->setUserId($userId);
                    $failedHistory->setCreatedAt(new \DateTime());
                    $historyEntities[] = $failedHistory; // Collect failed history
                }
                $results[$originalNumber] = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'history' => $failedHistory // Optionally include failed history ref
                ];
                $failureCount++;
            }
        }

        // Save all collected history entities in bulk for direct sending
        if (!empty($historyEntities) && $this->smsHistoryRepository !== null) {
            try {
                $this->logger->info("Enregistrement en masse de l'historique SMS", ['count' => count($historyEntities)]);
                $this->smsHistoryRepository->saveBulk($historyEntities);
                $this->logger->info("Historique SMS enregistré en masse avec succès.");
            } catch (Exception $e) {
                $this->logger->error("Échec de l'enregistrement en masse de l'historique SMS", ['error' => $e->getMessage()]);
            }
        }

        $this->logger->info("Envoi en masse terminé", [
            'totalAttempted' => $count,
            'successful' => $successCount,
            'failed' => $failureCount,
            'userId' => $userId,
            'segmentId' => $segmentId
        ]);

        return $results;
    }

    /**
     * Send an SMS to all phone numbers in a segment
     * 
     * @param int $segmentId Segment ID
     * @param string $message SMS message
     * @param int|null $userId ID of the user sending the SMS
     * @param bool $useQueue Whether to use the queue (true) or send directly (false)
     * @return array Results for each number
     * @throws RuntimeException If the repositories are not provided or segment not found
     */
    public function sendSMSToSegment(int $segmentId, string $message, ?int $userId = null, bool $useQueue = true): array
    {
        $this->logger->info("Début de l'envoi au segment", [
            'segmentId' => $segmentId,
            'userId' => $userId,
            'useQueue' => $useQueue
        ]);

        if ($this->phoneNumberRepository === null || $this->customSegmentRepository === null) {
            $this->logger->error("Repositories manquants pour l'envoi au segment.");
            throw new RuntimeException('Phone number and custom segment repositories are required for sending to a segment');
        }

        $segment = $this->customSegmentRepository->findById($segmentId);
        if ($segment === null) {
            $this->logger->error("Segment non trouvé", ['segmentId' => $segmentId]);
            throw new RuntimeException('Segment not found: ' . $segmentId);
        }
        $this->logger->debug("Segment trouvé", ['segmentId' => $segmentId, 'segmentName' => $segment->getName()]);

        $phoneNumbers = $this->phoneNumberRepository->findByCustomSegment($segmentId);
        $numbers = array_map(fn($pn) => $pn->getNumber(), $phoneNumbers);
        $this->logger->debug("Numéros trouvés dans le segment", ['segmentId' => $segmentId, 'count' => count($numbers)]);

        if (empty($numbers)) {
            $this->logger->info("Aucun numéro trouvé dans le segment, envoi annulé.", ['segmentId' => $segmentId]);
            return []; // No numbers in segment
        }

        // If we should use the queue and SMSQueueService is available
        if ($useQueue && isset($this->smsQueueService)) {
            $this->logger->info("Utilisation du service de file d'attente pour l'envoi au segment", [
                'segmentId' => $segmentId,
                'count' => count($numbers)
            ]);

            try {
                // Check credits before enqueuing
                if ($userId !== null && $this->userRepository !== null) {
                    $user = $this->userRepository->findById($userId);
                    if ($user === null) {
                        throw new RuntimeException("Utilisateur non trouvé");
                    }

                    $requiredCredits = count($numbers);
                    if ($user->getSmsCredit() < $requiredCredits) {
                        throw new RuntimeException("Crédits SMS insuffisants ({$user->getSmsCredit()} disponibles, {$requiredCredits} requis)");
                    }
                }

                // Use optimized enqueueSegment method
                $batchId = $this->smsQueueService->enqueueSegment(
                    $segmentId,
                    $message,
                    $userId
                );

                $this->logger->info("SMS du segment mis en file d'attente avec succès", [
                    'batchId' => $batchId,
                    'segmentId' => $segmentId,
                    'count' => count($numbers)
                ]);

                // Return success status for all numbers
                $results = [];
                foreach ($numbers as $number) {
                    $results[$number] = [
                        'status' => 'queued',
                        'message' => 'Mis en file d\'attente pour envoi',
                        'batchId' => $batchId
                    ];
                }

                return $results;
            } catch (Exception $e) {
                $this->logger->error("Échec de la mise en file d'attente pour l'envoi au segment", [
                    'segmentId' => $segmentId,
                    'error' => $e->getMessage()
                ]);
                // Fall back to direct sending if queue fails
                $this->logger->warning("Passage à l'envoi direct suite à l'échec de la mise en file d'attente");
            }
        }

        // Direct sending if queue is not available or failed
        // User and credit check (before calling sendBulkSMS)
        $user = null;
        if ($userId !== null && $this->userRepository !== null) {
            $this->logger->debug("Vérification des crédits pour l'envoi au segment (Utilisateur #{$userId})");
            $user = $this->userRepository->findById($userId);
            if ($user === null) {
                $this->logger->error("Utilisateur #{$userId} non trouvé pour l'envoi au segment.");
                throw new RuntimeException("Utilisateur non trouvé");
            }
            $requiredCredits = count($numbers);
            if ($user->getSmsCredit() < $requiredCredits) {
                $this->logger->warning("Crédits SMS insuffisants pour l'envoi au segment", [
                    'userId' => $userId,
                    'segmentId' => $segmentId,
                    'required' => $requiredCredits,
                    'available' => $user->getSmsCredit()
                ]);
                throw new RuntimeException("Crédits SMS insuffisants pour l'envoi au segment ({$user->getSmsCredit()} disponibles, {$requiredCredits} requis).");
            }
            $this->logger->debug("Vérification des crédits pour l'envoi au segment réussie", ['userId' => $userId, 'credits' => $user->getSmsCredit()]);
        } else {
            $this->logger->debug("Aucun userId fourni, pas de vérification de crédits pour l'envoi au segment.");
        }

        // Send the SMS directly using sendBulkSMS
        $this->logger->info("Appel de sendBulkSMS pour le segment", ['segmentId' => $segmentId, 'count' => count($numbers), 'userId' => $userId]);
        $results = $this->sendBulkSMS($numbers, $message, $userId, $segmentId, false); // force direct sending

        $this->logger->info("Envoi au segment terminé", ['segmentId' => $segmentId, 'userId' => $userId]);
        return $results;
    }

    /**
     * Normalize a phone number to the format required by the Orange API
     * 
     * @param string $number Phone number
     * @return string Normalized phone number (e.g., tel:+225XXXXXXXX)
     */
    private function normalizePhoneNumber(string $number): string
    {
        $number = preg_replace('/[^0-9+]/', '', $number);
        if (strpos($number, '+') === 0) {
            // Already international format
        } elseif (strpos($number, '00') === 0) {
            // Starts with 00, assume country code follows
            $number = '+' . substr($number, 2);
        } elseif (strpos($number, '0') === 0 && strlen($number) > 5) { // Basic check for local number
            // Assume local Côte d'Ivoire number if starts with 0
            // Preserve the leading 0 when adding the country code
            $number = '+225' . $number;
        } else {
            // Cannot determine format, maybe add default country code or throw error?
            // For now, assume it might be missing '+'
            if (ctype_digit($number) && strlen($number) > 9) { // Basic check
                // Maybe add default country code? Risky.
                // Let's assume it should have had a '+' if international
            }
            // Return as is or potentially throw an error for unhandled format
        }

        // Ensure 'tel:' prefix
        if (strpos($number, 'tel:') !== 0) {
            return 'tel:' . $number;
        }
        return $number; // Already has prefix
    }

    /**
     * Send an SMS to all contacts of a specific user.
     *
     * @param int $userId The ID of the user whose contacts should receive the SMS.
     * @param string $message The SMS message content.
     * @param bool $useQueue Whether to use the queue (true) or send directly (false)
     * @return array Results structured like BulkSMSResult: ['status', 'message', 'summary', 'results']
     * @throws RuntimeException If required repositories are missing, user not found, or insufficient credits.
     */
    public function sendToAllContacts(int $userId, string $message, bool $useQueue = true): array
    {
        $this->logger->info("Début de l'envoi à tous les contacts", [
            'userId' => $userId,
            'useQueue' => $useQueue
        ]);

        if ($this->contactRepository === null || $this->userRepository === null) {
            $this->logger->error("Repositories manquants pour l'envoi à tous les contacts.");
            throw new RuntimeException('Contact and User repositories are required for sending to all contacts.');
        }

        // 1. Get User (for credit check)
        $this->logger->debug("Récupération de l'utilisateur #{$userId}");
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            $this->logger->error("Utilisateur #{$userId} non trouvé pour l'envoi à tous les contacts.");
            throw new RuntimeException("Utilisateur #{$userId} non trouvé.");
        }
        $this->logger->debug("Utilisateur trouvé", ['userId' => $userId, 'username' => $user->getUsername()]);

        // 2. Get all contacts for the user
        $this->logger->debug("Récupération des contacts pour l'utilisateur #{$userId}");
        $contacts = $this->contactRepository->findByUserId($userId, -1); // Use -1 or a large number to signify 'all'
        $this->logger->debug("Nombre de contacts bruts trouvés", ['userId' => $userId, 'count' => count($contacts)]);

        if (empty($contacts)) {
            $this->logger->info("Aucun contact trouvé pour l'utilisateur #{$userId}, envoi annulé.");
            return [
                'status' => 'COMPLETED',
                'message' => 'Aucun contact trouvé pour cet utilisateur.',
                'summary' => ['total' => 0, 'successful' => 0, 'failed' => 0],
                'results' => []
            ];
        }

        // 3. Extract unique, valid phone numbers
        $this->logger->debug("Extraction des numéros uniques et valides");
        $phoneNumbers = [];
        foreach ($contacts as $contact) {
            $number = $contact->getPhoneNumber();
            if (!empty($number)) {
                $phoneNumbers[$number] = true; // Use keys for uniqueness
            } else {
                $this->logger->debug("Contact ignoré (numéro vide)", ['contactId' => $contact->getId()]);
            }
        }
        $uniqueNumbers = array_keys($phoneNumbers);
        $totalContacts = count($uniqueNumbers);
        $this->logger->debug("Nombre de numéros uniques et valides trouvés", ['count' => $totalContacts]);

        if ($totalContacts === 0) {
            $this->logger->info("Aucun numéro de téléphone valide trouvé parmi les contacts pour l'utilisateur #{$userId}, envoi annulé.");
            return [
                'status' => 'COMPLETED',
                'message' => 'Aucun numéro de téléphone valide trouvé parmi les contacts.',
                'summary' => ['total' => 0, 'successful' => 0, 'failed' => 0],
                'results' => []
            ];
        }

        // 4. Check credits
        $this->logger->debug("Vérification des crédits pour l'envoi à tous les contacts (Utilisateur #{$userId})");
        $requiredCredits = $totalContacts;
        if ($user->getSmsCredit() < $requiredCredits) {
            $this->logger->warning("Crédits SMS insuffisants pour l'envoi à tous les contacts", [
                'userId' => $userId,
                'required' => $requiredCredits,
                'available' => $user->getSmsCredit()
            ]);
            throw new RuntimeException("Crédits SMS insuffisants ({$user->getSmsCredit()} disponibles, {$requiredCredits} requis).");
        }
        $this->logger->debug("Vérification des crédits pour l'envoi à tous les contacts réussie", ['userId' => $userId, 'credits' => $user->getSmsCredit()]);

        // If we should use the queue and SMSQueueService is available
        if ($useQueue && isset($this->smsQueueService)) {
            $this->logger->info("Utilisation du service de file d'attente pour l'envoi à tous les contacts", [
                'userId' => $userId,
                'count' => $totalContacts
            ]);

            try {
                // Use optimized enqueueAllContacts method
                $batchId = $this->smsQueueService->enqueueAllContacts(
                    $userId,
                    $message
                );

                $this->logger->info("SMS à tous les contacts mis en file d'attente avec succès", [
                    'batchId' => $batchId,
                    'userId' => $userId,
                    'count' => $totalContacts
                ]);

                // Return success status
                return [
                    'status' => 'QUEUED',
                    'message' => 'Envoi à tous les contacts mis en file d\'attente.',
                    'summary' => ['total' => $totalContacts, 'queued' => $totalContacts, 'failed' => 0],
                    'batchId' => $batchId,
                    'results' => array_map(function ($number) use ($batchId) {
                        return [
                            'phoneNumber' => $number,
                            'status' => 'QUEUED',
                            'message' => 'Mis en file d\'attente pour envoi'
                        ];
                    }, $uniqueNumbers)
                ];
            } catch (Exception $e) {
                $this->logger->error("Échec de la mise en file d'attente pour l'envoi à tous les contacts", [
                    'userId' => $userId,
                    'error' => $e->getMessage()
                ]);
                // Fall back to direct sending if queue fails
                $this->logger->warning("Passage à l'envoi direct suite à l'échec de la mise en file d'attente");
            }
        }

        // Direct sending if queue is not available or failed
        $this->logger->info("Appel de sendBulkSMS pour tous les contacts", ['count' => $totalContacts, 'userId' => $userId]);
        $bulkResults = $this->sendBulkSMS($uniqueNumbers, $message, $userId, null, false); // force direct sending

        // Format the final result
        $successful = 0;
        $failed = 0;
        $formattedResults = [];
        foreach ($bulkResults as $number => $result) {
            $isSuccess = ($result['status'] === 'success');
            if ($isSuccess) $successful++;
            else $failed++;
            $formattedResults[] = [
                'phoneNumber' => $number,
                'status' => $isSuccess ? 'SENT' : 'FAILED',
                'message' => $result['message'] ?? ($isSuccess ? 'Envoyé' : 'Échec')
            ];
        }

        $finalResult = [
            'status' => ($failed === 0) ? 'COMPLETED' : (($successful > 0) ? 'PARTIAL' : 'FAILED'),
            'message' => 'Envoi à tous les contacts terminé.',
            'summary' => ['total' => $totalContacts, 'successful' => $successful, 'failed' => $failed],
            'results' => $formattedResults
        ];

        $this->logger->info("Envoi à tous les contacts terminé", [
            'userId' => $userId,
            'totalAttempted' => $totalContacts,
            'successful' => $successful,
            'failed' => $failed
        ]);

        return $finalResult;
    }
}
