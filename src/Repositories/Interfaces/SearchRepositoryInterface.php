<?php

namespace App\Repositories\Interfaces;

/**
 * Interface SearchRepositoryInterface
 * 
 * Interface pour les opérations de recherche dans un repository.
 * Suit le principe d'Interface Segregation (ISP) de SOLID en séparant
 * les opérations de recherche des autres opérations.
 */
interface SearchRepositoryInterface
{
    /**
     * Recherche des entités selon des critères spécifiques
     * 
     * @param array $criteria Les critères de recherche
     * @param array|null $orderBy Tri des résultats
     * @param int|null $limit Limite le nombre d'entités retournées
     * @param int|null $offset Décalage pour la pagination
     * @return array Les entités trouvées
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /**
     * Trouve une seule entité selon des critères spécifiques
     * 
     * @param array $criteria Les critères de recherche
     * @param array|null $orderBy Tri des résultats
     * @return mixed|null L'entité trouvée ou null si non trouvée
     */
    public function findOneBy(array $criteria, ?array $orderBy = null);

    /**
     * Recherche des entités par une requête textuelle
     * 
     * @param string $query La requête de recherche
     * @param array|null $fields Les champs à rechercher
     * @param int|null $limit Limite le nombre d'entités retournées
     * @param int|null $offset Décalage pour la pagination
     * @return array Les entités trouvées
     */
    public function search(string $query, ?array $fields = null, ?int $limit = null, ?int $offset = null): array;
}
