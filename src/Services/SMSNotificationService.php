<?php

namespace App\Services;

use App\Services\Interfaces\SMSNotificationServiceInterface;
use App\Services\Interfaces\SMSSenderServiceInterface;
use App\Services\Interfaces\SMSHistoryServiceInterface;

/**
 * Service de notification SMS
 */
class SMSNotificationService implements SMSNotificationServiceInterface
{
    /**
     * @var SMSSenderServiceInterface
     */
    private $smsSender;

    /**
     * @var SMSHistoryServiceInterface
     */
    private $smsHistoryService;

    /**
     * @var array
     */
    private $config;

    /**
     * @var array
     */
    private $templates = [];

    /**
     * Constructeur
     *
     * @param SMSSenderServiceInterface $smsSender
     * @param SMSHistoryServiceInterface $smsHistoryService
     */
    public function __construct(
        SMSSenderServiceInterface $smsSender,
        SMSHistoryServiceInterface $smsHistoryService
    ) {
        $this->smsSender = $smsSender;
        $this->smsHistoryService = $smsHistoryService;
        $this->config = require __DIR__ . '/../config/sms.php';
        $this->loadTemplates();
    }

    /**
     * Charge les templates SMS
     */
    private function loadTemplates(): void
    {
        foreach ($this->config['templates'] as $name => $path) {
            if (file_exists($path)) {
                $this->templates[$name] = $path;
            }
        }
    }

    /**
     * Récupère un template SMS et remplace les variables
     *
     * @param string $templateName
     * @param array $variables
     * @return string
     */
    private function renderTemplate(string $templateName, array $variables): string
    {
        if (!isset($this->templates[$templateName])) {
            throw new \InvalidArgumentException("Template SMS '$templateName' non trouvé");
        }

        // Extraction des variables pour les rendre disponibles dans le template
        extract($variables);

        // Inclusion du template qui retourne le contenu
        return include $this->templates[$templateName];
    }

    /**
     * Vérifie si les notifications SMS sont activées
     *
     * @return bool
     */
    private function isEnabled(): bool
    {
        return $this->config['notifications']['enabled'] ?? false;
    }

    /**
     * Récupère le nom d'expéditeur par défaut
     *
     * @return string
     */
    private function getDefaultSenderName(): string
    {
        return $this->config['notifications']['default_sender_name'] ?? '225HBC';
    }

    /**
     * {@inheritdoc}
     */
    public function sendCreditAddedNotification(string $phoneNumber, string $username, int $amount, int $newBalance): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $message = $this->renderTemplate('credit_added', [
            'username' => $username,
            'amount' => $amount,
            'newBalance' => $newBalance
        ]);

        return $this->smsSender->sendSMS($phoneNumber, $message, $this->getDefaultSenderName());
    }

    /**
     * {@inheritdoc}
     */
    public function sendSenderNameApprovedNotification(string $phoneNumber, string $username, string $senderName, string $approvalDate): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $message = $this->renderTemplate('sender_name_approved', [
            'username' => $username,
            'senderName' => $senderName,
            'approvalDate' => $approvalDate
        ]);

        return $this->smsSender->sendSMS($phoneNumber, $message, $this->getDefaultSenderName());
    }

    /**
     * {@inheritdoc}
     */
    public function sendOrderConfirmationNotification(string $phoneNumber, string $username, int $orderNumber, int $quantity, string $orderDate): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $message = $this->renderTemplate('order_confirmation', [
            'username' => $username,
            'orderNumber' => $orderNumber,
            'quantity' => $quantity,
            'orderDate' => $orderDate
        ]);

        return $this->smsSender->sendSMS($phoneNumber, $message, $this->getDefaultSenderName());
    }
}
