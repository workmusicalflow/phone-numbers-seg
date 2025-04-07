<?php

namespace App\Repositories\Interfaces;

/**
 * Interface RepositoryInterface
 * 
 * Interface complète pour un repository, combinant toutes les interfaces spécifiques.
 * Suit le principe d'Interface Segregation (ISP) de SOLID en permettant aux clients
 * de n'implémenter que les interfaces dont ils ont besoin.
 */
interface RepositoryInterface extends ReadRepositoryInterface, WriteRepositoryInterface, DeleteRepositoryInterface, SearchRepositoryInterface
{
    /**
     * Retourne le nom de la classe d'entité gérée par ce repository
     * 
     * @return string Le nom de la classe d'entité
     */
    public function getEntityClassName(): string;

    /**
     * Commence une transaction
     * 
     * @return void
     */
    public function beginTransaction(): void;

    /**
     * Valide une transaction
     * 
     * @return void
     */
    public function commit(): void;

    /**
     * Annule une transaction
     * 
     * @return void
     */
    public function rollback(): void;
}
