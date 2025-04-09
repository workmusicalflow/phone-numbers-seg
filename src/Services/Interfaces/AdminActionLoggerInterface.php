<?php

namespace App\Services\Interfaces;

/**
 * Interface pour le service de journalisation des actions administrateur
 */
interface AdminActionLoggerInterface
{
    /**
     * Journalise une action administrateur
     *
     * @param int $adminId ID de l'administrateur
     * @param string $actionType Type d'action (ex: user_creation, user_update, etc.)
     * @param int|null $targetId ID de la cible de l'action (ex: ID de l'utilisateur créé)
     * @param string|null $targetType Type de la cible (ex: user, sender_name, etc.)
     * @param array $details Détails supplémentaires de l'action
     * @return bool True si l'action a été journalisée avec succès
     */
    public function log(
        int $adminId,
        string $actionType,
        ?int $targetId = null,
        ?string $targetType = null,
        array $details = []
    ): bool;

    /**
     * Récupère les journaux d'actions récents
     *
     * @param int $limit Nombre maximum de journaux à récupérer
     * @return array Tableau des journaux d'actions
     */
    public function getRecentLogs(int $limit = 100): array;

    /**
     * Récupère les journaux d'actions d'un administrateur spécifique
     *
     * @param int $adminId ID de l'administrateur
     * @param int $limit Nombre maximum de journaux à récupérer
     * @return array Tableau des journaux d'actions
     */
    public function getLogsByAdmin(int $adminId, int $limit = 100): array;

    /**
     * Récupère les journaux d'actions par type d'action
     *
     * @param string $actionType Type d'action
     * @param int $limit Nombre maximum de journaux à récupérer
     * @return array Tableau des journaux d'actions
     */
    public function getLogsByActionType(string $actionType, int $limit = 100): array;

    /**
     * Récupère les journaux d'actions pour une cible spécifique
     *
     * @param int $targetId ID de la cible
     * @param string|null $targetType Type de la cible (optionnel)
     * @param int $limit Nombre maximum de journaux à récupérer
     * @return array Tableau des journaux d'actions
     */
    public function getLogsByTarget(int $targetId, ?string $targetType = null, int $limit = 100): array;
}
