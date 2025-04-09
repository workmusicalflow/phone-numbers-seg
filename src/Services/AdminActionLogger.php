<?php

namespace App\Services;

use App\Services\Interfaces\AdminActionLoggerInterface;
use PDO;

/**
 * Service de journalisation des actions administrateur
 */
class AdminActionLogger implements AdminActionLoggerInterface
{
    private PDO $db;
    private array $config;

    /**
     * Constructeur
     *
     * @param PDO $db Instance PDO pour l'accès à la base de données
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;

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
            $stmt = $this->db->prepare("
                INSERT INTO admin_action_logs 
                (admin_id, action_type, target_id, target_type, details) 
                VALUES (?, ?, ?, ?, ?)
            ");

            return $stmt->execute([
                $adminId,
                $actionType,
                $targetId,
                $targetType,
                !empty($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : null
            ]);
        } catch (\PDOException $e) {
            // En cas d'erreur, on peut logger l'erreur dans le système de log d'erreurs
            // mais on ne veut pas que cela bloque l'application
            error_log("Erreur lors de la journalisation de l'action administrateur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRecentLogs(int $limit = 100): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT al.*, u.username as admin_username 
                FROM admin_action_logs al
                JOIN users u ON al.admin_id = u.id
                ORDER BY created_at DESC
                LIMIT ?
            ");

            $stmt->bindParam(1, $limit, PDO::PARAM_INT);
            $stmt->execute();

            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Convertir les détails JSON en tableau PHP
            return $this->processLogs($logs);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des journaux d'actions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLogsByAdmin(int $adminId, int $limit = 100): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT al.*, u.username as admin_username 
                FROM admin_action_logs al
                JOIN users u ON al.admin_id = u.id
                WHERE al.admin_id = ?
                ORDER BY created_at DESC
                LIMIT ?
            ");

            $stmt->bindParam(1, $adminId, PDO::PARAM_INT);
            $stmt->bindParam(2, $limit, PDO::PARAM_INT);
            $stmt->execute();

            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Convertir les détails JSON en tableau PHP
            return $this->processLogs($logs);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des journaux d'actions par administrateur: " . $e->getMessage());
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLogsByActionType(string $actionType, int $limit = 100): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT al.*, u.username as admin_username 
                FROM admin_action_logs al
                JOIN users u ON al.admin_id = u.id
                WHERE al.action_type = ?
                ORDER BY created_at DESC
                LIMIT ?
            ");

            $stmt->bindParam(1, $actionType, PDO::PARAM_STR);
            $stmt->bindParam(2, $limit, PDO::PARAM_INT);
            $stmt->execute();

            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Convertir les détails JSON en tableau PHP
            return $this->processLogs($logs);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des journaux d'actions par type: " . $e->getMessage());
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLogsByTarget(int $targetId, ?string $targetType = null, int $limit = 100): array
    {
        try {
            $sql = "
                SELECT al.*, u.username as admin_username 
                FROM admin_action_logs al
                JOIN users u ON al.admin_id = u.id
                WHERE al.target_id = ?
            ";

            $params = [$targetId];

            if ($targetType !== null) {
                $sql .= " AND al.target_type = ?";
                $params[] = $targetType;
            }

            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = $limit;

            $stmt = $this->db->prepare($sql);

            for ($i = 0; $i < count($params); $i++) {
                $paramType = is_int($params[$i]) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindParam($i + 1, $params[$i], $paramType);
            }

            $stmt->execute();

            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Convertir les détails JSON en tableau PHP
            return $this->processLogs($logs);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des journaux d'actions par cible: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Traite les logs pour convertir les détails JSON en tableau PHP
     *
     * @param array $logs Logs à traiter
     * @return array Logs traités
     */
    private function processLogs(array $logs): array
    {
        foreach ($logs as &$log) {
            if (isset($log['details']) && !empty($log['details'])) {
                $log['details'] = json_decode($log['details'], true);
            } else {
                $log['details'] = [];
            }
        }

        return $logs;
    }
}
