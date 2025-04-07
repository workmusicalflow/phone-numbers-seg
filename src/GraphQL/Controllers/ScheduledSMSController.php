<?php

namespace App\GraphQL\Controllers;

use App\Models\ScheduledSMS;
use App\Models\ScheduledSMSLog;
use App\Repositories\ScheduledSMSRepository;
use App\Repositories\ScheduledSMSLogRepository;
use App\Repositories\SenderNameRepository;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Right;
use App\Models\User;
use Exception;
use Psr\Log\LoggerInterface;
use DateTime;

class ScheduledSMSController
{
    private ScheduledSMSRepository $scheduledSMSRepository;
    private ScheduledSMSLogRepository $scheduledSMSLogRepository;
    private SenderNameRepository $senderNameRepository;
    private LoggerInterface $logger;

    public function __construct(
        ScheduledSMSRepository $scheduledSMSRepository,
        ScheduledSMSLogRepository $scheduledSMSLogRepository,
        SenderNameRepository $senderNameRepository,
        LoggerInterface $logger
    ) {
        $this->scheduledSMSRepository = $scheduledSMSRepository;
        $this->scheduledSMSLogRepository = $scheduledSMSLogRepository;
        $this->senderNameRepository = $senderNameRepository;
        $this->logger = $logger;
    }

    /**
     * @Query
     * @Logged
     * @Right("ROLE_USER")
     */
    public function scheduledSMS(?int $limit = 100, ?int $offset = 0, User $user): array
    {
        try {
            return $this->scheduledSMSRepository->findByUserId($user->getId(), $limit, $offset);
        } catch (Exception $e) {
            $this->logger->error('Error fetching scheduled SMS: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @Query
     * @Logged
     * @Right("ROLE_USER")
     */
    public function scheduledSMSCount(User $user): int
    {
        try {
            return $this->scheduledSMSRepository->count($user->getId());
        } catch (Exception $e) {
            $this->logger->error('Error counting scheduled SMS: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * @Query
     * @Logged
     * @Right("ROLE_USER")
     */
    public function scheduledSMSById(int $id, User $user): ?ScheduledSMS
    {
        try {
            $scheduledSMS = $this->scheduledSMSRepository->findById($id);

            // Vérifier que le SMS planifié appartient à l'utilisateur
            if ($scheduledSMS && $scheduledSMS->getUserId() === $user->getId()) {
                return $scheduledSMS;
            }

            return null;
        } catch (Exception $e) {
            $this->logger->error('Error fetching scheduled SMS by ID: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @Query
     * @Logged
     * @Right("ROLE_USER")
     */
    public function searchScheduledSMS(string $query, ?int $limit = 100, ?int $offset = 0, User $user): array
    {
        try {
            return $this->scheduledSMSRepository->searchByUserId($query, $user->getId(), $limit, $offset);
        } catch (Exception $e) {
            $this->logger->error('Error searching scheduled SMS: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @Query
     * @Logged
     * @Right("ROLE_USER")
     */
    public function scheduledSMSLogs(int $scheduledSmsId, ?int $limit = 100, ?int $offset = 0, User $user): array
    {
        try {
            // Vérifier que le SMS planifié appartient à l'utilisateur
            $scheduledSMS = $this->scheduledSMSRepository->findById($scheduledSmsId);
            if (!$scheduledSMS || $scheduledSMS->getUserId() !== $user->getId()) {
                return [];
            }

            return $this->scheduledSMSLogRepository->findByScheduledSmsId($scheduledSmsId, $limit, $offset);
        } catch (Exception $e) {
            $this->logger->error('Error fetching scheduled SMS logs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @Query
     * @Logged
     * @Right("ROLE_USER")
     */
    public function scheduledSMSLogsCount(int $scheduledSmsId, User $user): int
    {
        try {
            // Vérifier que le SMS planifié appartient à l'utilisateur
            $scheduledSMS = $this->scheduledSMSRepository->findById($scheduledSmsId);
            if (!$scheduledSMS || $scheduledSMS->getUserId() !== $user->getId()) {
                return 0;
            }

            return $this->scheduledSMSLogRepository->count($scheduledSmsId);
        } catch (Exception $e) {
            $this->logger->error('Error counting scheduled SMS logs: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * @Mutation
     * @Logged
     * @Right("ROLE_USER")
     */
    public function createScheduledSMS(
        string $name,
        string $message,
        int $senderNameId,
        string $scheduledDate,
        string $recipientsType,
        string $recipientsData,
        bool $isRecurring = false,
        ?string $recurrencePattern = null,
        ?string $recurrenceConfig = null,
        User $user
    ): ?ScheduledSMS {
        try {
            // Vérifier que l'expéditeur appartient à l'utilisateur
            $senderName = $this->senderNameRepository->findById($senderNameId);
            if (!$senderName || $senderName->getUserId() !== $user->getId()) {
                throw new Exception("Sender name not found or not owned by user");
            }

            // Vérifier que la date planifiée est dans le futur
            $now = new DateTime();
            $scheduledDateTime = new DateTime($scheduledDate);
            if ($scheduledDateTime <= $now) {
                throw new Exception("Scheduled date must be in the future");
            }

            // Créer le SMS planifié
            $scheduledSMS = new ScheduledSMS(
                0, // ID sera généré par la base de données
                $user->getId(),
                $name,
                $message,
                $senderNameId,
                $scheduledDate,
                'pending',
                $isRecurring,
                $recurrencePattern,
                $recurrenceConfig,
                $recipientsType,
                $recipientsData
            );

            return $this->scheduledSMSRepository->create($scheduledSMS);
        } catch (Exception $e) {
            $this->logger->error('Error creating scheduled SMS: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @Mutation
     * @Logged
     * @Right("ROLE_USER")
     */
    public function updateScheduledSMS(
        int $id,
        string $name,
        string $message,
        int $senderNameId,
        string $scheduledDate,
        string $recipientsType,
        string $recipientsData,
        bool $isRecurring = false,
        ?string $recurrencePattern = null,
        ?string $recurrenceConfig = null,
        User $user
    ): ?ScheduledSMS {
        try {
            // Récupérer le SMS planifié existant
            $existingScheduledSMS = $this->scheduledSMSRepository->findById($id);

            // Vérifier que le SMS planifié existe et appartient à l'utilisateur
            if (!$existingScheduledSMS || $existingScheduledSMS->getUserId() !== $user->getId()) {
                throw new Exception("Scheduled SMS not found or not owned by user");
            }

            // Vérifier que le SMS planifié n'a pas déjà été envoyé
            if ($existingScheduledSMS->getStatus() === 'sent') {
                throw new Exception("Cannot update a sent scheduled SMS");
            }

            // Vérifier que l'expéditeur appartient à l'utilisateur
            $senderName = $this->senderNameRepository->findById($senderNameId);
            if (!$senderName || $senderName->getUserId() !== $user->getId()) {
                throw new Exception("Sender name not found or not owned by user");
            }

            // Vérifier que la date planifiée est dans le futur
            $now = new DateTime();
            $scheduledDateTime = new DateTime($scheduledDate);
            if ($scheduledDateTime <= $now) {
                throw new Exception("Scheduled date must be in the future");
            }

            // Mettre à jour les propriétés du SMS planifié
            $existingScheduledSMS->setName($name);
            $existingScheduledSMS->setMessage($message);
            $existingScheduledSMS->setSenderNameId($senderNameId);
            $existingScheduledSMS->setScheduledDate($scheduledDate);
            $existingScheduledSMS->setRecipientsType($recipientsType);
            $existingScheduledSMS->setRecipientsData($recipientsData);
            $existingScheduledSMS->setIsRecurring($isRecurring);
            $existingScheduledSMS->setRecurrencePattern($recurrencePattern);
            $existingScheduledSMS->setRecurrenceConfig($recurrenceConfig);

            // Si c'est récurrent, calculer la prochaine date d'exécution
            if ($isRecurring && $recurrencePattern && $recurrenceConfig) {
                $nextRunAt = $existingScheduledSMS->calculateNextRunDate();
                if ($nextRunAt) {
                    $existingScheduledSMS->setNextRunAt($nextRunAt);
                }
            } else {
                $existingScheduledSMS->setNextRunAt($scheduledDate);
            }

            return $this->scheduledSMSRepository->update($existingScheduledSMS);
        } catch (Exception $e) {
            $this->logger->error('Error updating scheduled SMS: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @Mutation
     * @Logged
     * @Right("ROLE_USER")
     */
    public function cancelScheduledSMS(int $id, User $user): bool
    {
        try {
            // Récupérer le SMS planifié existant
            $existingScheduledSMS = $this->scheduledSMSRepository->findById($id);

            // Vérifier que le SMS planifié existe et appartient à l'utilisateur
            if (!$existingScheduledSMS || $existingScheduledSMS->getUserId() !== $user->getId()) {
                throw new Exception("Scheduled SMS not found or not owned by user");
            }

            // Vérifier que le SMS planifié n'a pas déjà été envoyé
            if ($existingScheduledSMS->getStatus() === 'sent') {
                throw new Exception("Cannot cancel a sent scheduled SMS");
            }

            // Mettre à jour le statut du SMS planifié
            return $this->scheduledSMSRepository->updateStatus($id, 'cancelled');
        } catch (Exception $e) {
            $this->logger->error('Error cancelling scheduled SMS: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @Mutation
     * @Logged
     * @Right("ROLE_USER")
     */
    public function deleteScheduledSMS(int $id, User $user): bool
    {
        try {
            // Récupérer le SMS planifié existant
            $existingScheduledSMS = $this->scheduledSMSRepository->findById($id);

            // Vérifier que le SMS planifié existe et appartient à l'utilisateur
            if (!$existingScheduledSMS || $existingScheduledSMS->getUserId() !== $user->getId()) {
                throw new Exception("Scheduled SMS not found or not owned by user");
            }

            return $this->scheduledSMSRepository->deleteById($id);
        } catch (Exception $e) {
            $this->logger->error('Error deleting scheduled SMS: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @Query
     * @Logged
     * @Right("ROLE_ADMIN")
     */
    public function allScheduledSMS(?int $limit = 100, ?int $offset = 0): array
    {
        try {
            return $this->scheduledSMSRepository->findAll($limit, $offset);
        } catch (Exception $e) {
            $this->logger->error('Error fetching all scheduled SMS: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @Query
     * @Logged
     * @Right("ROLE_ADMIN")
     */
    public function allScheduledSMSCount(): int
    {
        try {
            return $this->scheduledSMSRepository->count();
        } catch (Exception $e) {
            $this->logger->error('Error counting all scheduled SMS: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * @Query
     * @Logged
     * @Right("ROLE_ADMIN")
     */
    public function userScheduledSMS(int $userId, ?int $limit = 100, ?int $offset = 0): array
    {
        try {
            return $this->scheduledSMSRepository->findByUserId($userId, $limit, $offset);
        } catch (Exception $e) {
            $this->logger->error('Error fetching user scheduled SMS: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @Query
     * @Logged
     * @Right("ROLE_ADMIN")
     */
    public function userScheduledSMSCount(int $userId): int
    {
        try {
            return $this->scheduledSMSRepository->count($userId);
        } catch (Exception $e) {
            $this->logger->error('Error counting user scheduled SMS: ' . $e->getMessage());
            return 0;
        }
    }
}
