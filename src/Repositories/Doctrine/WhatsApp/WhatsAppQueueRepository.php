<?php

namespace App\Repositories\Doctrine\WhatsApp;

use App\Repositories\Doctrine\BaseRepository;
use App\Repositories\Interfaces\WhatsApp\WhatsAppQueueRepositoryInterface;
use App\Entities\WhatsApp\WhatsAppQueue;
use App\Entities\User;

/**
 * Repository Doctrine pour la file d'attente WhatsApp
 */
class WhatsAppQueueRepository extends BaseRepository implements WhatsAppQueueRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function save($queue)
    {
        $this->getEntityManager()->persist($queue);
        $this->getEntityManager()->flush();
        return $queue;
    }
    
    /**
     * {@inheritdoc}
     */
    public function findPendingMessages(int $limit = 100): array
    {
        return $this->getEntityManager()->getRepository(WhatsAppQueue::class)
            ->createQueryBuilder('q')
            ->where('q.status = :status')
            ->andWhere('q.scheduledAt IS NULL OR q.scheduledAt <= :now')
            ->setParameter('status', 'PENDING')
            ->setParameter('now', new \DateTime())
            ->orderBy('q.priority', 'DESC')
            ->addOrderBy('q.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByUser(User $user, int $limit = 50, int $offset = 0): array
    {
        return $this->getEntityManager()->getRepository(WhatsAppQueue::class)
            ->createQueryBuilder('q')
            ->where('q.oracleUser = :user')
            ->setParameter('user', $user)
            ->orderBy('q.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * {@inheritdoc}
     */
    public function markAsProcessing(int $queueId): bool
    {
        $queue = $this->find($queueId);
        if ($queue === null) {
            return false;
        }
        
        $queue->setStatus('PROCESSING');
        $queue->setProcessedAt(new \DateTime());
        
        $this->getEntityManager()->flush();
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function markAsSent(int $queueId, string $wabaMessageId): bool
    {
        $queue = $this->find($queueId);
        if ($queue === null) {
            return false;
        }
        
        $queue->setStatus('SENT');
        $queue->setWabaMessageId($wabaMessageId);
        
        $this->getEntityManager()->flush();
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function markAsFailed(int $queueId, string $errorMessage): bool
    {
        $queue = $this->find($queueId);
        if ($queue === null) {
            return false;
        }
        
        $queue->setStatus('FAILED');
        $queue->setErrorMessage($errorMessage);
        $queue->incrementAttempts();
        
        // Si on peut réessayer, remettre en pending
        if ($queue->canRetry()) {
            $queue->setStatus('PENDING');
        }
        
        $this->getEntityManager()->flush();
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function findScheduledMessages(\DateTime $before, int $limit = 100): array
    {
        return $this->getEntityManager()->getRepository(WhatsAppQueue::class)
            ->createQueryBuilder('q')
            ->where('q.status = :status')
            ->andWhere('q.scheduledAt IS NOT NULL')
            ->andWhere('q.scheduledAt <= :before')
            ->setParameter('status', 'PENDING')
            ->setParameter('before', $before)
            ->orderBy('q.priority', 'DESC')
            ->addOrderBy('q.scheduledAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * {@inheritdoc}
     */
    public function countByUserAndStatus(User $user, ?string $status = null): int
    {
        $qb = $this->getEntityManager()->getRepository(WhatsAppQueue::class)
            ->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.oracleUser = :user')
            ->setParameter('user', $user);
        
        if ($status !== null) {
            $qb->andWhere('q.status = :status')
               ->setParameter('status', $status);
        }
        
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
    
    /**
     * {@inheritdoc}
     */
    public function deleteProcessedBefore(\DateTime $before): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        $result = $qb->delete(WhatsAppQueue::class, 'q')
            ->where('q.status IN (:statuses)')
            ->andWhere('q.processedAt < :before')
            ->setParameter('statuses', ['SENT', 'FAILED'])
            ->setParameter('before', $before)
            ->getQuery()
            ->execute();
        
        return $result;
    }
}