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

    /**
     * Récupère les comptes quotidiens de SMS pour une plage de dates
     * 
     * @param string $startDate Date de début au format Y-m-d
     * @param string $endDate Date de fin au format Y-m-d
     * @return array Tableau associatif avec les dates et les comptes
     */
    public function getDailyCountsForDateRange(string $startDate, string $endDate): array;
}
