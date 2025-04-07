<?php

namespace App\Repositories\Interfaces;

/**
 * Interface ReadRepositoryInterface
 * 
 * Interface pour les opérations de lecture dans un repository.
 * Suit le principe d'Interface Segregation (ISP) de SOLID en séparant
 * les opérations de lecture des autres opérations.
 */
interface ReadRepositoryInterface
{
    /**
     * Trouve une entité par son ID
     * 
     * @param int $id L'ID de l'entité à trouver
     * @return mixed|null L'entité trouvée ou null si non trouvée
     */
    public function findById(int $id);

    /**
     * Trouve toutes les entités
     * 
     * @param int|null $limit Limite le nombre d'entités retournées
     * @param int|null $offset Décalage pour la pagination
     * @return array Les entités trouvées
     */
    public function findAll(?int $limit = null, ?int $offset = null): array;

    /**
     * Compte le nombre total d'entités
     * 
     * @return int Le nombre total d'entités
     */
    public function count(): int;
}
