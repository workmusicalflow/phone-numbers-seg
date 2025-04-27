<?php

namespace App\GraphQL\Resolvers;

use App\Repositories\Interfaces\SMSHistoryRepositoryInterface;
use App\Repositories\Interfaces\CustomSegmentRepositoryInterface;
use App\Services\SMSService;
use App\Entities\SMSHistory;
use App\Entities\Segment;
use App\Entities\CustomSegment; // Explicitly use CustomSegment where needed
use App\Services\Interfaces\AuthServiceInterface;
use App\GraphQL\Formatters\GraphQLFormatterInterface;
use Exception;
use Psr\Log\LoggerInterface;

class SMSResolver
{
    private SMSHistoryRepositoryInterface $smsHistoryRepository;
    private CustomSegmentRepositoryInterface $customSegmentRepository;
    private SMSService $smsService;
    private AuthServiceInterface $authService;
    private GraphQLFormatterInterface $formatter;
    private LoggerInterface $logger;

    public function __construct(
        SMSHistoryRepositoryInterface $smsHistoryRepository,
        CustomSegmentRepositoryInterface $customSegmentRepository,
        SMSService $smsService,
        AuthServiceInterface $authService,
        GraphQLFormatterInterface $formatter,
        LoggerInterface $logger
    ) {
        $this->smsHistoryRepository = $smsHistoryRepository;
        $this->customSegmentRepository = $customSegmentRepository;
        $this->smsService = $smsService;
        $this->authService = $authService;
        $this->formatter = $formatter;
        $this->logger = $logger;
    }

    /**
     * Resolver for the 'smsHistory' query.
     * Handles filtering by userId, status, search term (phone number), and segmentId.
     * Uses DataLoader for optimized batching of similar queries.
     */
    public function resolveSmsHistory(array $args, $context): array
    {
        $limit = isset($args['limit']) ? (int)$args['limit'] : 100;
        $offset = isset($args['offset']) ? (int)$args['offset'] : 0;
        $userId = isset($args['userId']) ? (int)$args['userId'] : null;
        $status = $args['status'] ?? null;
        $search = $args['search'] ?? null;
        $segmentId = isset($args['segmentId']) ? (int)$args['segmentId'] : null;

        $this->logger->info('Executing SMSResolver::resolveSmsHistory', [
            'limit' => $limit,
            'offset' => $offset,
            'userId' => $userId,
            'status' => $status,
            'search' => $search,
            'segmentId' => $segmentId
        ]);

        try {
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                throw new Exception("User not authenticated");
            }
            $requestingUserId = $currentUser->getId();
            $isAdmin = $currentUser->isAdmin();

            // Authorization: Non-admins can only see their own history unless explicitly querying for someone else (which is denied if not admin)
            if ($userId !== null && $userId !== $requestingUserId && !$isAdmin) {
                $this->logger->warning('Permission denied: User ' . $requestingUserId . ' attempted to access history for user ' . $userId);
                throw new Exception("Permission denied");
            }
            // If no specific user is requested by an admin, show all. If no user requested by non-admin, show their own.
            $effectiveUserId = $userId;
            if ($userId === null && !$isAdmin) {
                $effectiveUserId = $requestingUserId;
            }

            // Build criteria array
            $criteria = [];
            if ($effectiveUserId !== null) {
                $criteria['userId'] = $effectiveUserId;
            }
            if ($status !== null) {
                $criteria['status'] = $status;
            }
            if ($search !== null) {
                $criteria['search'] = $search; // Repository needs to handle LIKE query
            }
            if ($segmentId !== null) {
                $criteria['segmentId'] = $segmentId;
            }
            
            // Add pagination to criteria so it's part of the cache key
            $criteria['_limit'] = $limit;
            $criteria['_offset'] = $offset;
            
            $this->logger->debug('Constructed criteria for smsHistory query', ['criteria' => $criteria]);

            // Use DataLoader if available in the context
            if (isset($context) && method_exists($context, 'getDataLoader')) {
                $dataLoader = $context->getDataLoader('smsHistory');
                if ($dataLoader) {
                    $this->logger->debug('Using context-scoped SMSHistoryDataLoader for batch loading');
                    
                    // Load using DataLoader for efficient batching
                    return $dataLoader->load($criteria);
                }
            }
            
            // Fallback to direct repository call if DataLoader is not available
            $this->logger->debug('No DataLoader found, using direct repository call');
            $history = $this->smsHistoryRepository->findByCriteria($criteria, $limit, $offset);
            $this->logger->info('Fetched ' . count($history) . ' SMS history records based on criteria.');

            $result = [];
            foreach ($history as $item) {
                $result[] = $this->formatter->formatSmsHistory($item);
            }
            return $result;
        } catch (Exception $e) {
            $this->logger->error('Error in SMSResolver::resolveSmsHistory: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'smsHistoryCount' query.
     */
    public function resolveSmsHistoryCount(array $args, $context): int
    {
        $userId = isset($args['userId']) ? (int)$args['userId'] : null;
        $status = $args['status'] ?? null;
        $search = $args['search'] ?? null;
        $segmentId = isset($args['segmentId']) ? (int)$args['segmentId'] : null;

        $this->logger->info('Executing SMSResolver::resolveSmsHistoryCount', [
            'userId' => $userId,
            'status' => $status,
            'search' => $search,
            'segmentId' => $segmentId
        ]);

        try {
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                throw new Exception("User not authenticated");
            }
            $requestingUserId = $currentUser->getId();
            $isAdmin = $currentUser->isAdmin();

            if ($userId !== null && $userId !== $requestingUserId && !$isAdmin) {
                throw new Exception("Permission denied");
            }
            // If no specific user is requested by an admin, show all. If no user requested by non-admin, show their own.
            $effectiveUserId = $userId;
            if ($userId === null && !$isAdmin) {
                $effectiveUserId = $requestingUserId;
            }

            // Build criteria array
            $criteria = [];
            if ($effectiveUserId !== null) {
                $criteria['userId'] = $effectiveUserId;
            }
            if ($status !== null) {
                $criteria['status'] = $status;
            }
            if ($search !== null) {
                $criteria['search'] = $search; // Repository needs to handle LIKE query
            }
            if ($segmentId !== null) {
                $criteria['segmentId'] = $segmentId;
            }
            $this->logger->debug('Constructed criteria for smsHistoryCount query', ['criteria' => $criteria]);

            // Call repository method that handles multiple criteria
            // Assuming countByCriteria exists or will be created in the repository
            $count = $this->smsHistoryRepository->countByCriteria($criteria);
            $this->logger->info('Counted ' . $count . ' SMS history records based on criteria.');

            return $count;
        } catch (Exception $e) {
            $this->logger->error('Error in SMSResolver::resolveSmsHistoryCount: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'segmentsForSMS' query.
     */
    public function resolveSegmentsForSMS(array $args, $context): array
    {
        $this->logger->info('Executing SMSResolver::resolveSegmentsForSMS');
        try {
            if (!$this->authService->isAuthenticated()) {
                throw new Exception("User not authenticated");
            }

            $segments = $this->customSegmentRepository->findAll(); // Assuming CustomSegment is the relevant type here
            $result = [];
            foreach ($segments as $segment) {
                if ($segment instanceof CustomSegment) { // Ensure it's the correct type before formatting
                    $phoneNumbers = $this->customSegmentRepository->findPhoneNumbersBySegmentId($segment->getId());
                    $phoneNumberCount = count($phoneNumbers);
                    $result[] = $this->formatter->formatCustomSegment($segment, $phoneNumberCount);
                }
            }
            return $result;
        } catch (Exception $e) {
            $this->logger->error('Error in SMSResolver::resolveSegmentsForSMS: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'sendSms' mutation.
     */
    public function mutateSendSms(array $args, $context): array
    {
        $phoneNumber = $args['phoneNumber'] ?? '';
        $message = $args['message'] ?? '';
        $targetUserId = isset($args['userId']) ? (int)$args['userId'] : null;
        $this->logger->info('Executing SMSResolver::mutateSendSms', ['to' => $phoneNumber, 'targetUserId' => $targetUserId]);

        try {
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                throw new Exception("User not authenticated");
            }
            $requestingUserId = $currentUser->getId();
            $isAdmin = $currentUser->isAdmin();

            $effectiveUserId = $targetUserId ?? $requestingUserId;
            if ($effectiveUserId !== $requestingUserId && !$isAdmin) {
                throw new Exception("Permission denied to send SMS as another user.");
            }

            $result = $this->smsService->sendSMS($phoneNumber, $message, $effectiveUserId);
            $status = isset($result['outboundSMSMessageRequest']) ? 'SENT' : 'FAILED';

            // Assuming SMSService logs history and returns necessary info or ID
            // We might need to fetch the history record if ID isn't returned directly
            return [
                'id' => $result['history_id'] ?? uniqid(), // Placeholder if ID not returned
                'phoneNumber' => $phoneNumber,
                'message' => $message,
                'status' => $status,
                'createdAt' => date('Y-m-d H:i:s') // Placeholder
            ];
        } catch (Exception $e) {
            $this->logger->error('Error in SMSResolver::mutateSendSms: ' . $e->getMessage(), ['exception' => $e]);
            return [ // Return structure consistent with SMSResult on error
                'id' => uniqid(),
                'phoneNumber' => $phoneNumber,
                'message' => $message,
                'status' => 'FAILED',
                'createdAt' => date('Y-m-d H:i:s')
            ];
        }
    }

    /**
     * Resolver for the 'sendBulkSms' mutation.
     */
    public function mutateSendBulkSms(array $args, $context): array
    {
        $phoneNumbers = $args['phoneNumbers'] ?? [];
        $message = $args['message'] ?? '';
        $targetUserId = isset($args['userId']) ? (int)$args['userId'] : null;
        $this->logger->info('Executing SMSResolver::mutateSendBulkSms', ['count' => count($phoneNumbers), 'targetUserId' => $targetUserId]);

        if (empty($phoneNumbers) || empty($message)) {
            throw new Exception("Liste de numéros et message requis.");
        }

        try {
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                throw new Exception("User not authenticated");
            }
            $requestingUserId = $currentUser->getId();
            $isAdmin = $currentUser->isAdmin();

            $effectiveUserId = $targetUserId ?? $requestingUserId;
            if ($effectiveUserId !== $requestingUserId && !$isAdmin) {
                throw new Exception("Permission denied to send bulk SMS as another user.");
            }

            $results = $this->smsService->sendBulkSMS($phoneNumbers, $message, $effectiveUserId);
            $successful = 0;
            $failed = 0;
            $formattedResults = [];

            foreach ($results as $number => $result) {
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
                'message' => 'Envoi en masse terminé.',
                'summary' => ['total' => count($phoneNumbers), 'successful' => $successful, 'failed' => $failed],
                'results' => $formattedResults
            ];
        } catch (Exception $e) {
            $this->logger->error('Error in SMSResolver::mutateSendBulkSms: ' . $e->getMessage(), ['exception' => $e]);
            return [
                'status' => 'ERROR',
                'message' => 'Erreur lors de l\'envoi en masse: ' . $e->getMessage(),
                'summary' => ['total' => count($phoneNumbers), 'successful' => 0, 'failed' => count($phoneNumbers)],
                'results' => []
            ];
        }
    }

    /**
     * Resolver for the 'sendSmsToSegment' mutation.
     */
    public function mutateSendSmsToSegment(array $args, $context): array
    {
        $segmentId = (int)($args['segmentId'] ?? 0);
        $message = $args['message'] ?? '';
        $targetUserId = isset($args['userId']) ? (int)$args['userId'] : null;
        $this->logger->info('Executing SMSResolver::mutateSendSmsToSegment', ['segmentId' => $segmentId, 'targetUserId' => $targetUserId]);

        if ($segmentId <= 0 || empty($message)) {
            throw new Exception("ID de segment et message requis.");
        }

        try {
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                throw new Exception("User not authenticated");
            }
            $requestingUserId = $currentUser->getId();
            $isAdmin = $currentUser->isAdmin();

            $effectiveUserId = $targetUserId ?? $requestingUserId;
            if ($effectiveUserId !== $requestingUserId && !$isAdmin) {
                throw new Exception("Permission denied to send segment SMS as another user.");
            }

            $segment = $this->customSegmentRepository->findById($segmentId);
            if (!$segment) {
                throw new Exception("Segment non trouvé");
            }

            $results = $this->smsService->sendSMSToSegment($segmentId, $message, $effectiveUserId);
            $successful = 0;
            $failed = 0;
            $formattedResults = [];
            $total = 0;

            foreach ($results as $number => $result) {
                $total++;
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
                'message' => 'Envoi au segment terminé.',
                'segment' => $this->formatter->formatCustomSegment($segment), // Format segment info
                'summary' => ['total' => $total, 'successful' => $successful, 'failed' => $failed],
                'results' => $formattedResults
            ];
        } catch (Exception $e) {
            $this->logger->error('Error in SMSResolver::mutateSendSmsToSegment: ' . $e->getMessage(), ['exception' => $e]);
            return [
                'status' => 'ERROR',
                'message' => 'Erreur lors de l\'envoi au segment: ' . $e->getMessage(),
                'segment' => ['id' => $segmentId, 'name' => 'Unknown'],
                'summary' => ['total' => 0, 'successful' => 0, 'failed' => 0],
                'results' => []
            ];
        }
    }

    /**
     * Resolver for the 'sendSmsToAllContacts' mutation.
     */
    public function mutateSendSmsToAllContacts(array $args, $context): array
    {
        $message = $args['message'] ?? '';
        $this->logger->info('Executing SMSResolver::mutateSendSmsToAllContacts');

        if (empty($message)) {
            throw new Exception("Message requis.");
        }

        try {
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                throw new Exception("User not authenticated");
            }
            $userId = $currentUser->getId();

            // Call the service method (to be created in SMSService)
            $result = $this->smsService->sendToAllContacts($userId, $message);

            $this->logger->info('Send SMS to all contacts attempt completed.');
            return $result; // Assuming service returns BulkSMSResult structure

        } catch (Exception $e) {
            $this->logger->error('Error in SMSResolver::mutateSendSmsToAllContacts: ' . $e->getMessage(), ['exception' => $e]);
            return [
                'status' => 'ERROR',
                'message' => 'Erreur lors de l\'envoi à tous les contacts: ' . $e->getMessage(),
                'summary' => ['total' => 0, 'successful' => 0, 'failed' => 0],
                'results' => []
            ];
        }
    }

    /**
     * Resolver for the 'retrySms' mutation.
     */
    public function mutateRetrySms(array $args, $context): array
    {
        $historyId = (int)($args['id'] ?? 0);
        $this->logger->info('Executing SMSResolver::mutateRetrySms', ['historyId' => $historyId]);

        if ($historyId <= 0) {
            throw new Exception("ID d'historique invalide.");
        }

        try {
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser) {
                throw new Exception("User not authenticated");
            }
            $requestingUserId = $currentUser->getId();
            $isAdmin = $currentUser->isAdmin();

            $smsHistory = $this->smsHistoryRepository->findById($historyId);
            if (!$smsHistory) {
                throw new Exception("Enregistrement d'historique SMS non trouvé.");
            }

            $originalUserId = $smsHistory->getUserId();
            if ($originalUserId !== $requestingUserId && !$isAdmin) {
                throw new Exception("Permission denied to retry this SMS.");
            }

            $effectiveUserId = $originalUserId;

            $result = $this->smsService->sendSMS($smsHistory->getPhoneNumber(), $smsHistory->getMessage(), $effectiveUserId);
            $status = isset($result['outboundSMSMessageRequest']) ? 'SENT' : 'FAILED';

            return [
                'id' => $result['history_id'] ?? uniqid(),
                'phoneNumber' => $smsHistory->getPhoneNumber(),
                'message' => $smsHistory->getMessage(),
                'status' => $status,
                'createdAt' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            $this->logger->error('Error in SMSResolver::mutateRetrySms: ' . $e->getMessage(), ['exception' => $e]);
            return [
                'id' => $historyId,
                'phoneNumber' => $smsHistory?->getPhoneNumber() ?? 'unknown', // Use null safe operator
                'message' => $smsHistory?->getMessage() ?? 'unknown',
                'status' => 'FAILED',
                'createdAt' => date('Y-m-d H:i:s')
            ];
        }
    }

    // Placeholder methods (can be removed if not used elsewhere)
    private function checkUserCredits(int $userId, int $requiredCredits): bool
    {
        $this->logger->info('Placeholder: Checking credits for user ' . $userId . ', needs ' . $requiredCredits);
        return true;
    }

    private function deductUserCredits(int $userId, int $creditsToDeduct): void
    {
        $this->logger->info('Placeholder: Deducting ' . $creditsToDeduct . ' credits from user ' . $userId);
    }
}
