<?php

namespace App\Services;

use App\Entities\User;
use App\Repositories\Interfaces\AdminActionLogRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\AdminActionLoggerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de journalisation des actions administrateur
 */
class AdminActionLogger implements AdminActionLoggerInterface
{
    private AdminActionLogRepositoryInterface $adminActionLogRepository;
    private UserRepositoryInterface $userRepository;
    private LoggerInterface $logger;
    private array $config;

    /**
     * Constructeur
     *
     * @param AdminActionLogRepositoryInterface $adminActionLogRepository Repository pour les logs d'actions administrateur
     * @param UserRepositoryInterface $userRepository Repository for User entities
     * @param LoggerInterface $logger Logger instance
     */
    public function __construct(
        AdminActionLogRepositoryInterface $adminActionLogRepository,
        UserRepositoryInterface $userRepository,
        LoggerInterface $logger
    ) {
        $this->adminActionLogRepository = $adminActionLogRepository;
        $this->userRepository = $userRepository;
        $this->logger = $logger;

        // Charger la configuration
        $this->config = require __DIR__ . '/../config/notification.php';
    }

    /**
     * {@inheritdoc}
     */
    public function log(
        int $adminId,
        string $actionType,
        ?int $targetId = null,
        ?string $targetType = null,
        array $details = []
    ): bool {
        // Vérifier si la journalisation des actions administrateur est activée
        $adminLoggingConfig = $this->config['admin_actions'] ?? null;
        if (!$adminLoggingConfig || !($adminLoggingConfig['enabled'] ?? true)) {
            return false;
        }

        try {
            // Fetch the User entity
            $admin = $this->userRepository->findById($adminId);

            if (!$admin) {
                $this->logger->warning("Attempted to log action for non-existent admin ID: " . $adminId);
                return false; // Cannot log action for non-existent admin
            }

            $this->adminActionLogRepository->log(
                $admin,
                $actionType,
                $targetId,
                $targetType,
                $details
            );
            return true; // Assuming repository's log method is successful if no exception
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la journalisation de l'action administrateur: " . $e->getMessage(), ['exception' => $e]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRecentLogs(int $limit = 100): array
    {
        try {
            return $this->adminActionLogRepository->getRecentLogs($limit);
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la récupération des journaux d'actions: " . $e->getMessage(), ['exception' => $e]);
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLogsByAdmin(int $adminId, int $limit = 100): array
    {
        try {
            // Fetch the User entity
            $admin = $this->userRepository->findById($adminId);

            if (!$admin) {
                $this->logger->warning("Attempted to retrieve logs for non-existent admin ID: " . $adminId);
                return []; // Cannot retrieve logs for non-existent admin
            }

            return $this->adminActionLogRepository->getLogsByAdmin($admin, $limit);
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la récupération des journaux d'actions par administrateur: " . $e->getMessage(), ['exception' => $e]);
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLogsByActionType(string $actionType, int $limit = 100): array
    {
        try {
            return $this->adminActionLogRepository->getLogsByActionType($actionType, $limit);
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la récupération des journaux d'actions par type: " . $e->getMessage(), ['exception' => $e]);
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLogsByTarget(int $targetId, ?string $targetType = null, int $limit = 100): array
    {
        try {
            return $this->adminActionLogRepository->getLogsByTarget($targetId, $targetType, $limit);
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la récupération des journaux d'actions par cible: " . $e->getMessage(), ['exception' => $e]);
            return [];
        }
    }
}
