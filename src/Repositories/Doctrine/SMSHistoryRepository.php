<?php

namespace App\Repositories\Doctrine;

use App\Entities\SMSHistory;
use App\Repositories\Interfaces\SMSHistoryRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

/**
 * SMSHistory repository using Doctrine ORM
 * 
 * This repository provides methods to access and manipulate SMSHistory entities.
 */
class SMSHistoryRepository extends BaseRepository implements SMSHistoryRepositoryInterface
{
    /**
     * Constructor
     * 
     * @param EntityManagerInterface $entityManager The entity manager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, SMSHistory::class);
    }

    /**
     * Find an entity by its ID
     * 
     * @param mixed $id The entity ID
     * @param mixed $lockMode The lock mode
     * @param mixed $lockVersion The lock version
     * @return object|null The entity or null if not found
     */
    public function find($id, $lockMode = null, $lockVersion = null): ?object
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    /**
     * Find all SMS history records
     * 
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The SMS history records
     */
    public function findAll(?int $limit = null, ?int $offset = null): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('s')
            ->from($this->getClassName(), 's')
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Count SMS history records by multiple criteria
     * 
     * @param array $criteria Associative array of criteria (e.g., ['userId' => 1, 'status' => 'SENT', 'search' => '123', 'segmentId' => 5])
     * @return int The number of matching SMS history records
     */
    public function countByCriteria(array $criteria): int
    {
        $queryBuilder = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)'); // Select count instead of the entity

        $paramIndex = 1; // Index for unique parameter names

        foreach ($criteria as $field => $value) {
            if ($value === null) {
                continue; // Skip null criteria values
            }

            $paramName = $field . $paramIndex++;

            switch ($field) {
                case 'userId':
                    $queryBuilder->andWhere('s.userId = :' . $paramName)
                        ->setParameter($paramName, $value);
                    break;
                case 'status':
                    $queryBuilder->andWhere('s.status = :' . $paramName)
                        ->setParameter($paramName, $value);
                    break;
                case 'segmentId':
                    $queryBuilder->andWhere('s.segmentId = :' . $paramName)
                        ->setParameter($paramName, $value);
                    break;
                case 'search': // Assuming search targets the phone number
                    $queryBuilder->andWhere('s.phoneNumber LIKE :' . $paramName)
                        ->setParameter($paramName, '%' . $value . '%');
                    break;
                    // Add more cases here if other criteria are needed in the future
            }
        }

        // Return the single scalar result (the count)
        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * Find SMS history records by phone number
     * 
     * @param string $phoneNumber The phone number
     * @param int $limit Maximum number of entities to return
     * @param int $offset Number of entities to skip
     * @return array The SMS history records
     */
    public function findByPhoneNumber(string $phoneNumber, int $limit = 100, int $offset = 0): array
    {
        // Check if the number is in international or local format
        $isInternational = strpos($phoneNumber, '+225') === 0;
        $isLocal = !$isInternational && (strlen($phoneNumber) === 10 || strlen($phoneNumber) === 8);

        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('s')
            ->from($this->getClassName(), 's')
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($isInternational) {
            // International format: search for both international and local formats
            $localNumber = $this->convertToLocalFormat($phoneNumber);
            $queryBuilder->where('s.phoneNumber = :phoneNumber OR s.phoneNumber = :localNumber')
                ->setParameter('phoneNumber', $phoneNumber)
                ->setParameter('localNumber', $localNumber);
        } elseif ($isLocal) {
            // Local format: search for both local and international formats
            $internationalNumber = $this->convertToInternationalFormat($phoneNumber);
            $queryBuilder->where('s.phoneNumber = :phoneNumber OR s.phoneNumber = :internationalNumber')
                ->setParameter('phoneNumber', $phoneNumber)
                ->setParameter('internationalNumber', $internationalNumber);
        } else {
            // Exact search
            $queryBuilder->where('s.phoneNumber = :phoneNumber')
                ->setParameter('phoneNumber', $phoneNumber);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Find SMS history records by phone number ID
     * 
     * @param int $phoneNumberId The phone number ID
     * @param int $limit Maximum number of entities to return
     * @param int $offset Number of entities to skip
     * @return array The SMS history records
     */
    public function findByPhoneNumberId(int $phoneNumberId, int $limit = 100, int $offset = 0): array
    {
        return $this->findBy(
            ['phoneNumberId' => $phoneNumberId],
            ['createdAt' => 'DESC'],
            $limit,
            $offset
        );
    }

    /**
     * Find SMS history records by segment ID
     * 
     * @param int $segmentId The segment ID
     * @param int $limit Maximum number of entities to return
     * @param int $offset Number of entities to skip
     * @return array The SMS history records
     */
    public function findBySegmentId(int $segmentId, int $limit = 100, int $offset = 0): array
    {
        return $this->findBy(
            ['segmentId' => $segmentId],
            ['createdAt' => 'DESC'],
            $limit,
            $offset
        );
    }

    /**
     * Find SMS history records by status
     * 
     * @param string $status The status
     * @param int $limit Maximum number of entities to return
     * @param int $offset Number of entities to skip
     * @return array The SMS history records
     */
    public function findByStatus(string $status, int $limit = 100, int $offset = 0): array
    {
        return $this->findBy(
            ['status' => $status],
            ['createdAt' => 'DESC'],
            $limit,
            $offset
        );
    }

    /**
     * Find SMS history records by user ID
     * 
     * @param int $userId The user ID
     * @param int $limit Maximum number of entities to return
     * @param int $offset Number of entities to skip
     * @return array The SMS history records
     */
    public function findByUserId(int $userId, int $limit = 100, int $offset = 0): array
    {
        return $this->findBy(
            ['userId' => $userId],
            ['createdAt' => 'DESC'],
            $limit,
            $offset
        );
    }

    /**
     * Count all SMS history records
     * 
     * @return int The number of SMS history records
     */
    public function countAll(): int
    {
        return $this->count();
    }

    /**
     * Count SMS history records by date
     * 
     * @param string $date The date in Y-m-d format
     * @return int The number of SMS history records
     */
    public function countByDate(string $date): int
    {
        $startDate = new \DateTime($date . ' 00:00:00');
        $endDate = new \DateTime($date . ' 23:59:59');

        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('COUNT(s.id)')
            ->from($this->getClassName(), 's')
            ->where('s.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * Count SMS history records by user ID
     * 
     * @param int $userId The user ID
     * @return int The number of SMS history records
     */
    public function countByUserId(int $userId): int
    {
        return $this->count(['userId' => $userId]);
    }

    /**
     * Get daily counts for a date range
     * 
     * @param string $startDate The start date in Y-m-d format
     * @param string $endDate The end date in Y-m-d format
     * @return array The daily counts
     */
    public function getDailyCountsForDateRange(string $startDate, string $endDate): array
    {
        $startDateTime = new \DateTime($startDate . ' 00:00:00');
        $endDateTime = new \DateTime($endDate . ' 23:59:59');

        // Use native SQL for this query since Doctrine DQL doesn't support DATE() function
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT DATE(created_at) as date, COUNT(id) as count
            FROM sms_history
            WHERE created_at BETWEEN :startDate AND :endDate
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('startDate', $startDateTime->format('Y-m-d H:i:s'));
        $stmt->bindValue('endDate', $endDateTime->format('Y-m-d H:i:s'));
        $result = $stmt->executeQuery()->fetchAllAssociative();

        return $result;
    }

    /**
     * Create a new SMS history record
     * 
     * @param string $phoneNumber The phone number
     * @param string $message The message
     * @param string $status The status
     * @param string|null $messageId The message ID
     * @param string|null $errorMessage The error message
     * @param string $senderAddress The sender address
     * @param string $senderName The sender name
     * @param int|null $segmentId The segment ID
     * @param int|null $phoneNumberId The phone number ID
     * @param int|null $userId The user ID
     * @return SMSHistory The created SMS history record
     */
    public function create(
        string $phoneNumber,
        string $message,
        string $status,
        ?string $messageId = null,
        ?string $errorMessage = null,
        string $senderAddress = 'tel:+2250595016840',
        string $senderName = 'Qualitas CI',
        ?int $segmentId = null,
        ?int $phoneNumberId = null,
        ?int $userId = null
    ): SMSHistory {
        $smsHistory = new SMSHistory();
        $smsHistory->setPhoneNumber($phoneNumber);
        $smsHistory->setMessage($message);
        $smsHistory->setStatus($status);
        $smsHistory->setMessageId($messageId);
        $smsHistory->setErrorMessage($errorMessage);
        $smsHistory->setSenderAddress($senderAddress);
        $smsHistory->setSenderName($senderName);
        $smsHistory->setSegmentId($segmentId);
        $smsHistory->setPhoneNumberId($phoneNumberId);
        $smsHistory->setUserId($userId);

        return $this->save($smsHistory);
    }

    /**
     * Update segment ID for phone numbers
     * 
     * @param array $phoneNumbers The phone numbers
     * @param int $segmentId The segment ID
     * @return bool True if successful
     */
    public function updateSegmentIdForPhoneNumbers(array $phoneNumbers, int $segmentId): bool
    {
        if (empty($phoneNumbers)) {
            return false;
        }

        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->update($this->getClassName(), 's')
            ->set('s.segmentId', ':segmentId')
            ->where('s.phoneNumber IN (:phoneNumbers)')
            ->andWhere('s.segmentId IS NULL')
            ->setParameter('segmentId', $segmentId)
            ->setParameter('phoneNumbers', $phoneNumbers);

        $result = $queryBuilder->getQuery()->execute();

        return $result > 0;
    }

    /**
     * Convert a phone number from international to local format
     * 
     * @param string $phoneNumber The phone number
     * @return string The converted phone number
     */
    private function convertToLocalFormat(string $phoneNumber): string
    {
        // If the number starts with +225, convert it to local format
        if (strpos($phoneNumber, '+225') === 0) {
            $localNumber = substr($phoneNumber, 4); // Remove the +225

            // If the number starts with 0, leave it as is
            if (strpos($localNumber, '0') === 0) {
                return $localNumber;
            }

            // Otherwise, add a 0 at the beginning
            return '0' . $localNumber;
        }

        return $phoneNumber;
    }

    /**
     * Find SMS history record by message ID
     * 
     * @param string $messageId The message ID
     * @return SMSHistory|null The SMS history record or null if not found
     */
    public function findByMessageId(string $messageId): ?SMSHistory
    {
        return $this->findOneBy(['messageId' => $messageId]);
    }

    /**
     * Delete all SMS history records for a user
     * 
     * @param int $userId The user ID
     * @return bool True if successful
     */
    public function removeAllByUserId(int $userId): bool
    {
        try {
            // Vérifier d'abord s'il y a des entrées à supprimer
            $count = $this->countByUserId($userId);

            // Si aucune entrée, considérer l'opération comme réussie
            if ($count === 0) {
                return true;
            }

            $queryBuilder = $this->getEntityManager()->createQueryBuilder();
            $queryBuilder->delete($this->getClassName(), 's')
                ->where('s.userId = :userId')
                ->setParameter('userId', $userId);

            $result = $queryBuilder->getQuery()->execute();

            return $result > 0;
        } catch (Exception $e) {
            // Log the error
            error_log('Error deleting SMS history for user ' . $userId . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Convert a phone number from local to international format
     * 
     * @param string $phoneNumber The phone number
     * @return string The converted phone number
     */
    private function convertToInternationalFormat(string $phoneNumber): string
    {
        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);

        // If the number starts with 0 and has 10 digits, it's a local number
        if (strlen($cleaned) === 10 && substr($cleaned, 0, 1) === '0') {
            // Convert to international format (Côte d'Ivoire +225)
            return '+225' . $cleaned;
        }

        // If the number has 8 digits, it's a local number without the 0
        if (strlen($cleaned) === 8) {
            // Convert to international format (Côte d'Ivoire +225)
            return '+2250' . $cleaned;
        }

        return $phoneNumber;
    }

    /**
     * Find SMS history records by multiple criteria
     * 
     * @param array $criteria Associative array of criteria (e.g., ['userId' => 1, 'status' => 'SENT', 'search' => '123', 'segmentId' => 5])
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The SMS history records
     */
    public function findByCriteria(array $criteria, ?int $limit = 100, ?int $offset = 0): array
    {
        $queryBuilder = $this->createQueryBuilder('s'); // 's' is the alias for SMSHistory

        $paramIndex = 1; // Index for unique parameter names

        foreach ($criteria as $field => $value) {
            if ($value === null) {
                continue; // Skip null criteria values
            }

            $paramName = $field . $paramIndex++;

            switch ($field) {
                case 'userId':
                    $queryBuilder->andWhere('s.userId = :' . $paramName)
                        ->setParameter($paramName, $value);
                    break;
                case 'status':
                    $queryBuilder->andWhere('s.status = :' . $paramName)
                        ->setParameter($paramName, $value);
                    break;
                case 'segmentId':
                    $queryBuilder->andWhere('s.segmentId = :' . $paramName)
                        ->setParameter($paramName, $value);
                    break;
                case 'search': // Assuming search targets the phone number
                    $queryBuilder->andWhere('s.phoneNumber LIKE :' . $paramName)
                        ->setParameter($paramName, '%' . $value . '%');
                    break;
                    // Add more cases here if other criteria are needed in the future
            }
        }

        // Add default ordering
        $queryBuilder->orderBy('s.createdAt', 'DESC');

        // Apply limit and offset
        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }
        if ($offset !== null) {
            $queryBuilder->setFirstResult($offset);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Save multiple SMS history entities in a single transaction.
     *
     * @param SMSHistory[] $histories An array of SMSHistory entities to save.
     * @return void
     * @throws \Exception If there's an error during the bulk save operation.
     */
    public function saveBulk(array $histories): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->beginTransaction();
        try {
            foreach ($histories as $history) {
                if (!$history instanceof SMSHistory) {
                    throw new \InvalidArgumentException('Array must contain only SMSHistory objects.');
                }
                $entityManager->persist($history);
            }
            $entityManager->flush();
            $entityManager->commit();
        } catch (\Exception $e) {
            $entityManager->rollback();
            // Log the exception or handle it as needed
            error_log("Error during bulk save of SMS history: " . $e->getMessage());
            throw $e; // Re-throw the exception
        }
    }
}
