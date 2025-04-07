<?php

namespace App\Repositories\Interfaces;

/**
 * Interface pour le repository SMSHistory
 */
interface SMSHistoryRepositoryInterface extends DashboardRepositoryInterface
{
    /**
     * Compte le nombre de SMS envoyés à une date spécifique
     * 
     * @param string $date Date au format Y-m-d
     * @return int
     */
    public function countByDate(string $date): int;
}
