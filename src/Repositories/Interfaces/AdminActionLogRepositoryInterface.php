<?php

namespace App\Repositories\Interfaces;

use App\Entities\AdminActionLog;
use App\Entities\User;

/**
 * Interface for AdminActionLogRepository
 */
interface AdminActionLogRepositoryInterface extends RepositoryInterface, ReadRepositoryInterface
{
    /**
     * Logs an administrator action.
     *
     * @param User $admin The administrator performing the action.
     * @param string $actionType The type of action performed.
     * @param int|null $targetId The ID of the target entity (optional).
     * @param string|null $targetType The type of the target entity (optional).
     * @param array $details Additional details about the action (optional).
     * @return AdminActionLog The created AdminActionLog entity.
     */
    public function log(
        User $admin,
        string $actionType,
        ?int $targetId = null,
        ?string $targetType = null,
        array $details = []
    ): AdminActionLog;

    /**
     * Gets recent admin action logs.
     *
     * @param int $limit The maximum number of logs to retrieve.
     * @return AdminActionLog[] An array of AdminActionLog entities.
     */
    public function getRecentLogs(int $limit = 100): array;

    /**
     * Gets admin action logs by administrator.
     *
     * @param User $admin The administrator whose logs to retrieve.
     * @param int $limit The maximum number of logs to retrieve.
     * @return AdminActionLog[] An array of AdminActionLog entities.
     */
    public function getLogsByAdmin(User $admin, int $limit = 100): array;

    /**
     * Gets admin action logs by action type.
     *
     * @param string $actionType The type of action to filter by.
     * @param int $limit The maximum number of logs to retrieve.
     * @return AdminActionLog[] An array of AdminActionLog entities.
     */
    public function getLogsByActionType(string $actionType, int $limit = 100): array;

    /**
     * Gets admin action logs by target entity.
     *
     * @param int $targetId The ID of the target entity.
     * @param string|null $targetType The type of the target entity (optional).
     * @param int $limit The maximum number of logs to retrieve.
     * @return AdminActionLog[] An array of AdminActionLog entities.
     */
    public function getLogsByTarget(int $targetId, ?string $targetType = null, int $limit = 100): array;
}
