<?php

namespace App\Repositories\Interfaces;

use App\Entities\SMSHistory;

/**
 * Interface pour le repository SMSHistory
 */
interface SMSHistoryRepositoryInterface extends DoctrineRepositoryInterface
{
    /**
     * Find an entity by its ID
     * 
     * @param int $id The entity ID
     * @return object|null The entity or null if not found
     */
    public function find(int $id): ?object;

    /**
     * Find all SMS history records
     * 
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The SMS history records
     */
    public function findAll(?int $limit = null, ?int $offset = null): array;

    /**
     * Find entities by criteria
     * 
     * @param array $criteria The criteria
     * @param array|null $orderBy The order by
     * @param int|null $limit The limit
     * @param int|null $offset The offset
     * @return array The entities
     */
    public function findBy(array $criteria, array $orderBy = null, int $limit = null, int $offset = null): array;

    /**
     * Count all SMS history records
     * 
     * @return int The number of SMS history records
     */
    public function countAll(): int;

    /**
     * Compte le nombre de SMS envoyés à une date spécifique
     * 
     * @param string $date Date au format Y-m-d
     * @return int
     */
    public function countByDate(string $date): int;

    /**
     * Récupère les comptes quotidiens de SMS pour une plage de dates
     * 
     * @param string $startDate Date de début au format Y-m-d
     * @param string $endDate Date de fin au format Y-m-d
     * @return array Tableau associatif avec les dates et les comptes
     */
    public function getDailyCountsForDateRange(string $startDate, string $endDate): array;

    /**
     * Trouver les enregistrements d'historique SMS par ID d'utilisateur
     *
     * @param int $userId
     * @param int $limit Limite de résultats
     * @param int $offset Offset pour la pagination
     * @return array
     */
    public function findByUserId(int $userId, int $limit = 100, int $offset = 0): array;

    /**
     * Compte le nombre de SMS envoyés par un utilisateur spécifique
     * 
     * @param int $userId ID de l'utilisateur
     * @return int
     */
    public function countByUserId(int $userId): int;

    /**
     * Find SMS history records by phone number
     * 
     * @param string $phoneNumber The phone number
     * @param int $limit Maximum number of entities to return
     * @param int $offset Number of entities to skip
     * @return array The SMS history records
     */
    public function findByPhoneNumber(string $phoneNumber, int $limit = 100, int $offset = 0): array;

    /**
     * Find SMS history records by phone number ID
     * 
     * @param int $phoneNumberId The phone number ID
     * @param int $limit Maximum number of entities to return
     * @param int $offset Number of entities to skip
     * @return array The SMS history records
     */
    public function findByPhoneNumberId(int $phoneNumberId, int $limit = 100, int $offset = 0): array;

    /**
     * Find SMS history records by segment ID
     * 
     * @param int $segmentId The segment ID
     * @param int $limit Maximum number of entities to return
     * @param int $offset Number of entities to skip
     * @return array The SMS history records
     */
    public function findBySegmentId(int $segmentId, int $limit = 100, int $offset = 0): array;

    /**
     * Find SMS history records by status
     * 
     * @param string $status The status
     * @param int $limit Maximum number of entities to return
     * @param int $offset Number of entities to skip
     * @return array The SMS history records
     */
    public function findByStatus(string $status, int $limit = 100, int $offset = 0): array;

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
    ): SMSHistory;

    /**
     * Update segment ID for phone numbers
     * 
     * @param array $phoneNumbers The phone numbers
     * @param int $segmentId The segment ID
     * @return bool True if successful
     */
    public function updateSegmentIdForPhoneNumbers(array $phoneNumbers, int $segmentId): bool;
}
