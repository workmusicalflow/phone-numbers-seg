<?php

namespace App\Services;

use App\Entities\SMSQueue;
use App\Repositories\Interfaces\SMSQueueRepositoryInterface;
use App\Repositories\Interfaces\PhoneNumberRepositoryInterface;
use App\Repositories\Interfaces\ContactRepositoryInterface;
use App\Repositories\Interfaces\SegmentRepositoryInterface;
use App\Services\Interfaces\SMSQueueServiceInterface;
use App\Services\Interfaces\OrangeAPIClientInterface;
use App\Services\Interfaces\AuthServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * SMS Queue Service
 */
class SMSQueueService implements SMSQueueServiceInterface
{
    /**
     * @var SMSQueueRepositoryInterface
     */
    private $smsQueueRepository;

    /**
     * @var PhoneNumberRepositoryInterface
     */
    private $phoneNumberRepository;

    /**
     * @var ContactRepositoryInterface
     */
    private $contactRepository;

    /**
     * @var SegmentRepositoryInterface
     */
    private $segmentRepository;

    /**
     * @var OrangeAPIClientInterface
     */
    private $orangeAPIClient;

    /**
     * @var AuthServiceInterface
     */
    private $authService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $maxAttempts;

    /**
     * Constructor
     *
     * @param SMSQueueRepositoryInterface $smsQueueRepository
     * @param PhoneNumberRepositoryInterface $phoneNumberRepository
     * @param ContactRepositoryInterface $contactRepository
     * @param SegmentRepositoryInterface $segmentRepository
     * @param OrangeAPIClientInterface $orangeAPIClient
     * @param AuthServiceInterface $authService
     * @param LoggerInterface $logger
     * @param int $maxAttempts Maximum number of retry attempts
     */
    public function __construct(
        SMSQueueRepositoryInterface $smsQueueRepository,
        PhoneNumberRepositoryInterface $phoneNumberRepository,
        ContactRepositoryInterface $contactRepository,
        SegmentRepositoryInterface $segmentRepository,
        OrangeAPIClientInterface $orangeAPIClient,
        AuthServiceInterface $authService,
        LoggerInterface $logger,
        int $maxAttempts = 5
    ) {
        $this->smsQueueRepository = $smsQueueRepository;
        $this->phoneNumberRepository = $phoneNumberRepository;
        $this->contactRepository = $contactRepository;
        $this->segmentRepository = $segmentRepository;
        $this->orangeAPIClient = $orangeAPIClient;
        $this->authService = $authService;
        $this->logger = $logger;
        $this->maxAttempts = $maxAttempts;
    }

    /**
     * {@inheritdoc}
     */
    public function enqueue(
        string $phoneNumber,
        string $message,
        ?int $userId = null,
        ?int $segmentId = null,
        ?string $senderName = null,
        int $priority = SMSQueue::PRIORITY_NORMAL,
        ?string $batchId = null
    ): SMSQueue {
        $this->logger->info('Enqueuing SMS', [
            'phoneNumber' => $phoneNumber,
            'userId' => $userId,
            'segmentId' => $segmentId,
            'priority' => $priority,
            'batchId' => $batchId
        ]);

        $smsQueue = new SMSQueue();
        $smsQueue->setPhoneNumber($phoneNumber);
        $smsQueue->setMessage($message);
        $smsQueue->setUserId($userId);
        $smsQueue->setSegmentId($segmentId);
        $smsQueue->setPriority($priority);
        $smsQueue->setBatchId($batchId);
        $smsQueue->setSenderName($senderName);
        $smsQueue->setSenderAddress($this->orangeAPIClient->getSenderAddress());
        $smsQueue->setStatus(SMSQueue::STATUS_PENDING);
        $smsQueue->setCreatedAt(new \DateTime());
        $smsQueue->setNextAttemptAt(new \DateTime());

        try {
            $smsQueue = $this->smsQueueRepository->save($smsQueue);
            $this->logger->debug('SMS enqueued successfully', [
                'queueId' => $smsQueue->getId()
            ]);
            return $smsQueue;
        } catch (\Exception $e) {
            $this->logger->error('Failed to enqueue SMS', [
                'phoneNumber' => $phoneNumber,
                'userId' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function enqueueBulk(
        array $phoneNumbers,
        string $message,
        ?int $userId = null,
        ?int $segmentId = null,
        ?string $senderName = null,
        int $priority = SMSQueue::PRIORITY_NORMAL
    ): string {
        $phoneNumbers = array_unique($phoneNumbers);
        $count = count($phoneNumbers);
        
        $this->logger->info('Enqueuing bulk SMS', [
            'count' => $count,
            'userId' => $userId,
            'segmentId' => $segmentId,
            'priority' => $priority
        ]);

        if (empty($phoneNumbers)) {
            $this->logger->warning('No phone numbers provided for bulk enqueue');
            return '';
        }

        // Generate a batch ID
        $batchId = uniqid('batch_', true);

        // Check user credits if applicable
        if ($userId !== null) {
            $user = $this->authService->getUserById($userId);
            if ($user === null) {
                $this->logger->error('User not found', ['userId' => $userId]);
                throw new \RuntimeException("User not found");
            }

            if ($user->getSmsCredit() < $count) {
                $this->logger->warning('Insufficient SMS credits', [
                    'userId' => $userId,
                    'available' => $user->getSmsCredit(),
                    'required' => $count
                ]);
                throw new \RuntimeException("Insufficient SMS credits: {$user->getSmsCredit()} available, {$count} required");
            }
        }

        $queueEntries = [];
        foreach ($phoneNumbers as $phoneNumber) {
            $smsQueue = new SMSQueue();
            $smsQueue->setPhoneNumber($phoneNumber);
            $smsQueue->setMessage($message);
            $smsQueue->setUserId($userId);
            $smsQueue->setSegmentId($segmentId);
            $smsQueue->setPriority($priority);
            $smsQueue->setBatchId($batchId);
            $smsQueue->setSenderName($senderName);
            $smsQueue->setSenderAddress($this->orangeAPIClient->getSenderAddress());
            $smsQueue->setStatus(SMSQueue::STATUS_PENDING);
            $smsQueue->setCreatedAt(new \DateTime());
            $smsQueue->setNextAttemptAt(new \DateTime());

            $queueEntries[] = $smsQueue;
        }

        try {
            $this->smsQueueRepository->saveBatch($queueEntries);
            $this->logger->info('Bulk SMS enqueued successfully', [
                'batchId' => $batchId,
                'count' => $count
            ]);
            return $batchId;
        } catch (\Exception $e) {
            $this->logger->error('Failed to enqueue bulk SMS', [
                'error' => $e->getMessage(),
                'count' => $count
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function enqueueSegment(
        int $segmentId,
        string $message,
        ?int $userId = null,
        ?string $senderName = null,
        int $priority = SMSQueue::PRIORITY_NORMAL
    ): string {
        $this->logger->info('Enqueuing SMS for segment', [
            'segmentId' => $segmentId,
            'userId' => $userId,
            'priority' => $priority
        ]);

        // Find segment
        $segment = $this->segmentRepository->findById($segmentId);
        if ($segment === null) {
            $this->logger->error('Segment not found', ['segmentId' => $segmentId]);
            throw new \RuntimeException("Segment not found");
        }

        // Get phone numbers in segment
        $phoneNumbers = $this->phoneNumberRepository->findBySegmentId($segmentId);
        $phoneNumberStrings = array_map(function($pn) { return $pn->getNumber(); }, $phoneNumbers);

        if (empty($phoneNumberStrings)) {
            $this->logger->warning('No phone numbers found in segment', ['segmentId' => $segmentId]);
            return '';
        }

        // Use enqueueBulk to handle the actual queue entries
        return $this->enqueueBulk(
            $phoneNumberStrings,
            $message,
            $userId,
            $segmentId,
            $senderName,
            $priority
        );
    }

    /**
     * {@inheritdoc}
     */
    public function enqueueAllContacts(
        int $userId,
        string $message,
        ?string $senderName = null,
        int $priority = SMSQueue::PRIORITY_NORMAL
    ): string {
        $this->logger->info('Enqueuing SMS for all contacts', [
            'userId' => $userId,
            'priority' => $priority
        ]);

        // Get user to verify existence
        $user = $this->authService->getUserById($userId);
        if ($user === null) {
            $this->logger->error('User not found', ['userId' => $userId]);
            throw new \RuntimeException("User not found");
        }

        // Get all contacts for the user
        $contacts = $this->contactRepository->findByUserId($userId);
        if (empty($contacts)) {
            $this->logger->warning('No contacts found for user', ['userId' => $userId]);
            return '';
        }

        // Extract unique phone numbers
        $phoneNumbers = [];
        foreach ($contacts as $contact) {
            $phone = $contact->getPhoneNumber();
            if (!empty($phone)) {
                $phoneNumbers[$phone] = true;
            }
        }
        $uniqueNumbers = array_keys($phoneNumbers);

        if (empty($uniqueNumbers)) {
            $this->logger->warning('No valid phone numbers found in contacts', ['userId' => $userId]);
            return '';
        }

        // Use enqueueBulk to handle the actual queue entries
        return $this->enqueueBulk(
            $uniqueNumbers,
            $message,
            $userId,
            null, // No segment ID
            $senderName,
            $priority
        );
    }

    /**
     * {@inheritdoc}
     */
    public function processNextBatch(int $batchSize = 50): array
    {
        $this->logger->info('Processing next batch of SMS', ['batchSize' => $batchSize]);

        // Reset entries that have been stuck in PROCESSING state
        $threshold = new \DateTime('-10 minutes');
        $expiredProcessing = $this->smsQueueRepository->findExpiredProcessing($threshold);
        foreach ($expiredProcessing as $item) {
            $this->logger->warning('Resetting stuck SMS queue entry', [
                'id' => $item->getId(),
                'lastAttempt' => $item->getLastAttemptAt() ? $item->getLastAttemptAt()->format('Y-m-d H:i:s') : 'null'
            ]);
            $item->markAsFailed('Processing timeout', $this->maxAttempts);
            $this->smsQueueRepository->save($item);
        }

        // Get next batch of pending SMS
        $pendingItems = $this->smsQueueRepository->findNextBatch($batchSize);
        if (empty($pendingItems)) {
            $this->logger->debug('No pending SMS to process');
            return ['sent' => 0, 'failed' => 0, 'total' => 0];
        }

        $this->logger->info('Found ' . count($pendingItems) . ' SMS to process');

        $sent = 0;
        $failed = 0;
        $deductedCredits = []; // Track deducted credits by user ID to avoid race conditions

        // Process each item
        foreach ($pendingItems as $item) {
            try {
                // Mark as processing
                $item->markAsProcessing();
                $this->smsQueueRepository->save($item);

                // Check user credits
                $userId = $item->getUserId();
                if ($userId !== null) {
                    // Get current deducted count for this user in this batch
                    $currentDeducted = $deductedCredits[$userId] ?? 0;
                    
                    // Get user
                    $user = $this->authService->getUserById($userId);
                    if ($user === null) {
                        throw new \RuntimeException("User not found");
                    }

                    // Check if enough credits left
                    if ($user->getSmsCredit() - $currentDeducted <= 0) {
                        throw new \RuntimeException("Insufficient credits");
                    }

                    // Track deduction (will be applied after SMS sent successfully)
                    $deductedCredits[$userId] = $currentDeducted + 1;
                }

                // Normalize phone number
                $phoneNumber = $item->getPhoneNumber();
                $normalizedNumber = $this->normalizePhoneNumber($phoneNumber);

                // Send SMS
                $result = $this->orangeAPIClient->sendSMS($normalizedNumber, $item->getMessage());

                // Extract message ID if available
                $messageId = null;
                if (isset($result['outboundSMSMessageRequest']['resourceURL'])) {
                    $resourceUrl = $result['outboundSMSMessageRequest']['resourceURL'];
                    $messageId = substr($resourceUrl, strrpos($resourceUrl, '/') + 1);
                }

                // Update queue entry
                $item->markAsSent($messageId);
                $this->smsQueueRepository->save($item);
                $sent++;

                // Deduct credits if sending succeeded
                if ($userId !== null) {
                    $user = $this->authService->getUserById($userId);
                    if ($user !== null) {
                        $user->setSmsCredit($user->getSmsCredit() - 1);
                        if ($user->getSmsCredit() === 0) {
                            $user->setSmsLimit(0);
                        }
                        $this->authService->updateUser($user);
                    }
                }

                $this->logger->info('SMS sent successfully', [
                    'queueId' => $item->getId(),
                    'phoneNumber' => $phoneNumber,
                    'messageId' => $messageId
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Failed to send SMS', [
                    'queueId' => $item->getId(),
                    'phoneNumber' => $item->getPhoneNumber(),
                    'error' => $e->getMessage()
                ]);

                // Handle failure
                $item->markAsFailed($e->getMessage(), $this->maxAttempts);
                $this->smsQueueRepository->save($item);
                $failed++;
            }
        }

        $this->logger->info('Batch processing complete', [
            'sent' => $sent,
            'failed' => $failed,
            'total' => count($pendingItems)
        ]);

        return [
            'sent' => $sent,
            'failed' => $failed,
            'total' => count($pendingItems)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getBatchStatus(string $batchId): array
    {
        $this->logger->debug('Getting batch status', ['batchId' => $batchId]);

        try {
            $batchItems = $this->smsQueueRepository->findByBatchId($batchId);
            
            if (empty($batchItems)) {
                return [
                    'batchId' => $batchId,
                    'total' => 0,
                    'sent' => 0,
                    'failed' => 0,
                    'pending' => 0,
                    'processing' => 0,
                    'cancelled' => 0,
                    'status' => 'NOT_FOUND'
                ];
            }

            $total = count($batchItems);
            $sent = 0;
            $failed = 0;
            $pending = 0;
            $processing = 0;
            $cancelled = 0;

            foreach ($batchItems as $item) {
                switch ($item->getStatus()) {
                    case SMSQueue::STATUS_SENT:
                        $sent++;
                        break;
                    case SMSQueue::STATUS_FAILED:
                        $failed++;
                        break;
                    case SMSQueue::STATUS_PENDING:
                        $pending++;
                        break;
                    case SMSQueue::STATUS_PROCESSING:
                        $processing++;
                        break;
                    case SMSQueue::STATUS_CANCELLED:
                        $cancelled++;
                        break;
                }
            }

            // Determine overall status
            $status = 'PENDING';
            if ($total === $sent) {
                $status = 'COMPLETED';
            } elseif ($total === $cancelled) {
                $status = 'CANCELLED';
            } elseif ($total === $failed) {
                $status = 'FAILED';
            } elseif ($sent > 0 || $failed > 0 || $processing > 0) {
                $status = 'IN_PROGRESS';
            }

            return [
                'batchId' => $batchId,
                'total' => $total,
                'sent' => $sent,
                'failed' => $failed,
                'pending' => $pending,
                'processing' => $processing,
                'cancelled' => $cancelled,
                'status' => $status
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error getting batch status', [
                'batchId' => $batchId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cancelBatch(string $batchId, ?string $reason = null): int
    {
        $this->logger->info('Cancelling batch', [
            'batchId' => $batchId,
            'reason' => $reason ?? 'User requested'
        ]);

        try {
            $cancelledCount = $this->smsQueueRepository->cancelPendingByBatchId($batchId, $reason);
            $this->logger->info('Batch cancelled', [
                'batchId' => $batchId,
                'cancelledCount' => $cancelledCount
            ]);
            
            return $cancelledCount;
        } catch (\Exception $e) {
            $this->logger->error('Error cancelling batch', [
                'batchId' => $batchId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueStats(): array
    {
        $this->logger->debug('Getting queue statistics');

        try {
            $pending = $this->smsQueueRepository->countByStatus(SMSQueue::STATUS_PENDING);
            $processing = $this->smsQueueRepository->countByStatus(SMSQueue::STATUS_PROCESSING);
            $sent = $this->smsQueueRepository->countByStatus(SMSQueue::STATUS_SENT);
            $failed = $this->smsQueueRepository->countByStatus(SMSQueue::STATUS_FAILED);
            $cancelled = $this->smsQueueRepository->countByStatus(SMSQueue::STATUS_CANCELLED);
            $total = $pending + $processing + $sent + $failed + $cancelled;

            return [
                'total' => $total,
                'pending' => $pending,
                'processing' => $processing,
                'sent' => $sent,
                'failed' => $failed,
                'cancelled' => $cancelled
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error getting queue statistics', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'total' => 0,
                'pending' => 0,
                'processing' => 0,
                'sent' => 0,
                'failed' => 0,
                'cancelled' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cleanupOldEntries(int $daysToKeep = 30): int
    {
        $this->logger->info('Cleaning up old queue entries', ['daysToKeep' => $daysToKeep]);

        try {
            $threshold = new \DateTime("-{$daysToKeep} days");
            $deletedCount = $this->smsQueueRepository->deleteOldEntries($threshold);
            
            $this->logger->info('Old queue entries cleaned up', [
                'deletedCount' => $deletedCount,
                'olderThan' => $threshold->format('Y-m-d H:i:s')
            ]);
            
            return $deletedCount;
        } catch (\Exception $e) {
            $this->logger->error('Error cleaning up old queue entries', [
                'error' => $e->getMessage(),
                'daysToKeep' => $daysToKeep
            ]);
            
            return 0;
        }
    }

    /**
     * Normalize a phone number for the Orange API
     *
     * @param string $number Phone number
     * @return string Normalized phone number
     */
    private function normalizePhoneNumber(string $number): string
    {
        $number = preg_replace('/[^0-9+]/', '', $number);
        
        if (strpos($number, '+') === 0) {
            // Already international format
        } elseif (strpos($number, '00') === 0) {
            // Starts with 00, assume country code follows
            $number = '+' . substr($number, 2);
        } elseif (strpos($number, '0') === 0 && strlen($number) > 5) {
            // Assume local Côte d'Ivoire number if starts with 0
            $number = '+225' . $number;
        } else {
            // Cannot determine format, maybe add default country code or throw error?
            if (ctype_digit($number) && strlen($number) > 9) {
                // Add default country code
                $number = '+225' . $number;
            }
        }

        // Ensure 'tel:' prefix
        if (strpos($number, 'tel:') !== 0) {
            return 'tel:' . $number;
        }
        
        return $number;
    }
}