<?php

namespace App\Services\Interfaces;

/**
 * Interface pour le service de notification SMS
 */
interface SMSNotificationServiceInterface
{
    /**
     * Envoie une notification SMS pour l'ajout de crédits
     *
     * @param string $phoneNumber Numéro de téléphone du destinataire
     * @param string $username Nom d'utilisateur
     * @param int $amount Montant de crédits ajoutés
     * @param int $newBalance Nouveau solde de crédits
     * @return bool Succès de l'envoi
     */
    public function sendCreditAddedNotification(string $phoneNumber, string $username, int $amount, int $newBalance): bool;

    /**
     * Envoie une notification SMS pour l'approbation d'un nom d'expéditeur
     *
     * @param string $phoneNumber Numéro de téléphone du destinataire
     * @param string $username Nom d'utilisateur
     * @param string $senderName Nom d'expéditeur approuvé
     * @param string $approvalDate Date d'approbation
     * @return bool Succès de l'envoi
     */
    public function sendSenderNameApprovedNotification(string $phoneNumber, string $username, string $senderName, string $approvalDate): bool;

    /**
     * Envoie une notification SMS pour la confirmation d'une commande
     *
     * @param string $phoneNumber Numéro de téléphone du destinataire
     * @param string $username Nom d'utilisateur
     * @param int $orderNumber Numéro de la commande
     * @param int $quantity Quantité de crédits SMS commandés
     * @param string $orderDate Date de la commande
     * @return bool Succès de l'envoi
     */
    public function sendOrderConfirmationNotification(string $phoneNumber, string $username, int $orderNumber, int $quantity, string $orderDate): bool;
}
