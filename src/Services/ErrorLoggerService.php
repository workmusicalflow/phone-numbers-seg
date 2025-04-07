<?php

namespace App\Services;

use App\Services\Interfaces\ErrorLoggerServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de journalisation des erreurs
 */
class ErrorLoggerService implements ErrorLoggerServiceInterface
{
    private LoggerInterface $logger;
    private array $config;
    private ?NotificationService $notificationService;

    /**
     * Constructeur
     */
    public function __construct(LoggerInterface $logger, ?NotificationService $notificationService = null)
    {
        $this->logger = $logger;
        $this->notificationService = $notificationService;

        // Charger la configuration
        $this->config = require __DIR__ . '/../config/notification.php';
    }

    /**
     * Journalise une erreur
     *
     * @param string $message Message d'erreur
     * @param \Throwable $exception Exception
     * @param string $level Niveau de log (debug, info, warning, error, critical)
     * @param array $context Contexte supplémentaire
     * @param bool $notifyAdmin Si true, notifie l'administrateur
     * @return void
     */
    public function logError(
        string $message,
        \Throwable $exception,
        string $level = 'error',
        array $context = [],
        bool $notifyAdmin = false
    ): void {
        $errorConfig = $this->config['error_logging'] ?? null;

        if (!$errorConfig || !($errorConfig['enabled'] ?? false)) {
            return;
        }

        // Vérifier si le niveau de log est suffisant
        $configLevel = $errorConfig['log_level'] ?? 'error';
        if (!$this->isLevelSufficient($level, $configLevel)) {
            return;
        }

        // Préparer le message de log
        $logMessage = $this->formatErrorMessage($message, $exception, $errorConfig, $context);

        // Journaliser l'erreur
        switch ($level) {
            case 'debug':
                $this->logger->debug($logMessage, $context);
                break;
            case 'info':
                $this->logger->info($logMessage, $context);
                break;
            case 'warning':
                $this->logger->warning($logMessage, $context);
                break;
            case 'critical':
                $this->logger->critical($logMessage, $context);
                break;
            case 'error':
            default:
                $this->logger->error($logMessage, $context);
                break;
        }

        // Notifier l'administrateur si configuré
        if ($notifyAdmin && $errorConfig['notify_admin'] ?? false) {
            $this->notifyAdmin($message, $logMessage, $level, $context);
        }
    }

    /**
     * Journalise une erreur d'API
     *
     * @param string $endpoint Point d'API
     * @param string $method Méthode HTTP
     * @param int $statusCode Code de statut HTTP
     * @param string $response Réponse de l'API
     * @param array $requestData Données de la requête
     * @param array $context Contexte supplémentaire
     * @return void
     */
    public function logApiError(
        string $endpoint,
        string $method,
        int $statusCode,
        string $response,
        array $requestData = [],
        array $context = []
    ): void {
        $message = "Erreur API: $method $endpoint a retourné $statusCode";
        $exception = new \Exception($response);

        // Ajouter les données de la requête au contexte
        $context['request_data'] = $requestData;
        $context['status_code'] = $statusCode;
        $context['response'] = $response;

        $this->logError($message, $exception, 'error', $context, true);
    }

    /**
     * Journalise une erreur de validation
     *
     * @param string $entity Entité concernée
     * @param array $errors Erreurs de validation
     * @param array $data Données soumises
     * @param array $context Contexte supplémentaire
     * @return void
     */
    public function logValidationError(
        string $entity,
        array $errors,
        array $data = [],
        array $context = []
    ): void {
        $message = "Erreur de validation pour $entity";
        $errorMessage = json_encode($errors, JSON_UNESCAPED_UNICODE);
        $exception = new \Exception($errorMessage);

        // Ajouter les données soumises au contexte
        $context['validation_errors'] = $errors;
        $context['submitted_data'] = $data;

        $this->logError($message, $exception, 'warning', $context, false);
    }

    /**
     * Journalise une erreur d'accès
     *
     * @param string $resource Ressource concernée
     * @param string $action Action tentée
     * @param int $userId ID de l'utilisateur
     * @param array $context Contexte supplémentaire
     * @return void
     */
    public function logAccessError(
        string $resource,
        string $action,
        int $userId,
        array $context = []
    ): void {
        $message = "Erreur d'accès: L'utilisateur $userId a tenté d'effectuer l'action '$action' sur la ressource '$resource'";
        $exception = new \Exception("Accès non autorisé");

        // Ajouter les informations d'accès au contexte
        $context['resource'] = $resource;
        $context['action'] = $action;
        $context['user_id'] = $userId;

        $this->logError($message, $exception, 'warning', $context, true);
    }

    /**
     * Vérifie si le niveau de log est suffisant
     *
     * @param string $level Niveau de log actuel
     * @param string $configLevel Niveau de log configuré
     * @return bool True si le niveau est suffisant
     */
    private function isLevelSufficient(string $level, string $configLevel): bool
    {
        $levels = [
            'debug' => 0,
            'info' => 1,
            'warning' => 2,
            'error' => 3,
            'critical' => 4
        ];

        $levelValue = $levels[$level] ?? $levels['error'];
        $configValue = $levels[$configLevel] ?? $levels['error'];

        return $levelValue >= $configValue;
    }

    /**
     * Formate le message d'erreur
     *
     * @param string $message Message d'erreur
     * @param \Throwable $exception Exception
     * @param array $errorConfig Configuration des erreurs
     * @param array $context Contexte supplémentaire
     * @return string Message formaté
     */
    private function formatErrorMessage(
        string $message,
        \Throwable $exception,
        array $errorConfig,
        array $context = []
    ): string {
        $logMessage = $message . ': ' . $exception->getMessage();

        // Ajouter la trace si configuré
        if ($errorConfig['include_trace'] ?? false) {
            $logMessage .= "\nTrace: " . $exception->getTraceAsString();
        }

        // Ajouter le contexte si présent
        if (!empty($context)) {
            $logMessage .= "\nContexte: " . json_encode($context, JSON_UNESCAPED_UNICODE);
        }

        return $logMessage;
    }

    /**
     * Notifie l'administrateur d'une erreur
     *
     * @param string $message Message d'erreur
     * @param string $logMessage Message de log complet
     * @param string $level Niveau de log
     * @param array $context Contexte supplémentaire
     * @return void
     */
    private function notifyAdmin(
        string $message,
        string $logMessage,
        string $level,
        array $context = []
    ): void {
        $errorConfig = $this->config['error_logging'] ?? null;
        $adminEmail = $errorConfig['admin_email'] ?? null;

        if (!$adminEmail) {
            return;
        }

        // Préparer le sujet de l'email
        $subject = "[" . strtoupper($level) . "] " . $message;

        // Envoyer la notification
        if ($this->notificationService) {
            $this->notificationService->sendEmailNotification(
                $adminEmail,
                $subject,
                $logMessage,
                [
                    'level' => $level,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'context' => $context
                ]
            );
        } else {
            // Fallback si le service de notification n'est pas disponible
            $emailService = new EmailService();
            $emailService->sendEmail(
                $adminEmail,
                $subject,
                nl2br($logMessage)
            );
        }
    }
}
