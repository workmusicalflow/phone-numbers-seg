<?php

namespace App\GraphQL\Controllers\WhatsApp;

use App\Services\Interfaces\WhatsApp\WebhookVerificationServiceInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppMessageServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Contrôleur pour gérer les webhooks WhatsApp
 */
class WebhookController
{
    /**
     * @var WebhookVerificationServiceInterface
     */
    private WebhookVerificationServiceInterface $verificationService;

    /**
     * @var WhatsAppMessageServiceInterface
     */
    private WhatsAppMessageServiceInterface $messageService;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Constructeur
     *
     * @param WebhookVerificationServiceInterface $verificationService
     * @param WhatsAppMessageServiceInterface $messageService
     * @param LoggerInterface $logger
     */
    public function __construct(
        WebhookVerificationServiceInterface $verificationService,
        WhatsAppMessageServiceInterface $messageService,
        LoggerInterface $logger
    ) {
        $this->verificationService = $verificationService;
        $this->messageService = $messageService;
        $this->logger = $logger;
    }

    /**
     * Vérifie le webhook lors de sa configuration avec Meta
     *
     * @param string $mode
     * @param string $token
     * @param string $challenge
     * @return string
     */
    public function verifyWebhook(string $mode, string $token, string $challenge): string
    {
        $this->logger->info('Tentative de vérification de webhook', [
            'mode' => $mode,
            'token' => substr($token, 0, 5) . '...' // Ne pas logger le token complet
        ]);

        if ($this->verificationService->verifyToken($mode, $token)) {
            $this->logger->info('Vérification de webhook réussie');
            return $challenge;
        }

        $this->logger->warning('Échec de vérification de webhook');
        return 'Échec de vérification';
    }

    /**
     * Traite les données de webhook entrantes
     *
     * @param array $data
     * @return bool
     */
    public function processWebhook(array $data): bool
    {
        try {
            $this->logger->info('Réception de données webhook', [
                'type' => $data['object'] ?? 'inconnu'
            ]);

            // Vérifie si c'est un événement WhatsApp
            if (isset($data['object']) && $data['object'] === 'whatsapp_business_account') {
                // Traitement des entrées
                if (isset($data['entry']) && is_array($data['entry'])) {
                    foreach ($data['entry'] as $entry) {
                        $this->processEntry($entry);
                    }
                }
                return true;
            }

            $this->logger->info('Objet webhook non pris en charge', [
                'object' => $data['object'] ?? 'non défini'
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du traitement webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Traite une entrée de webhook
     *
     * @param array $entry
     * @return void
     */
    private function processEntry(array $entry): void
    {
        if (isset($entry['changes']) && is_array($entry['changes'])) {
            foreach ($entry['changes'] as $change) {
                $this->processChange($change);
            }
        }
    }

    /**
     * Traite un changement
     *
     * @param array $change
     * @return void
     */
    private function processChange(array $change): void
    {
        if (isset($change['value']) && isset($change['value']['messages']) && is_array($change['value']['messages'])) {
            foreach ($change['value']['messages'] as $message) {
                $this->messageService->processIncomingMessage($message, $change['value']);
            }
        }

        // Gestion des statuts de message
        if (isset($change['value']) && isset($change['value']['statuses']) && is_array($change['value']['statuses'])) {
            foreach ($change['value']['statuses'] as $status) {
                $this->messageService->processMessageStatus($status, $change['value']);
            }
        }
    }
}