<?php

namespace App\Repositories\Interfaces;

/**
 * Interface pour les repositories qui peuvent compter leurs entités
 */
interface CountableRepositoryInterface
{
    /**
     * Compte le nombre total d'entités
     * 
     * @return int
     */
    public function countAll(): int; // Renamed from count() to countAll() for consistency
}
