<?php

namespace App\Services\WhatsApp;

use App\Services\Interfaces\WhatsApp\WebhookVerificationServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Service pour la vérification des webhooks WhatsApp
 */
class WebhookVerificationService implements WebhookVerificationServiceInterface
{
    /**
     * @var string Le token de vérification configuré
     */
    private string $verifyToken;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Constructeur
     *
     * @param string $verifyToken Token de vérification
     * @param LoggerInterface $logger
     */
    public function __construct(string $verifyToken, LoggerInterface $logger)
    {
        $this->verifyToken = $verifyToken;
        $this->logger = $logger;
    }

    /**
     * Vérifie le token de vérification de webhook
     *
     * @param string $mode Mode de vérification (subscribe)
     * @param string $token Token de vérification à valider
     * @return bool
     */
    public function verifyToken(string $mode, string $token): bool
    {
        // Le mode doit être "subscribe" pour WhatsApp
        if ($mode !== 'subscribe') {
            $this->logger->warning('Mode de vérification webhook invalide', [
                'mode' => $mode,
                'expected' => 'subscribe'
            ]);
            return false;
        }

        // Vérification du token
        $isValid = hash_equals($this->verifyToken, $token);
        
        if (!$isValid) {
            $this->logger->warning('Token de vérification webhook invalide');
        }
        
        return $isValid;
    }
}