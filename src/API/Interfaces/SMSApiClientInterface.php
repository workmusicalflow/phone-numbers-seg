<?php

namespace App\API\Interfaces;

/**
 * Interface SMSApiClientInterface
 * 
 * Interface spécifique pour les clients de l'API des SMS.
 * Suit le principe d'Interface Segregation (ISP) de SOLID en exposant uniquement
 * les méthodes nécessaires aux clients de l'API des SMS.
 */
interface SMSApiClientInterface
{
    /**
     * Envoie un SMS à un numéro de téléphone
     * 
     * @param string $phoneNumber Le numéro de téléphone du destinataire
     * @param string $message Le message à envoyer
     * @param string|null $senderName Le nom de l'expéditeur (optionnel)
     * @return array Le résultat de l'envoi du SMS
     */
    public function sendSMS(string $phoneNumber, string $message, ?string $senderName = null): array;

    /**
     * Envoie un SMS à plusieurs numéros de téléphone
     * 
     * @param array $phoneNumbers Les numéros de téléphone des destinataires
     * @param string $message Le message à envoyer
     * @param string|null $senderName Le nom de l'expéditeur (optionnel)
     * @return array Le résultat de l'envoi des SMS
     */
    public function sendBulkSMS(array $phoneNumbers, string $message, ?string $senderName = null): array;

    /**
     * Récupère l'historique des SMS envoyés
     * 
     * @param int|null $limit Limite le nombre d'enregistrements retournés
     * @param int|null $offset Décalage pour la pagination
     * @return array L'historique des SMS
     */
    public function getSMSHistory(?int $limit = null, ?int $offset = null): array;

    /**
     * Récupère l'historique des SMS envoyés à un numéro de téléphone spécifique
     * 
     * @param string $phoneNumber Le numéro de téléphone
     * @param int|null $limit Limite le nombre d'enregistrements retournés
     * @param int|null $offset Décalage pour la pagination
     * @return array L'historique des SMS
     */
    public function getSMSHistoryByPhoneNumber(string $phoneNumber, ?int $limit = null, ?int $offset = null): array;

    /**
     * Récupère l'historique des SMS par statut
     * 
     * @param string $status Le statut des SMS (envoyé, échoué, etc.)
     * @param int|null $limit Limite le nombre d'enregistrements retournés
     * @param int|null $offset Décalage pour la pagination
     * @return array L'historique des SMS
     */
    public function getSMSHistoryByStatus(string $status, ?int $limit = null, ?int $offset = null): array;

    /**
     * Récupère un enregistrement d'historique SMS par son ID
     * 
     * @param int $id L'ID de l'enregistrement d'historique SMS
     * @return array|null L'enregistrement d'historique SMS ou null si non trouvé
     */
    public function getSMSHistoryById(int $id): ?array;

    /**
     * Réessaie l'envoi d'un SMS échoué
     * 
     * @param int $historyId L'ID de l'enregistrement d'historique SMS
     * @return array Le résultat du réessai
     */
    public function retrySMS(int $historyId): array;

    /**
     * Vérifie le crédit SMS disponible
     * 
     * @return int Le nombre de crédits SMS disponibles
     */
    public function checkSMSCredit(): int;

    /**
     * Vérifie le statut de l'API SMS
     * 
     * @return array Le statut de l'API SMS
     */
    public function checkSMSApiStatus(): array;
}
