<?php

namespace App\Repositories\Interfaces\WhatsApp;

use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\Entities\User;
use App\Entities\Contact;
use Doctrine\DBAL\LockMode;

/**
 * Interface pour le repository de l'historique des messages WhatsApp
 */
interface WhatsAppMessageHistoryRepositoryInterface
{
    /**
     * Sauvegarder un message dans l'historique
     * 
     * @param mixed $message
     * @return mixed
     */
    public function save($message);

    /**
     * Trouver un message par son WABA ID
     * 
     * @param string $wabaMessageId
     * @return WhatsAppMessageHistory|null
     */
    public function findByWabaMessageId(string $wabaMessageId): ?WhatsAppMessageHistory;

    /**
     * Obtenir l'historique des messages pour un utilisateur
     * 
     * @param User $user
     * @param int $limit
     * @param int $offset
     * @return WhatsAppMessageHistory[]
     */
    public function findByUser(User $user, int $limit = 50, int $offset = 0): array;

    /**
     * Obtenir l'historique des messages pour un contact
     * 
     * @param Contact $contact
     * @param int $limit
     * @param int $offset
     * @return WhatsAppMessageHistory[]
     */
    public function findByContact(Contact $contact, int $limit = 50, int $offset = 0): array;

    /**
     * Obtenir l'historique des messages par numéro de téléphone
     * 
     * @param string $phoneNumber
     * @param User|null $user
     * @param int $limit
     * @param int $offset
     * @return WhatsAppMessageHistory[]
     */
    public function findByPhoneNumber(string $phoneNumber, ?User $user = null, int $limit = 50, int $offset = 0): array;

    /**
     * Obtenir les messages en attente de statut
     * 
     * @param string $status
     * @param int $limit
     * @return WhatsAppMessageHistory[]
     */
    public function findByStatus(string $status, int $limit = 100): array;

    /**
     * Mettre à jour le statut d'un message
     * 
     * @param string $wabaMessageId
     * @param string $status
     * @param array|null $errorData
     * @return bool
     */
    public function updateStatus(string $wabaMessageId, string $status, ?array $errorData = null): bool;

    /**
     * Compter les messages par utilisateur
     * 
     * @param User $user
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @return int
     */
    public function countByUser(User $user, ?\DateTime $startDate = null, ?\DateTime $endDate = null): int;

    /**
     * Obtenir les statistiques de messages
     * 
     * @param User $user
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @return array
     */
    public function getStatistics(User $user, ?\DateTime $startDate = null, ?\DateTime $endDate = null): array;

    /**
     * Trouver un message par son ID.
     *
     * @param mixed $id
     * @param LockMode|int|null $lockMode
     * @param int|null $lockVersion
     * @return object|null
     */
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object;

    /**
     * Trouver des messages par un ensemble de critères.
     *
     * @param array $criteria
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return WhatsAppMessageHistory[]
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /**
     * Compter les messages par un ensemble de critères.
     *
     * @param array $criteria
     * @return int
     */
    public function count(array $criteria): int;

    /**
     * Trouver un message par un ensemble de critères, retournant le premier résultat.
     *
     * @param array $criteria
     * @param array|null $orderBy
     * @return object|null
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    /**
     * Trouver des messages avec filtrage par plage de dates
     *
     * @param array $criteria Critères de recherche
     * @param array $dateFilters Filtres de date (startDate, endDate)
     * @param array|null $orderBy Ordre de tri
     * @param int|null $limit Limite de résultats
     * @param int|null $offset Offset pour la pagination
     * @return WhatsAppMessageHistory[]
     */
    public function findByWithDateRange(array $criteria, array $dateFilters, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /**
     * Compter les messages avec filtrage par plage de dates
     *
     * @param array $criteria Critères de recherche
     * @param array $dateFilters Filtres de date (startDate, endDate)
     * @return int
     */
    public function countWithDateRange(array $criteria, array $dateFilters): int;

    /**
     * Trouver des messages avec filtres avancés (date et téléphone)
     * 
     * @param array $criteria Critères de recherche
     * @param array $dateFilters Filtres de date
     * @param string|null $phoneFilter Filtre de téléphone (recherche partielle)
     * @param array|null $orderBy Ordre de tri
     * @param int|null $limit Limite de résultats
     * @param int|null $offset Décalage
     * @return array
     */
    public function findByWithFilters(array $criteria, array $dateFilters = [], ?string $phoneFilter = null, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /**
     * Compter les messages avec filtres avancés (date et téléphone)
     * 
     * @param array $criteria Critères de recherche
     * @param array $dateFilters Filtres de date
     * @param string|null $phoneFilter Filtre de téléphone (recherche partielle)
     * @return int
     */
    public function countWithFilters(array $criteria, array $dateFilters = [], ?string $phoneFilter = null): int;
}
