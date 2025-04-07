<?php

namespace App\Repositories\Interfaces;

/**
 * Interface pour les repositories utilisés par le tableau de bord
 */
interface DashboardRepositoryInterface extends CountableRepositoryInterface
{
    /**
     * Trouve des entités selon des critères spécifiques
     * 
     * @param array $criteria Critères de recherche
     * @param array|null $orderBy Critères de tri
     * @param int|null $limit Nombre maximum d'entités à retourner
     * @param int|null $offset Décalage pour la pagination
     * @return array
     */
    public function findBy(array $criteria, array $orderBy = null, int $limit = null, int $offset = null): array;

    /**
     * Trouve une entité par son identifiant
     * 
     * @param int $id Identifiant de l'entité
     * @return object|null
     */
    public function find(int $id): ?object;

    /**
     * Trouve toutes les entités
     * 
     * @return array
     */
    public function findAll(): array;
}
