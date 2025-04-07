<?php

namespace App\Services\Interfaces;

/**
 * Interface pour le service d'envoi d'emails
 */
interface EmailServiceInterface
{
    /**
     * Envoie un email
     *
     * @param string $to Adresse email du destinataire
     * @param string $subject Sujet de l'email
     * @param string $body Corps de l'email (peut contenir du HTML)
     * @param array $attachments Pièces jointes (optionnel)
     * @return bool True si l'envoi a réussi, false sinon
     */
    public function sendEmail(string $to, string $subject, string $body, array $attachments = []): bool;

    /**
     * Envoie un email à partir d'un template
     *
     * @param string $to Adresse email du destinataire
     * @param string $subject Sujet de l'email
     * @param string $templateName Nom du template à utiliser
     * @param array $data Données à injecter dans le template
     * @param array $attachments Pièces jointes (optionnel)
     * @return bool True si l'envoi a réussi, false sinon
     */
    public function sendTemplatedEmail(string $to, string $subject, string $templateName, array $data, array $attachments = []): bool;
}
