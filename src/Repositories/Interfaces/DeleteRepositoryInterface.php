<?php

namespace App\Repositories\Interfaces;

/**
 * Interface DeleteRepositoryInterface
 * 
 * Interface pour les opérations de suppression dans un repository.
 * Suit le principe d'Interface Segregation (ISP) de SOLID en séparant
 * les opérations de suppression des autres opérations.
 */
interface DeleteRepositoryInterface
{
    /**
     * Supprime une entité par son ID
     * 
     * @param int $id L'ID de l'entité à supprimer
     * @return bool True si la suppression a réussi, false sinon
     */
    public function deleteById(int $id): bool;

    /**
     * Supprime une entité
     * 
     * @param mixed $entity L'entité à supprimer
     * @return bool True si la suppression a réussi, false sinon
     */
    public function delete($entity): bool;

    /**
     * Supprime plusieurs entités en une seule opération
     * 
     * @param array $entities Les entités à supprimer
     * @return bool True si toutes les suppressions ont réussi, false sinon
     */
    public function deleteMany(array $entities): bool;
}
