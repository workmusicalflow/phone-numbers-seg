<?php

namespace App\Repositories\Interfaces\WhatsApp;

use App\Entities\WhatsApp\WhatsAppQueue;
use App\Entities\User;

/**
 * Interface pour le repository de la file d'attente WhatsApp
 */
interface WhatsAppQueueRepositoryInterface
{
    /**
     * Enregistrer un message dans la file d'attente
     * 
     * @param mixed $queue
     * @return mixed
     */
    public function save($queue);
    
    /**
     * Obtenir les messages en attente de traitement
     * 
     * @param int $limit Nombre maximum de messages
     * @return WhatsAppQueue[]
     */
    public function findPendingMessages(int $limit = 100): array;
    
    /**
     * Obtenir les messages d'un utilisateur
     * 
     * @param User $user
     * @param int $limit
     * @param int $offset
     * @return WhatsAppQueue[]
     */
    public function findByUser(User $user, int $limit = 50, int $offset = 0): array;
    
    /**
     * Marquer un message comme en cours de traitement
     * 
     * @param int $queueId
     * @return bool
     */
    public function markAsProcessing(int $queueId): bool;
    
    /**
     * Marquer un message comme envoyé
     * 
     * @param int $queueId
     * @param string $wabaMessageId
     * @return bool
     */
    public function markAsSent(int $queueId, string $wabaMessageId): bool;
    
    /**
     * Marquer un message comme échoué
     * 
     * @param int $queueId
     * @param string $errorMessage
     * @return bool
     */
    public function markAsFailed(int $queueId, string $errorMessage): bool;
    
    /**
     * Obtenir les messages programmés
     * 
     * @param \DateTime $before
     * @param int $limit
     * @return WhatsAppQueue[]
     */
    public function findScheduledMessages(\DateTime $before, int $limit = 100): array;
    
    /**
     * Compter les messages par statut pour un utilisateur
     * 
     * @param User $user
     * @param string|null $status
     * @return int
     */
    public function countByUserAndStatus(User $user, ?string $status = null): int;
    
    /**
     * Effacer les vieux messages traités
     * 
     * @param \DateTime $before
     * @return int Nombre de messages supprimés
     */
    public function deleteProcessedBefore(\DateTime $before): int;
}