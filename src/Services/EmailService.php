<?php

namespace App\Services;

use App\Services\Interfaces\EmailServiceInterface;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Service d'envoi d'emails utilisant PHPMailer
 */
class EmailService implements EmailServiceInterface
{
    private array $config;
    private string $templatesDir;
    private bool $debugEnabled;
    private string $logFile;

    /**
     * Constructeur
     */
    public function __construct()
    {
        // Charger la configuration
        $this->config = require __DIR__ . '/../config/email.php';
        $this->templatesDir = $this->config['templates_dir'];
        $this->debugEnabled = $this->config['debug']['enabled'];
        $this->logFile = $this->config['debug']['log_file'];

        // Créer le répertoire de logs s'il n'existe pas
        if ($this->debugEnabled && !is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sendEmail(string $to, string $subject, string $body, array $attachments = []): bool
    {
        $mail = $this->configurePHPMailer();

        try {
            // Destinataire
            $mail->addAddress($to);

            // Sujet et corps
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->isHTML(true);

            // Ajout des pièces jointes
            foreach ($attachments as $attachment) {
                if (isset($attachment['path']) && file_exists($attachment['path'])) {
                    $mail->addAttachment(
                        $attachment['path'],
                        $attachment['name'] ?? basename($attachment['path']),
                        $attachment['encoding'] ?? 'base64',
                        $attachment['type'] ?? ''
                    );
                }
            }

            // Envoi
            $result = $mail->send();

            if ($this->debugEnabled) {
                $this->log("Email envoyé à $to avec le sujet '$subject'");
            }

            return $result;
        } catch (Exception $e) {
            if ($this->debugEnabled) {
                $this->log("Erreur lors de l'envoi de l'email à $to : " . $mail->ErrorInfo);
            }
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sendTemplatedEmail(string $to, string $subject, string $template, array $data, array $attachments = []): bool
    {
        $templatePath = $this->templatesDir . '/' . $template . '.php';

        if (!file_exists($templatePath)) {
            if ($this->debugEnabled) {
                $this->log("Template d'email non trouvé : $templatePath");
            }
            return false;
        }

        // Extraire les variables pour qu'elles soient accessibles dans le template
        extract(array_merge(['subject' => $subject], $data));

        // Capturer la sortie du template
        ob_start();
        include $templatePath;
        $body = ob_get_clean();

        // Envoyer l'email avec les pièces jointes
        return $this->sendEmail($to, $subject, $body, $attachments);
    }

    /**
     * {@inheritdoc}
     */
    public function sendBulkEmail(array $recipients, string $subject, string $body, array $attachments = []): array
    {
        $results = [];

        foreach ($recipients as $recipient) {
            $results[$recipient] = $this->sendEmail($recipient, $subject, $body, $attachments);
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function sendBulkTemplatedEmail(array $recipients, string $subject, string $template, array $data = [], array $attachments = []): array
    {
        $results = [];

        foreach ($recipients as $recipient) {
            $results[$recipient] = $this->sendTemplatedEmail($recipient, $subject, $template, $data, $attachments);
        }

        return $results;
    }

    /**
     * Configure PHPMailer avec les paramètres du fichier de configuration
     */
    private function configurePHPMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);

        // Configuration SMTP
        $mail->isSMTP();
        $mail->Host = $this->config['smtp']['host'];
        $mail->SMTPAuth = $this->config['smtp']['auth'];
        $mail->Username = $this->config['smtp']['username'];
        $mail->Password = $this->config['smtp']['password'];
        $mail->SMTPSecure = $this->config['smtp']['secure'];
        $mail->Port = $this->config['smtp']['port'];

        // Expéditeur
        $mail->setFrom(
            $this->config['from']['email'],
            $this->config['from']['name']
        );

        // Encodage
        $mail->CharSet = 'UTF-8';

        // Debug
        if ($this->debugEnabled) {
            $mail->SMTPDebug = 2; // Niveau de débogage
            $mail->Debugoutput = function ($str, $level) {
                $this->log("PHPMailer Debug ($level): $str");
            };
        }

        return $mail;
    }

    /**
     * Enregistre un message dans le fichier de log
     */
    private function log(string $message): void
    {
        if (!$this->debugEnabled) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;

        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }
}
