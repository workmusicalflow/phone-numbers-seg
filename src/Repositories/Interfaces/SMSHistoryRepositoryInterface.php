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

    /**
     * Trouver les enregistrements d'historique SMS par ID d'utilisateur
     *
     * @param int $userId
     * @param int $limit Limite de résultats
     * @param int $offset Offset pour la pagination
     * @return array
     */
    public function findByUserId(int $userId, int $limit = 100, int $offset = 0): array;

    /**
     * Compte le nombre de SMS envoyés par un utilisateur spécifique
     * 
     * @param int $userId ID de l'utilisateur
     * @return int
     */
    public function countByUserId(int $userId): int;
}
