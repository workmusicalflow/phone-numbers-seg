<?php

namespace App\Repositories\Interfaces;

use App\Entities\SMSOrder;

/**
 * Interface pour le repository SMSOrder
 */
interface SMSOrderRepositoryInterface extends DoctrineRepositoryInterface
{
    /**
     * Find an entity by its ID
     * 
     * @param int $id The entity ID
     * @return object|null The entity or null if not found
     */
    public function find(int $id): ?object;

    /**
     * Find all SMS orders
     * 
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The SMS orders
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
     * Count all SMS orders
     * 
     * @return int The number of SMS orders
     */
    public function countAll(): int;

    /**
     * Trouve les commandes SMS par ID d'utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param int $limit Limite de résultats
     * @param int $offset Offset pour la pagination
     * @return array
     */
    public function findByUserId(int $userId, int $limit = 100, int $offset = 0): array;

    /**
     * Compte le nombre de commandes SMS pour un utilisateur spécifique
     * 
     * @param int $userId ID de l'utilisateur
     * @return int
     */
    public function countByUserId(int $userId): int;

    /**
     * Trouve les commandes SMS par statut
     *
     * @param string $status Statut des commandes (pending, completed)
     * @param int $limit Limite de résultats
     * @param int $offset Offset pour la pagination
     * @return array
     */
    public function findByStatus(string $status, int $limit = 100, int $offset = 0): array;

    /**
     * Compte le nombre de commandes SMS par statut
     * 
     * @param string $status Statut des commandes (pending, completed)
     * @return int
     */
    public function countByStatus(string $status): int;

    /**
     * Met à jour le statut d'une commande SMS
     * 
     * @param int $id ID de la commande
     * @param string $newStatus Nouveau statut
     * @return bool
     */
    public function updateStatus(int $id, string $newStatus): bool;

    /**
     * Crée une nouvelle commande SMS
     * 
     * @param int $userId ID de l'utilisateur
     * @param int $quantity Quantité de crédits SMS
     * @param string $status Statut de la commande
     * @return SMSOrder
     */
    public function create(int $userId, int $quantity, string $status = SMSOrder::STATUS_PENDING): SMSOrder;
}
