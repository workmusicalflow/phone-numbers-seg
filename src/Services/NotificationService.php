<?php

namespace App\Services;

use App\Services\Interfaces\EmailServiceInterface;
use App\Services\Interfaces\NotificationServiceInterface;

/**
 * Service de notification combinant différents canaux (email, SMS)
 */
class NotificationService implements NotificationServiceInterface
{
    private EmailServiceInterface $emailService;
    private SMSService $smsService;
    private array $config;

    /**
     * Constructeur
     */
    public function __construct(EmailServiceInterface $emailService, SMSService $smsService)
    {
        $this->emailService = $emailService;
        $this->smsService = $smsService;

        // Charger la configuration
        $this->config = require __DIR__ . '/../config/notification.php';
    }

    /**
     * {@inheritdoc}
     */
    public function sendSMSNotification(string $phoneNumber, string $message): bool
    {
        // Limiter la longueur du message SMS
        $maxLength = $this->config['sms']['max_length'] ?? 160;
        $smsMessage = $this->truncateSMS($message, $maxLength);

        // Envoyer le SMS
        return $this->smsService->sendSMS($phoneNumber, $smsMessage);
    }

    /**
     * {@inheritdoc}
     */
    public function sendEmailNotification(string $email, string $subject, string $message, array $data = []): bool
    {
        // Préparer les données pour le template par défaut
        $templateData = array_merge($data, [
            'subject' => $subject,
            'message' => $message,
            'username' => $data['username'] ?? null,
            'buttonUrl' => $data['buttonUrl'] ?? null,
            'buttonText' => $data['buttonText'] ?? null,
            'showThankYou' => $data['showThankYou'] ?? false,
            'highlightContent' => $data['highlightContent'] ?? null,
            'noteContent' => $data['noteContent'] ?? null,
            'unsubscribeUrl' => $data['unsubscribeUrl'] ?? null,
        ]);

        // Utiliser le template par défaut
        $attachments = $data['attachments'] ?? [];
        return $this->emailService->sendTemplatedEmail($email, $subject, 'default', $templateData, $attachments);
    }

    /**
     * {@inheritdoc}
     */
    public function sendMultiChannelNotification(
        string $phoneNumber,
        string $email,
        string $subject,
        string $message,
        array $data = []
    ): array {
        $result = [
            'sms' => false,
            'email' => false
        ];

        // Envoyer par SMS
        if (!empty($phoneNumber) && $this->isValidPhoneNumber($phoneNumber)) {
            $result['sms'] = $this->sendSMSNotification($phoneNumber, $message);
        }

        // Envoyer par email
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $result['email'] = $this->sendEmailNotification($email, $subject, $message, $data);
        }

        return $result;
    }

    /**
     * Envoie une notification par plusieurs canaux
     *
     * @param string $to Destinataire (email ou numéro de téléphone)
     * @param string $subject Sujet de la notification
     * @param string $message Message à envoyer
     * @param array $options Options supplémentaires
     * @return bool True si au moins un canal a réussi, false sinon
     */
    public function sendNotification(string $to, string $subject, string $message, array $options = []): bool
    {
        $channels = $options['channels'] ?? ['email'];
        $success = false;

        // Envoyer par email si demandé
        if (in_array('email', $channels) && filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $emailSuccess = $this->sendEmailNotification($to, $subject, $message, $options);
            $success = $success || $emailSuccess;
        }

        // Envoyer par SMS si demandé
        if (in_array('sms', $channels) && $this->isValidPhoneNumber($to)) {
            $smsSuccess = $this->sendSMSNotification($to, $message);
            $success = $success || $smsSuccess;
        }

        return $success;
    }

    /**
     * Envoie une notification basée sur un template par plusieurs canaux
     *
     * @param string $to Destinataire (email ou numéro de téléphone)
     * @param string $subject Sujet de la notification
     * @param string $template Nom du template à utiliser
     * @param array $data Données pour le template
     * @param array $options Options supplémentaires
     * @return bool True si au moins un canal a réussi, false sinon
     */
    public function sendTemplatedNotification(string $to, string $subject, string $template, array $data = [], array $options = []): bool
    {
        $channels = $options['channels'] ?? ['email'];
        $success = false;

        // Envoyer par email si demandé
        if (in_array('email', $channels) && filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $attachments = $options['attachments'] ?? [];
            $emailSuccess = $this->emailService->sendTemplatedEmail($to, $subject, $template, $data, $attachments);
            $success = $success || $emailSuccess;
        }

        // Envoyer par SMS si demandé
        if (in_array('sms', $channels) && $this->isValidPhoneNumber($to)) {
            // Pour les SMS, on utilise un template spécifique ou on génère un texte simple
            $smsTemplate = $options['sms_template'] ?? null;
            if ($smsTemplate) {
                $message = $this->generateSMSFromTemplate($smsTemplate, $data);
            } else {
                // Générer un texte simple à partir des données
                $message = $data['message'] ?? $subject;
            }
            $smsSuccess = $this->sendSMSNotification($to, $message);
            $success = $success || $smsSuccess;
        }

        return $success;
    }

    /**
     * Envoie une notification à plusieurs destinataires
     *
     * @param array $recipients Liste des destinataires
     * @param string $subject Sujet de la notification
     * @param string $message Message à envoyer
     * @param array $options Options supplémentaires
     * @return array Résultats par destinataire
     */
    public function sendBulkNotification(array $recipients, string $subject, string $message, array $options = []): array
    {
        $results = [];

        foreach ($recipients as $recipient) {
            $results[$recipient] = $this->sendNotification($recipient, $subject, $message, $options);
        }

        return $results;
    }

    /**
     * Envoie une notification basée sur un template à plusieurs destinataires
     *
     * @param array $recipients Liste des destinataires
     * @param string $subject Sujet de la notification
     * @param string $template Nom du template à utiliser
     * @param array $data Données pour le template
     * @param array $options Options supplémentaires
     * @return array Résultats par destinataire
     */
    public function sendBulkTemplatedNotification(array $recipients, string $subject, string $template, array $data = [], array $options = []): array
    {
        $results = [];

        foreach ($recipients as $recipient) {
            $results[$recipient] = $this->sendTemplatedNotification($recipient, $subject, $template, $data, $options);
        }

        return $results;
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
     * Tronque un message SMS à la longueur maximale
     */
    private function truncateSMS(string $message, int $maxLength): string
    {
        if (mb_strlen($message) <= $maxLength) {
            return $message;
        }

        return mb_substr($message, 0, $maxLength - 3) . '...';
    }

    /**
     * Vérifie si une chaîne est un numéro de téléphone valide
     */
    private function isValidPhoneNumber(string $phoneNumber): bool
    {
        // Validation simple pour les besoins de cette méthode
        // Dans un cas réel, on utiliserait une validation plus robuste
        return preg_match('/^\+?[0-9]{8,15}$/', $phoneNumber);
    }
}
