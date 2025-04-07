<?php

namespace App\Services\Interfaces;

/**
 * Interface pour le service d'envoi de SMS
 */
interface SMSSenderServiceInterface
{
    /**
     * Envoie un SMS à un numéro de téléphone
     *
     * @param string $phoneNumber Numéro de téléphone du destinataire
     * @param string $message Message à envoyer
     * @param string $senderName Nom d'expéditeur à utiliser
     * @return bool Succès de l'envoi
     */
    public function sendSMS(string $phoneNumber, string $message, string $senderName = null): bool;
}
