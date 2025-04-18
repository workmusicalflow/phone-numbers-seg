<?php

namespace App\Services;

use App\Repositories\Interfaces\CustomSegmentRepositoryInterface;
use App\Repositories\Interfaces\PhoneNumberRepositoryInterface;
use App\Repositories\Interfaces\SMSHistoryRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\ContactRepositoryInterface; // Import ContactRepository interface
use App\Models\SMSHistory;
use App\Models\Contact; // Import Contact model
use App\Services\Interfaces\OrangeAPIClientInterface;
use Exception; // Use base Exception
use RuntimeException; // Use RuntimeException for specific errors

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

    /**
     * Constructor
     * 
     * @param OrangeAPIClientInterface $orangeApiClient
     * @param PhoneNumberRepositoryInterface|null $phoneNumberRepository
     * @param CustomSegmentRepositoryInterface|null $customSegmentRepository
     * @param SMSHistoryRepositoryInterface|null $smsHistoryRepository
     * @param UserRepositoryInterface|null $userRepository
     * @param ContactRepositoryInterface|null $contactRepository // Add ContactRepository parameter
     */
    public function __construct(
        OrangeAPIClientInterface $orangeApiClient,
        ?PhoneNumberRepositoryInterface $phoneNumberRepository = null,
        ?CustomSegmentRepositoryInterface $customSegmentRepository = null,
        ?SMSHistoryRepositoryInterface $smsHistoryRepository = null,
        ?UserRepositoryInterface $userRepository = null,
        ?ContactRepositoryInterface $contactRepository = null // Inject ContactRepository
    ) {
        $this->orangeApiClient = $orangeApiClient;
        $this->phoneNumberRepository = $phoneNumberRepository;
        $this->customSegmentRepository = $customSegmentRepository;
        $this->smsHistoryRepository = $smsHistoryRepository;
        $this->userRepository = $userRepository;
        $this->contactRepository = $contactRepository; // Store ContactRepository
    }

    // getAccessToken method removed as it's handled by OrangeAPIClient

    /**
     * Send an SMS to a single phone number
     * 
     * @param string $receiverNumber Receiver phone number
     * @param string $message SMS message
     * @param int|null $userId ID of the user sending the SMS
     * @return array API response from OrangeAPIClient
     * @throws RuntimeException If the SMS cannot be sent or user checks fail
     */
    public function sendSMS(string $receiverNumber, string $message, ?int $userId = null): array
    {
        // Vérifier les crédits de l'utilisateur si un userId est fourni
        $user = null;
        if ($userId !== null && $this->userRepository !== null) {
            $user = $this->userRepository->findById($userId);
            if ($user === null) {
                throw new RuntimeException("Utilisateur non trouvé");
            }
            if ($user->getSmsCredit() <= 0) {
                throw new RuntimeException("Crédits SMS insuffisants");
            }
            // TODO: Implement SMS limit check if needed
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
            // Use the injected Orange API client to send the SMS
            $responseData = $this->orangeApiClient->sendSMS($normalizedReceiverNumber, $message);
            $isSuccess = true; // Assume success if no exception is thrown by the client

            // Extract message ID if available in the response
            if (isset($responseData['outboundSMSMessageRequest']['resourceURL'])) {
                $resourceUrl = $responseData['outboundSMSMessageRequest']['resourceURL'];
                $messageId = substr($resourceUrl, strrpos($resourceUrl, '/') + 1);
            }
        } catch (RuntimeException $e) {
            $isSuccess = false;
            $errorMessage = $e->getMessage();
            // Re-throw the exception after logging attempt
            // throw $e; // Decide whether to re-throw or just log and return failure indicator

        } finally {
            // Always attempt to log, regardless of success or failure
            if ($this->smsHistoryRepository !== null) {
                $phoneNumberId = null;
                if ($this->phoneNumberRepository !== null) {
                    $phoneNumber = $this->phoneNumberRepository->findByNumber($originalNumber);
                    if ($phoneNumber !== null) {
                        $phoneNumberId = $phoneNumber->getId();
                    }
                }

                $smsHistory = new SMSHistory(
                    null,
                    $originalNumber,
                    $message,
                    $isSuccess ? 'SENT' : 'FAILED',
                    $senderAddress, // Use value obtained from client
                    $senderName,    // Use value obtained from client
                    $phoneNumberId,
                    $messageId,
                    $errorMessage, // Log error message if sending failed
                    null, // segmentId - handled in sendSMSToSegment
                    $userId
                );
                try {
                    $this->smsHistoryRepository->save($smsHistory);
                } catch (Exception $logException) {
                    // Log the logging error itself, but don't let it stop the process
                    error_log("Failed to save SMS history: " . $logException->getMessage());
                }
            }
        }

        // If sending failed, throw the original exception now after logging
        if (!$isSuccess && $errorMessage !== null) {
            throw new RuntimeException($errorMessage);
        }

        // Décompter les crédits si l'envoi a réussi (API call succeeded) et qu'un utilisateur est spécifié
        if ($isSuccess && $userId !== null && $this->userRepository !== null && $user !== null) {
            try {
                $newCreditBalance = $user->getSmsCredit() - 1;
                $user->setSmsCredit($newCreditBalance);
                $this->userRepository->save($user);
            } catch (Exception $creditException) {
                // Log error deducting credits, but don't fail the whole operation
                error_log("Failed to deduct credits for user {$userId}: " . $creditException->getMessage());
            }
        }

        // Return the response data from the API client on success
        return $responseData;
    }

    /**
     * Send an SMS to multiple phone numbers
     * 
     * @param array $receiverNumbers Array of receiver phone numbers
     * @param string $message SMS message
     * @param int|null $userId ID of the user sending the SMS
     * @return array Results for each number ['phoneNumber' => ['status' => 'success|error', 'message' => '...', 'response' => mixed]]
     */
    public function sendBulkSMS(array $receiverNumbers, string $message, ?int $userId = null): array
    {
        // User and credit check (simplified - assumes enough credits if check passes once)
        if ($userId !== null && $this->userRepository !== null) {
            $user = $this->userRepository->findById($userId);
            if ($user === null) {
                throw new RuntimeException("Utilisateur non trouvé");
            }
            if ($user->getSmsCredit() < count($receiverNumbers)) {
                throw new RuntimeException("Crédits SMS insuffisants pour l'envoi en masse");
            }
            // TODO: Implement SMS limit check if needed
        }

        $results = [];
        foreach ($receiverNumbers as $number) {
            $originalNumber = $number; // Keep original for key
            try {
                // Call the refactored sendSMS method
                $response = $this->sendSMS($number, $message, $userId);
                $results[$originalNumber] = [
                    'status' => 'success',
                    'message' => 'Envoyé (Vérifier statut API)', // More accurate status might be in response
                    'response' => $response // Include API response if needed
                ];
            } catch (Exception $e) {
                // sendSMS already attempts logging, just record the failure here
                $results[$originalNumber] = [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }
        return $results;
    }

    /**
     * Send an SMS to all phone numbers in a segment
     * 
     * @param int $segmentId Segment ID
     * @param string $message SMS message
     * @param int|null $userId ID of the user sending the SMS
     * @return array Results for each number
     * @throws RuntimeException If the repositories are not provided or segment not found
     */
    public function sendSMSToSegment(int $segmentId, string $message, ?int $userId = null): array
    {
        if ($this->phoneNumberRepository === null || $this->customSegmentRepository === null) {
            throw new RuntimeException('Phone number and custom segment repositories are required for sending to a segment');
        }

        $segment = $this->customSegmentRepository->findById($segmentId);
        if ($segment === null) {
            throw new RuntimeException('Segment not found: ' . $segmentId);
        }

        $phoneNumbers = $this->phoneNumberRepository->findByCustomSegment($segmentId);
        $numbers = array_map(fn($pn) => $pn->getNumber(), $phoneNumbers);

        if (empty($numbers)) {
            return []; // No numbers in segment
        }

        // User and credit check (before calling sendBulkSMS)
        if ($userId !== null && $this->userRepository !== null) {
            $user = $this->userRepository->findById($userId);
            if ($user === null) {
                throw new RuntimeException("Utilisateur non trouvé");
            }
            if ($user->getSmsCredit() < count($numbers)) {
                throw new RuntimeException("Crédits SMS insuffisants pour l'envoi au segment");
            }
            // TODO: Implement SMS limit check if needed
        }

        // Send the SMS to all numbers using sendBulkSMS
        $results = $this->sendBulkSMS($numbers, $message, $userId);

        // Update segment ID in SMS history records AFTER sending attempts
        if ($this->smsHistoryRepository !== null) {
            // This is inefficient (N+1 updates). A batch update or different logging strategy is better.
            foreach ($numbers as $number) {
                $originalNumber = preg_replace('/^tel:/', '', $this->normalizePhoneNumber($number));
                // Find the most recent history entry for this number/message combo? Risky.
                // Better: SMSService::sendSMS should return the history ID it created.
                // For now, this update logic is flawed and removed. History should be logged correctly in sendSMS.
                /*
                 $history = $this->smsHistoryRepository->findByPhoneNumber($originalNumber, 1); // Find latest
                 if (!empty($history)) {
                     $latestHistory = $history[0];
                     // Check if this history record likely corresponds to *this* send operation
                     // This is difficult without more context (e.g., batch ID)
                     if ($latestHistory->getMessage() === $message) { // Basic check
                         $latestHistory->setSegmentId($segmentId);
                         try {
                             $this->smsHistoryRepository->save($latestHistory);
                         } catch (Exception $logException) {
                             error_log("Failed to update segment ID for history: " . $logException->getMessage());
                         }
                     }
                 }
                 */
            }
        }

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
     * @return array Results structured like BulkSMSResult: ['status', 'message', 'summary', 'results']
     * @throws RuntimeException If required repositories are missing, user not found, or insufficient credits.
     */
    public function sendToAllContacts(int $userId, string $message): array
    {
        if ($this->contactRepository === null || $this->userRepository === null) {
            throw new RuntimeException('Contact and User repositories are required for sending to all contacts.');
        }

        // 1. Get User (for credit check)
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new RuntimeException("Utilisateur #{$userId} non trouvé.");
        }

        // 2. Get all contacts for the user
        // Note: findByUserId might need adjustment if it doesn't fetch all contacts by default
        // Assuming it fetches all if limit/offset are not provided or handled internally.
        // Let's fetch all contacts without pagination for this feature.
        $contacts = $this->contactRepository->findByUserId($userId, -1); // Use -1 or a large number to signify 'all' if needed

        if (empty($contacts)) {
            return [
                'status' => 'COMPLETED', // Or 'NO_CONTACTS'?
                'message' => 'Aucun contact trouvé pour cet utilisateur.',
                'summary' => ['total' => 0, 'successful' => 0, 'failed' => 0],
                'results' => []
            ];
        }

        // 3. Extract unique, valid phone numbers
        $phoneNumbers = [];
        foreach ($contacts as $contact) {
            $number = $contact->getPhoneNumber();
            if (!empty($number)) {
                // Basic validation could be added here if needed
                $phoneNumbers[$number] = true; // Use keys for uniqueness
            }
        }
        $uniqueNumbers = array_keys($phoneNumbers);
        $totalContacts = count($uniqueNumbers);

        if ($totalContacts === 0) {
            return [
                'status' => 'COMPLETED', // Or 'NO_VALID_NUMBERS'?
                'message' => 'Aucun numéro de téléphone valide trouvé parmi les contacts.',
                'summary' => ['total' => 0, 'successful' => 0, 'failed' => 0],
                'results' => []
            ];
        }


        // 4. Check credits
        if ($user->getSmsCredit() < $totalContacts) {
            throw new RuntimeException("Crédits SMS insuffisants ({$user->getSmsCredit()} disponibles, {$totalContacts} requis).");
        }
        // TODO: Implement SMS limit check if needed

        // 5. Call sendBulkSMS
        // The sendBulkSMS method already handles individual sending, logging, and credit deduction per SMS.
        $bulkResults = $this->sendBulkSMS($uniqueNumbers, $message, $userId);

        // 6. Format the final result based on sendBulkSMS output
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

        return [
            'status' => ($failed === 0) ? 'COMPLETED' : (($successful > 0) ? 'PARTIAL' : 'FAILED'),
            'message' => 'Envoi à tous les contacts terminé.',
            'summary' => ['total' => $totalContacts, 'successful' => $successful, 'failed' => $failed],
            'results' => $formattedResults
        ];
    }
}
