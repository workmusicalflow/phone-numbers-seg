<?php

namespace App\Services;

use App\Services\Interfaces\RealtimeNotificationServiceInterface;
use App\Repositories\UserRepository;
use App\Models\User;
use Psr\Log\LoggerInterface;

/**
 * Service de notification en temps réel
 */
class RealtimeNotificationService implements RealtimeNotificationServiceInterface
{
    private array $config;
    private UserRepository $userRepository;
    private LoggerInterface $logger;
    private ?object $pusher = null;

    /**
     * Constructeur
     */
    public function __construct(UserRepository $userRepository, LoggerInterface $logger)
    {
        $this->userRepository = $userRepository;
        $this->logger = $logger;

        // Charger la configuration
        $this->config = require __DIR__ . '/../config/notification.php';

        // Initialiser le driver de notification en temps réel
        $this->initializeDriver();
    }

    /**
     * {@inheritdoc}
     */
    public function sendToUser(int $userId, string $type, string $message, array $data = []): bool
    {
        try {
            $channel = $this->config['realtime']['user_channel_prefix'] . $userId;
            $eventName = 'notification';
            $payload = [
                'type' => $type,
                'message' => $message,
                'timestamp' => date('Y-m-d H:i:s'),
                'data' => $data
            ];

            return $this->triggerEvent($channel, $eventName, $payload);
        } catch (\Exception $e) {
            $this->logError('Erreur lors de l\'envoi de notification à l\'utilisateur ' . $userId, $e);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sendToAdmins(string $type, string $message, array $data = []): bool
    {
        try {
            $channel = $this->config['realtime']['admin_channel'];
            $eventName = 'admin_notification';
            $payload = [
                'type' => $type,
                'message' => $message,
                'timestamp' => date('Y-m-d H:i:s'),
                'data' => $data
            ];

            return $this->triggerEvent($channel, $eventName, $payload);
        } catch (\Exception $e) {
            $this->logError('Erreur lors de l\'envoi de notification aux administrateurs', $e);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function broadcast(string $type, string $message, array $data = []): bool
    {
        try {
            $channel = 'broadcast';
            $eventName = 'global_notification';
            $payload = [
                'type' => $type,
                'message' => $message,
                'timestamp' => date('Y-m-d H:i:s'),
                'data' => $data
            ];

            return $this->triggerEvent($channel, $eventName, $payload);
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la diffusion de notification à tous les utilisateurs', $e);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sendToGroup(array $userIds, string $type, string $message, array $data = []): bool
    {
        $success = true;

        foreach ($userIds as $userId) {
            $result = $this->sendToUser($userId, $type, $message, $data);
            $success = $success && $result;
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function logAdminEvent(string $eventType, array $eventData): bool
    {
        try {
            // Récupérer la configuration pour ce type d'événement
            $eventConfig = $this->config['admin'][$eventType] ?? null;
            if (!$eventConfig) {
                $this->logger->warning("Type d'événement administratif non configuré: $eventType");
                return false;
            }

            // Déterminer les canaux de notification à utiliser
            $channels = $eventConfig['channels'] ?? ['realtime'];

            // Préparer les données de notification
            $notificationData = $eventData;
            $notificationData['event_type'] = $eventType;
            $notificationData['timestamp'] = date('Y-m-d H:i:s');

            // Déterminer le message et le sujet
            $message = $this->generateMessageFromEventType($eventType, $eventData);
            $subject = $this->generateSubjectFromEventType($eventType, $eventData);

            // Envoyer la notification par les canaux appropriés
            $success = true;

            // Notification en temps réel aux administrateurs
            if (in_array('realtime', $channels)) {
                $result = $this->sendToAdmins('admin_event', $message, $notificationData);
                $success = $success && $result;
            }

            // Notification par email
            if (in_array('email', $channels) && isset($eventData['admin_email'])) {
                $emailTemplate = $eventConfig['email_template'] ?? 'default';
                $emailService = new EmailService(); // Idéalement, injecté via le constructeur
                $result = $emailService->sendTemplatedEmail(
                    $eventData['admin_email'],
                    $subject,
                    $emailTemplate,
                    $notificationData
                );
                $success = $success && $result;
            }

            // Notification par SMS
            if (in_array('sms', $channels) && isset($eventData['admin_phone'])) {
                $smsTemplate = $eventConfig['sms_template'] ?? null;
                // Récupérer les informations de configuration SMS
                $smsConfig = require __DIR__ . '/../config/sms.php';
                $smsService = new SMSService(
                    $smsConfig['orange_api']['client_id'],
                    $smsConfig['orange_api']['client_secret'],
                    $smsConfig['sender_address'],
                    $smsConfig['sender_name']
                ); // Idéalement, injecté via le constructeur

                if ($smsTemplate) {
                    // Générer le message SMS à partir du template
                    $smsMessage = $this->generateSMSFromTemplate($smsTemplate, $notificationData);
                } else {
                    $smsMessage = $message;
                }

                $result = $smsService->sendSMS($eventData['admin_phone'], $smsMessage);
                $success = $success && $result;
            }

            return $success;
        } catch (\Exception $e) {
            $this->logError("Erreur lors de l'enregistrement de l'événement administratif: $eventType", $e);
            return false;
        }
    }

    /**
     * Initialise le driver de notification en temps réel
     */
    private function initializeDriver(): void
    {
        $driver = $this->config['realtime']['broadcast_driver'] ?? 'log';

        switch ($driver) {
            case 'pusher':
                $this->initializePusher();
                break;
            case 'redis':
                $this->initializeRedis();
                break;
            case 'log':
            default:
                // Pas d'initialisation nécessaire pour le driver de log
                break;
        }
    }

    /**
     * Initialise le driver Pusher
     */
    private function initializePusher(): void
    {
        // Vérifier si la classe Pusher est disponible
        if (!class_exists('Pusher\Pusher')) {
            $this->logger->warning('La bibliothèque Pusher n\'est pas disponible. Utilisation du driver de log à la place.');
            return;
        }

        // Récupérer les informations d'authentification Pusher
        $appId = $_ENV['PUSHER_APP_ID'] ?? null;
        $appKey = $_ENV['PUSHER_APP_KEY'] ?? null;
        $appSecret = $_ENV['PUSHER_APP_SECRET'] ?? null;
        $cluster = $_ENV['PUSHER_APP_CLUSTER'] ?? 'eu';

        if (!$appId || !$appKey || !$appSecret) {
            $this->logger->warning('Informations d\'authentification Pusher manquantes. Utilisation du driver de log à la place.');
            return;
        }

        // Initialiser Pusher
        $options = [
            'cluster' => $cluster,
            'useTLS' => true
        ];

        try {
            // Commenté car la bibliothèque Pusher n'est pas installée
            // Pour utiliser Pusher, il faudrait installer la bibliothèque via Composer:
            // composer require pusher/pusher-php-server
            // $this->pusher = new \Pusher\Pusher($appKey, $appSecret, $appId, $options);

            $this->logger->info('Pusher serait initialisé ici si la bibliothèque était installée.');
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'initialisation de Pusher: ' . $e->getMessage());
        }
    }

    /**
     * Initialise le driver Redis
     */
    private function initializeRedis(): void
    {
        // Implémentation de l'initialisation Redis
        // À compléter selon les besoins
    }

    /**
     * Déclenche un événement sur un canal
     */
    private function triggerEvent(string $channel, string $eventName, array $payload): bool
    {
        $driver = $this->config['realtime']['broadcast_driver'] ?? 'log';

        switch ($driver) {
            case 'pusher':
                return $this->triggerPusherEvent($channel, $eventName, $payload);
            case 'redis':
                return $this->triggerRedisEvent($channel, $eventName, $payload);
            case 'log':
            default:
                return $this->triggerLogEvent($channel, $eventName, $payload);
        }
    }

    /**
     * Déclenche un événement Pusher
     */
    private function triggerPusherEvent(string $channel, string $eventName, array $payload): bool
    {
        if (!$this->pusher) {
            $this->logger->warning('Pusher n\'est pas initialisé. Utilisation du driver de log à la place.');
            return $this->triggerLogEvent($channel, $eventName, $payload);
        }

        try {
            $result = $this->pusher->trigger($channel, $eventName, $payload);
            return $result === true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du déclenchement de l\'événement Pusher: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Déclenche un événement Redis
     */
    private function triggerRedisEvent(string $channel, string $eventName, array $payload): bool
    {
        // Implémentation du déclenchement d'événement Redis
        // À compléter selon les besoins
        return true;
    }

    /**
     * Déclenche un événement de log
     */
    private function triggerLogEvent(string $channel, string $eventName, array $payload): bool
    {
        $logMessage = sprintf(
            'Notification en temps réel - Canal: %s, Événement: %s, Contenu: %s',
            $channel,
            $eventName,
            json_encode($payload, JSON_UNESCAPED_UNICODE)
        );

        $this->logger->info($logMessage);
        return true;
    }

    /**
     * Génère un message à partir d'un type d'événement
     */
    private function generateMessageFromEventType(string $eventType, array $eventData): string
    {
        switch ($eventType) {
            case 'sender_name_approval':
                $senderName = $eventData['sender_name'] ?? 'Inconnu';
                $username = $eventData['username'] ?? 'Inconnu';
                return "Le nom d'expéditeur '$senderName' de l'utilisateur '$username' a été approuvé.";

            case 'order_completion':
                $orderId = $eventData['order_id'] ?? 'Inconnu';
                $username = $eventData['username'] ?? 'Inconnu';
                $credits = $eventData['credits'] ?? 0;
                return "La commande #$orderId de $credits crédits pour l'utilisateur '$username' a été complétée.";

            case 'credit_added':
                $username = $eventData['username'] ?? 'Inconnu';
                $credits = $eventData['credits'] ?? 0;
                return "$credits crédits ont été ajoutés au compte de l'utilisateur '$username'.";

            default:
                return "Événement administratif: $eventType";
        }
    }

    /**
     * Génère un sujet à partir d'un type d'événement
     */
    private function generateSubjectFromEventType(string $eventType, array $eventData): string
    {
        switch ($eventType) {
            case 'sender_name_approval':
                return "Approbation d'un nom d'expéditeur";

            case 'order_completion':
                return "Complétion d'une commande de crédits";

            case 'credit_added':
                return "Ajout de crédits à un compte utilisateur";

            default:
                return "Événement administratif: $eventType";
        }
    }

    /**
     * Génère un message SMS à partir d'un template et de données
     */
    private function generateSMSFromTemplate(string $template, array $data): string
    {
        // Chemin vers les templates SMS
        $templatePath = $this->config['sms']['templates_dir'] . '/' . $template . '.php';

        if (file_exists($templatePath)) {
            // Extraire les variables pour qu'elles soient accessibles dans le template
            extract($data);

            // Capturer la sortie du template
            ob_start();
            include $templatePath;
            $message = ob_get_clean();

            return $message;
        }

        // Si le template n'existe pas, générer un message simple
        $message = $data['message'] ?? '';
        if (empty($message) && isset($data['subject'])) {
            $message = $data['subject'];
        }

        return $message;
    }

    /**
     * Journalise une erreur
     */
    private function logError(string $message, \Exception $exception): void
    {
        $errorConfig = $this->config['error_logging'] ?? null;

        if (!$errorConfig || !($errorConfig['enabled'] ?? false)) {
            return;
        }

        $logMessage = $message . ': ' . $exception->getMessage();

        if ($errorConfig['include_trace'] ?? false) {
            $logMessage .= "\nTrace: " . $exception->getTraceAsString();
        }

        $this->logger->error($logMessage);

        // Notifier l'administrateur si configuré
        if ($errorConfig['notify_admin'] ?? false) {
            $adminEmail = $errorConfig['admin_email'] ?? null;

            if ($adminEmail) {
                $emailService = new EmailService(); // Idéalement, injecté via le constructeur
                $emailService->sendEmail(
                    $adminEmail,
                    'Erreur de notification en temps réel',
                    $logMessage
                );
            }
        }
    }
}
