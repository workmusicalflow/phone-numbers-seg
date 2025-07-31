<?php

namespace App\Repositories\Interfaces\WhatsApp;

use App\Entities\WhatsApp\WhatsAppMessage;
use App\Repositories\Interfaces\RepositoryInterface;
use App\Repositories\Interfaces\SearchRepositoryInterface;

/**
 * Interface pour le repository des messages WhatsApp
 */
interface WhatsAppMessageRepositoryInterface extends RepositoryInterface
{
    /**
     * Enregistre un message WhatsApp
     *
     * @param mixed $message
     * @return mixed
     */
    public function save($message);

    /**
     * Recherche un message par son identifiant Meta
     *
     * @param string $messageId
     * @return WhatsAppMessage|null
     */
    public function findByMessageId(string $messageId): ?WhatsAppMessage;

    /**
     * Récupère tous les messages d'un expéditeur
     *
     * @param string $sender
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findBySender(string $sender, int $limit = 50, int $offset = 0): array;

    /**
     * Récupère tous les messages d'un destinataire
     *
     * @param string $recipient
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findByRecipient(string $recipient, int $limit = 50, int $offset = 0): array;

    /**
     * Compte le nombre de messages pour un expéditeur
     *
     * @param string $sender
     * @return int
     */
    public function countBySender(string $sender): int;

    /**
     * Récupère les messages par type
     *
     * @param string $type
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findByType(string $type, int $limit = 50, int $offset = 0): array;

    /**
     * Récupère les messages dans une plage de dates
     *
     * @param int $startTimestamp
     * @param int $endTimestamp
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findByDateRange(int $startTimestamp, int $endTimestamp, int $limit = 50, int $offset = 0): array;
    
    /**
     * Recherche des entités selon des critères donnés
     * 
     * @param array $criteria Les critères de recherche
     * @param array|null $orderBy Les critères de tri (optionnel)
     * @param int|null $limit Limite de résultats (optionnel)
     * @param int|null $offset Décalage (optionnel)
     * @return array Les entités trouvées
     */
    public function findByCriteria(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;
}