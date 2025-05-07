<?php

namespace App\Services\Interfaces\WhatsApp;

/**
 * Interface pour le service de vérification des webhooks WhatsApp
 */
interface WebhookVerificationServiceInterface
{
    /**
     * Vérifie le token de vérification de webhook
     *
     * @param string $mode Mode de vérification (subscribe)
     * @param string $token Token de vérification
     * @return bool
     */
    public function verifyToken(string $mode, string $token): bool;
}