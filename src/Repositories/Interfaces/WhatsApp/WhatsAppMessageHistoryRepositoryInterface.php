<?php

namespace App\Repositories\Interfaces\WhatsApp;

use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\Entities\User;
use App\Entities\Contact;

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
     * @return WhatsAppMessageHistory|null
     */
    public function find(mixed $id): ?WhatsAppMessageHistory;

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
     * @return WhatsAppMessageHistory|null
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?WhatsAppMessageHistory;
}
