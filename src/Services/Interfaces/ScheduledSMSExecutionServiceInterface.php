<?php

namespace App\Services\Interfaces;

/**
 * Interface pour le service d'exécution des SMS planifiés
 */
interface ScheduledSMSExecutionServiceInterface
{
    /**
     * Exécute les SMS planifiés qui sont dus
     * 
     * @param int|null $limit Limite le nombre de SMS à exécuter
     * @return array Résultats de l'exécution
     */
    public function executeScheduledSMS(?int $limit = 100): array;

    /**
     * Exécute un SMS planifié spécifique
     * 
     * @param int $scheduledSmsId ID du SMS planifié
     * @return array Résultat de l'exécution
     */
    public function executeSpecificScheduledSMS(int $scheduledSmsId): array;
}
