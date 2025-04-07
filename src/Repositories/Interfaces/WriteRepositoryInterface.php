<?php

namespace App\Repositories\Interfaces;

/**
 * Interface WriteRepositoryInterface
 * 
 * Interface pour les opérations d'écriture dans un repository.
 * Suit le principe d'Interface Segregation (ISP) de SOLID en séparant
 * les opérations d'écriture des autres opérations.
 */
interface WriteRepositoryInterface
{
    /**
     * Sauvegarde une entité (création ou mise à jour)
     * 
     * @param mixed $entity L'entité à sauvegarder
     * @return mixed L'entité sauvegardée
     */
    public function save($entity);

    /**
     * Sauvegarde plusieurs entités en une seule opération
     * 
     * @param array $entities Les entités à sauvegarder
     * @return array Les entités sauvegardées
     */
    public function saveMany(array $entities): array;
}
