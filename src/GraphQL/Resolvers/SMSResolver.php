<?php

namespace App\GraphQL\Resolvers;

use App\Repositories\SMSHistoryRepository;
use App\Repositories\CustomSegmentRepository;
use App\Services\SMSService;
use App\Models\SMSHistory;
use App\Models\Segment;
use App\Services\Interfaces\AuthServiceInterface;
use App\GraphQL\Formatters\GraphQLFormatterInterface; // Import Formatter interface
use Exception;
use Psr\Log\LoggerInterface;

class SMSResolver
{
    private SMSHistoryRepository $smsHistoryRepository;
    private CustomSegmentRepository $customSegmentRepository;
    private SMSService $smsService;
    private AuthServiceInterface $authService;
    private GraphQLFormatterInterface $formatter; // Add Formatter property
    private LoggerInterface $logger;

    public function __construct(
        SMSHistoryRepository $smsHistoryRepository,
        CustomSegmentRepository $customSegmentRepository,
        SMSService $smsService,
        AuthServiceInterface $authService,
        GraphQLFormatterInterface $formatter, // Inject Formatter
        LoggerInterface $logger
    ) {
        $this->smsHistoryRepository = $smsHistoryRepository;
        $this->customSegmentRepository = $customSegmentRepository;
        $this->smsService = $smsService;
        $this->authService = $authService;
        $this->formatter = $formatter; // Assign Formatter
        $this->logger = $logger;
    }

    /**
     * Resolver for the 'smsHistory' query.
     *
     * @param array<string, mixed> $args Contains 'limit', 'offset', 'userId'
     * @param mixed $context
     * @return array<int, array<string, mixed>>
     * @throws Exception
     */
    public function resolveSmsHistory(array $args, $context): array
    {
        $limit = isset($args['limit']) ? (int)$args['limit'] : 100;
        $offset = isset($args['offset']) ? (int)$args['offset'] : 0;
        $userId = isset($args['userId']) ? (int)$args['userId'] : null;
        $this->logger->info('Executing SMSResolver::resolveSmsHistory', ['limit' => $limit, 'offset' => $offset, 'userId' => $userId]);

        try {
            // --- Authentication/Authorization Check ---
            // --- Authentication/Authorization Check (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for resolveSmsHistory.');
                throw new Exception("User not authenticated");
            }
            $requestingUserId = $currentUser->getId();
            $isAdmin = $currentUser->isAdmin();

            // If a specific user's history is requested, check permissions
            if ($userId !== null && $userId !== $requestingUserId && !$isAdmin) {
                $this->logger->warning('User ' . $requestingUserId . ' attempted to access SMS history for user ' . $userId);
                throw new Exception("Permission denied");
            }

            // If no specific user ID is provided, default to the current user unless admin
            if ($userId === null && !$isAdmin) {
                $userId = $requestingUserId;
                $this->logger->info('Defaulting SMS history query to current user ID: ' . $userId);
            }
            // Admins can query all history if $userId is explicitly null or omitted
            // --- End Auth Check ---


            $history = $userId !== null
                ? $this->smsHistoryRepository->findByUserId($userId, $limit, $offset)
                : $this->smsHistoryRepository->findAll($limit, $offset); // Admin case

            $this->logger->info('Found ' . count($history) . ' SMS history records.');

            $result = [];
            foreach ($history as $item) {
                $result[] = $this->formatter->formatSmsHistory($item); // Use formatter
            }
            $this->logger->info('Formatted SMS history for GraphQL response.');
            return $result;
        } catch (Exception $e) {
            $this->logger->error('Error in SMSResolver::resolveSmsHistory: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'smsHistoryCount' query.
     *
     * @param array<string, mixed> $args Contains 'userId'
     * @param mixed $context
     * @return int
     * @throws Exception
     */
    public function resolveSmsHistoryCount(array $args, $context): int
    {
        $userId = isset($args['userId']) ? (int)$args['userId'] : null;
        $this->logger->info('Executing SMSResolver::resolveSmsHistoryCount', ['userId' => $userId]);

        try {
            // --- Authentication/Authorization Check (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for resolveSmsHistoryCount.');
                throw new Exception("User not authenticated");
            }
            $requestingUserId = $currentUser->getId();
            $isAdmin = $currentUser->isAdmin();

            if ($userId !== null && $userId !== $requestingUserId && !$isAdmin) {
                $this->logger->warning('User ' . $requestingUserId . ' attempted to access SMS history count for user ' . $userId);
                throw new Exception("Permission denied");
            }
            if ($userId === null && !$isAdmin) {
                $userId = $requestingUserId;
            }
            // --- End Auth Check ---

            $count = $userId !== null
                ? $this->smsHistoryRepository->countByUserId($userId)
                : $this->smsHistoryRepository->count(); // Admin case

            $this->logger->info('SMS history count: ' . $count);
            return $count;
        } catch (Exception $e) {
            $this->logger->error('Error in SMSResolver::resolveSmsHistoryCount: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'segmentsForSMS' query.
     *
     * @param array<string, mixed> $args
     * @param mixed $context
     * @return array<int, array<string, mixed>>
     * @throws Exception
     */
    public function resolveSegmentsForSMS(array $args, $context): array
    {
        $this->logger->info('Executing SMSResolver::resolveSegmentsForSMS');
        try {
            // --- Authentication Check (Using AuthService) ---
            if (!$this->authService->isAuthenticated()) {
                $this->logger->error('User not authenticated for resolveSegmentsForSMS.');
                throw new Exception("User not authenticated");
            }
            // --- End Auth Check ---

            // Assuming segments are global or user-specific logic is handled elsewhere/later
            $segments = $this->customSegmentRepository->findAll(); // Might need findByUserId later
            $this->logger->info('Found ' . count($segments) . ' segments for SMS.');

            $result = [];
            foreach ($segments as $segment) {
                // Get phone numbers for the segment and count them
                $phoneNumbers = $this->customSegmentRepository->findPhoneNumbersBySegmentId($segment->getId());
                $phoneNumberCount = count($phoneNumbers);
                $this->logger->debug('Segment ' . $segment->getId() . ' has ' . $phoneNumberCount . ' phone numbers.');

                // Use formatter for segment
                $result[] = $this->formatter->formatCustomSegment($segment, $phoneNumberCount);
            }
            $this->logger->info('Formatted segments for GraphQL response.');
            return $result;
        } catch (Exception $e) {
            $this->logger->error('Error in SMSResolver::resolveSegmentsForSMS: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'sendSms' mutation.
     *
     * @param array<string, mixed> $args Contains 'phoneNumber', 'message', 'userId'
     * @param mixed $context
     * @return array<string, mixed>
     * @throws Exception
     */
    public function mutateSendSms(array $args, $context): array
    {
        $phoneNumber = $args['phoneNumber'] ?? '';
        $message = $args['message'] ?? '';
        // If userId is provided in args, it might be an admin sending on behalf of someone
        // Otherwise, default to the logged-in user.
        $targetUserId = isset($args['userId']) ? (int)$args['userId'] : null;
        $this->logger->info('Executing SMSResolver::mutateSendSms', ['to' => $phoneNumber, 'targetUserId' => $targetUserId]);

        try {
            // --- Authentication/Authorization Check (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for mutateSendSms.');
                throw new Exception("User not authenticated");
            }
            $requestingUserId = $currentUser->getId();
            $isAdmin = $currentUser->isAdmin();

            // Determine the actual user ID for sending/logging
            $effectiveUserId = $targetUserId;
            if ($effectiveUserId === null) {
                $effectiveUserId = $requestingUserId; // Default to self
            } elseif ($effectiveUserId !== $requestingUserId && !$isAdmin) {
                // Non-admin trying to send as someone else
                $this->logger->warning('User ' . $requestingUserId . ' attempted to send SMS as user ' . $effectiveUserId);
                throw new Exception("Permission denied to send SMS as another user.");
            }
            $this->logger->info('Effective user ID for sending SMS: ' . $effectiveUserId);
            // --- End Auth Check ---

            // TODO: Add credit check logic here before sending
            // $canSend = $this->checkUserCredits($effectiveUserId, 1);
            // if (!$canSend) { throw new Exception("Insufficient SMS credits."); }

            $result = $this->smsService->sendSMS($phoneNumber, $message, $effectiveUserId);
            $this->logger->info('SMS send attempt result', ['result' => $result]);

            // Deduct credits on successful API call (even if delivery fails later)
            // $this->deductUserCredits($effectiveUserId, 1);

            // The SMSService should ideally handle history creation.
            // If not, create history record here based on $result.
            // For now, return a simplified structure based on schema.
            $status = isset($result['outboundSMSMessageRequest']) ? 'SENT' : 'FAILED'; // Basic check

            return [
                'id' => $result['history_id'] ?? uniqid(), // Use ID from history if available
                'phoneNumber' => $phoneNumber,
                'message' => $message,
                'status' => $status,
                'createdAt' => date('Y-m-d H:i:s') // Should come from history record
            ];
        } catch (Exception $e) {
            $this->logger->error('Error in SMSResolver::mutateSendSms: ' . $e->getMessage(), ['exception' => $e]);
            // Return error structure consistent with schema if possible
            return [
                'id' => uniqid(),
                'phoneNumber' => $phoneNumber,
                'message' => $message,
                'status' => 'FAILED', // Indicate failure
                'createdAt' => date('Y-m-d H:i:s')
            ];
            // Or re-throw: throw $e;
        }
    }

    /**
     * Resolver for the 'sendBulkSms' mutation.
     *
     * @param array<string, mixed> $args Contains 'phoneNumbers', 'message', 'userId'
     * @param mixed $context
     * @return array<string, mixed>
     * @throws Exception
     */
    public function mutateSendBulkSms(array $args, $context): array
    {
        $phoneNumbers = $args['phoneNumbers'] ?? [];
        $message = $args['message'] ?? '';
        $targetUserId = isset($args['userId']) ? (int)$args['userId'] : null;
        $this->logger->info('Executing SMSResolver::mutateSendBulkSms', ['count' => count($phoneNumbers), 'targetUserId' => $targetUserId]);

        if (empty($phoneNumbers) || empty($message)) {
            $this->logger->error('Missing phone numbers or message for sendBulkSms.', ['args' => $args]);
            throw new Exception("Liste de numéros et message requis.");
        }

        try {
            // --- Authentication/Authorization Check (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for mutateSendBulkSms.');
                throw new Exception("User not authenticated");
            }
            $requestingUserId = $currentUser->getId();
            $isAdmin = $currentUser->isAdmin();

            $effectiveUserId = $targetUserId ?? $requestingUserId;
            if ($effectiveUserId !== $requestingUserId && !$isAdmin) {
                throw new Exception("Permission denied to send bulk SMS as another user.");
            }
            $this->logger->info('Effective user ID for sending bulk SMS: ' . $effectiveUserId);
            // --- End Auth Check ---

            // TODO: Credit check for count($phoneNumbers)
            // $canSend = $this->checkUserCredits($effectiveUserId, count($phoneNumbers));
            // if (!$canSend) { throw new Exception("Insufficient SMS credits for bulk send."); }

            // Assuming sendBulkSMS returns an array like ['phoneNumber' => ['status' => 'success/error', 'message' => '...']]
            $results = $this->smsService->sendBulkSMS($phoneNumbers, $message, $effectiveUserId);
            $this->logger->info('Bulk SMS send attempt completed.');

            // TODO: Deduct credits based on successful sends or attempts? Policy needed.
            // $this->deductUserCredits($effectiveUserId, count($phoneNumbers)); // Or based on success count

            // Format results according to GraphQL schema
            $successful = 0;
            $failed = 0;
            $formattedResults = [];

            foreach ($results as $number => $result) {
                $isSuccess = ($result['status'] === 'success'); // Adjust based on actual return value
                if ($isSuccess) {
                    $successful++;
                } else {
                    $failed++;
                }
                $formattedResults[] = [
                    'phoneNumber' => $number,
                    'status' => $isSuccess ? 'SENT' : 'FAILED', // Match schema enum if defined
                    'message' => $result['message'] ?? ($isSuccess ? 'Envoyé' : 'Échec') // Provide default messages
                ];
            }

            return [
                'status' => 'COMPLETED', // Or 'PARTIAL' if some failed? Schema needs clarity.
                'message' => 'Envoi en masse terminé.',
                'summary' => [
                    'total' => count($phoneNumbers),
                    'successful' => $successful,
                    'failed' => $failed
                ],
                'results' => $formattedResults
            ];
        } catch (Exception $e) {
            $this->logger->error('Error in SMSResolver::mutateSendBulkSms: ' . $e->getMessage(), ['exception' => $e]);
            // Return error structure
            return [
                'status' => 'ERROR',
                'message' => 'Erreur lors de l\'envoi en masse: ' . $e->getMessage(),
                'summary' => ['total' => count($phoneNumbers), 'successful' => 0, 'failed' => count($phoneNumbers)],
                'results' => []
            ];
            // Or re-throw: throw $e;
        }
    }

    /**
     * Resolver for the 'sendSmsToSegment' mutation.
     *
     * @param array<string, mixed> $args Contains 'segmentId', 'message', 'userId'
     * @param mixed $context
     * @return array<string, mixed>
     * @throws Exception
     */
    public function mutateSendSmsToSegment(array $args, $context): array
    {
        $segmentId = (int)($args['segmentId'] ?? 0);
        $message = $args['message'] ?? '';
        $targetUserId = isset($args['userId']) ? (int)$args['userId'] : null;
        $this->logger->info('Executing SMSResolver::mutateSendSmsToSegment', ['segmentId' => $segmentId, 'targetUserId' => $targetUserId]);

        if ($segmentId <= 0 || empty($message)) {
            $this->logger->error('Invalid segment ID or empty message for sendSmsToSegment.', ['args' => $args]);
            throw new Exception("ID de segment et message requis.");
        }

        try {
            // --- Authentication/Authorization Check (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for mutateSendSmsToSegment.');
                throw new Exception("User not authenticated");
            }
            $requestingUserId = $currentUser->getId();
            $isAdmin = $currentUser->isAdmin();

            $effectiveUserId = $targetUserId ?? $requestingUserId;
            if ($effectiveUserId !== $requestingUserId && !$isAdmin) {
                throw new Exception("Permission denied to send segment SMS as another user.");
            }
            $this->logger->info('Effective user ID for sending segment SMS: ' . $effectiveUserId);
            // --- End Auth Check ---

            // Check if segment exists (and potentially belongs to user if not global)
            $segment = $this->customSegmentRepository->findById($segmentId);
            if (!$segment) {
                $this->logger->error('Segment not found for sendSmsToSegment with ID: ' . $segmentId);
                throw new Exception("Segment non trouvé");
            }
            // TODO: Add ownership check if segments are user-specific

            // TODO: Get count of numbers in segment for credit check
            // $numberCount = $this->customSegmentRepository->countContactsInSegment($segmentId);
            // $canSend = $this->checkUserCredits($effectiveUserId, $numberCount);
            // if (!$canSend) { throw new Exception("Insufficient SMS credits for segment send."); }


            // Assuming sendSMSToSegment returns similar structure to sendBulkSMS
            $results = $this->smsService->sendSMSToSegment($segmentId, $message, $effectiveUserId);
            $this->logger->info('Segment SMS send attempt completed for segment ID: ' . $segmentId);

            // TODO: Deduct credits
            // $this->deductUserCredits($effectiveUserId, $numberCount); // Or based on success count

            // Format results
            $successful = 0;
            $failed = 0;
            $formattedResults = [];
            $total = 0; // Need the service to return the total count ideally

            foreach ($results as $number => $result) {
                $total++;
                $isSuccess = ($result['status'] === 'success');
                if ($isSuccess) {
                    $successful++;
                } else {
                    $failed++;
                }
                $formattedResults[] = [
                    'phoneNumber' => $number,
                    'status' => $isSuccess ? 'SENT' : 'FAILED',
                    'message' => $result['message'] ?? ($isSuccess ? 'Envoyé' : 'Échec')
                ];
            }

            return [
                'status' => 'COMPLETED',
                'message' => 'Envoi au segment terminé.',
                'segment' => [ // Include segment info in response
                    'id' => $segment->getId(),
                    'name' => $segment->getName()
                ],
                'summary' => [
                    'total' => $total, // Use actual count
                    'successful' => $successful,
                    'failed' => $failed
                ],
                'results' => $formattedResults
            ];
        } catch (Exception $e) {
            $this->logger->error('Error in SMSResolver::mutateSendSmsToSegment: ' . $e->getMessage(), ['exception' => $e]);
            return [
                'status' => 'ERROR',
                'message' => 'Erreur lors de l\'envoi au segment: ' . $e->getMessage(),
                'segment' => ['id' => $segmentId, 'name' => 'Unknown'], // Provide ID even on error
                'summary' => ['total' => 0, 'successful' => 0, 'failed' => 0],
                'results' => []
            ];
            // Or re-throw: throw $e;
        }
    }

    /**
     * Resolver for the 'retrySms' mutation.
     *
     * @param array<string, mixed> $args Contains 'id', 'userId'
     * @param mixed $context
     * @return array<string, mixed>
     * @throws Exception
     */
    public function mutateRetrySms(array $args, $context): array
    {
        $historyId = (int)($args['id'] ?? 0);
        // userId in args might specify who the *original* send was for, or who is retrying
        $targetUserId = isset($args['userId']) ? (int)$args['userId'] : null; // Ambiguous, needs clarification
        $this->logger->info('Executing SMSResolver::mutateRetrySms', ['historyId' => $historyId, 'targetUserId' => $targetUserId]);

        if ($historyId <= 0) {
            $this->logger->error('Invalid history ID provided for retrySms.', ['args' => $args]);
            throw new Exception("ID d'historique invalide.");
        }

        try {
            // --- Authentication/Authorization Check (Using AuthService) ---
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                $this->logger->error('User not authenticated for mutateRetrySms.');
                throw new Exception("User not authenticated");
            }
            $requestingUserId = $currentUser->getId();
            $isAdmin = $currentUser->isAdmin();
            // --- End Auth Check ---

            // Get the original SMS history record
            $smsHistory = $this->smsHistoryRepository->findById($historyId);
            if (!$smsHistory) {
                $this->logger->error('SMS history record not found for retry with ID: ' . $historyId);
                throw new Exception("Enregistrement d'historique SMS non trouvé.");
            }

            // Authorization: Check if the current user is allowed to retry this SMS
            // Policy: Allow retry if it's their own SMS or if they are admin?
            $originalUserId = $smsHistory->getUserId();
            if ($originalUserId !== $requestingUserId && !$isAdmin) {
                $this->logger->warning('User ' . $requestingUserId . ' attempted to retry SMS ' . $historyId . ' belonging to user ' . $originalUserId);
                throw new Exception("Permission denied to retry this SMS.");
            }

            // Determine the user ID for the retry attempt (usually the original user)
            $effectiveUserId = $originalUserId; // Retry as the original sender
            $this->logger->info('Effective user ID for retrying SMS: ' . $effectiveUserId);

            // TODO: Credit check for 1 SMS
            // $canSend = $this->checkUserCredits($effectiveUserId, 1);
            // if (!$canSend) { throw new Exception("Insufficient SMS credits for retry."); }

            // Retry sending the SMS using the service
            // Pass the effectiveUserId for logging/credit purposes
            $result = $this->smsService->sendSMS($smsHistory->getPhoneNumber(), $smsHistory->getMessage(), $effectiveUserId);
            $this->logger->info('SMS retry attempt result', ['result' => $result]);

            // TODO: Deduct credit for the retry attempt
            // $this->deductUserCredits($effectiveUserId, 1);

            // SMSService should ideally create the new history record for the retry.
            // If not, we'd create it here. Assuming SMSService handles it and returns info.
            $status = isset($result['outboundSMSMessageRequest']) ? 'SENT' : 'FAILED';

            // Return data matching the SMSResult type in the schema
            return [
                'id' => $result['history_id'] ?? uniqid(), // ID of the *new* history record
                'phoneNumber' => $smsHistory->getPhoneNumber(),
                'message' => $smsHistory->getMessage(),
                'status' => $status,
                'createdAt' => date('Y-m-d H:i:s') // Should be from the new history record
            ];
        } catch (Exception $e) {
            $this->logger->error('Error in SMSResolver::mutateRetrySms: ' . $e->getMessage(), ['exception' => $e]);
            return [
                'id' => $historyId, // Return original ID on error? Schema unclear.
                'phoneNumber' => $smsHistory->getPhoneNumber() ?? 'unknown',
                'message' => $smsHistory->getMessage() ?? 'unknown',
                'status' => 'FAILED',
                'createdAt' => date('Y-m-d H:i:s')
            ];
            // Or re-throw: throw $e;
        }
    }


    // --- Helper Methods (Removed formatSmsHistory) ---

    // Placeholder for credit check logic (to be implemented in Phase 2/AuthService)
    private function checkUserCredits(int $userId, int $requiredCredits): bool
    {
        // Fetch user, check credits >= requiredCredits
        $this->logger->info('Placeholder: Checking credits for user ' . $userId . ', needs ' . $requiredCredits);
        return true; // Assume sufficient for now
    }

    // Placeholder for credit deduction logic (to be implemented in Phase 2/AuthService)
    private function deductUserCredits(int $userId, int $creditsToDeduct): void
    {
        // Fetch user, update credits, save user
        $this->logger->info('Placeholder: Deducting ' . $creditsToDeduct . ' credits from user ' . $userId);
        // Implementation needed
    }
}
