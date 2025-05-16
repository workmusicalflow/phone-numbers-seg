<?php

namespace App\Repositories\Doctrine\WhatsApp;

use App\Repositories\Doctrine\BaseRepository;
use App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface;
use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\Entities\User;
use App\Entities\Contact;
use Doctrine\ORM\QueryBuilder;

/**
 * Repository Doctrine pour l'historique des messages WhatsApp
 */
class WhatsAppMessageHistoryRepository extends BaseRepository implements WhatsAppMessageHistoryRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function save($message)
    {
        $this->getEntityManager()->persist($message);
        $this->getEntityManager()->flush();
        // Rafraîchir l'entité pour obtenir l'ID généré
        $this->getEntityManager()->refresh($message);
        return $message;
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByWabaMessageId(string $wabaMessageId): ?WhatsAppMessageHistory
    {
        return $this->getEntityManager()->getRepository(WhatsAppMessageHistory::class)
            ->findOneBy(['wabaMessageId' => $wabaMessageId]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByUser(User $user, int $limit = 50, int $offset = 0): array
    {
        return $this->getEntityManager()->getRepository(WhatsAppMessageHistory::class)
            ->createQueryBuilder('m')
            ->where('m.oracleUser = :user')
            ->setParameter('user', $user)
            ->orderBy('m.timestamp', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByContact(Contact $contact, int $limit = 50, int $offset = 0): array
    {
        return $this->getEntityManager()->getRepository(WhatsAppMessageHistory::class)
            ->createQueryBuilder('m')
            ->where('m.contact = :contact')
            ->setParameter('contact', $contact)
            ->orderBy('m.timestamp', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByPhoneNumber(string $phoneNumber, ?User $user = null, int $limit = 50, int $offset = 0): array
    {
        $qb = $this->getEntityManager()->getRepository(WhatsAppMessageHistory::class)
            ->createQueryBuilder('m')
            ->where('m.phoneNumber = :phone')
            ->setParameter('phone', $phoneNumber);
        
        if ($user !== null) {
            $qb->andWhere('m.oracleUser = :user')
               ->setParameter('user', $user);
        }
        
        return $qb->orderBy('m.timestamp', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByStatus(string $status, int $limit = 100): array
    {
        return $this->getEntityManager()->getRepository(WhatsAppMessageHistory::class)
            ->createQueryBuilder('m')
            ->where('m.status = :status')
            ->setParameter('status', $status)
            ->orderBy('m.timestamp', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * {@inheritdoc}
     */
    public function updateStatus(string $wabaMessageId, string $status, ?array $errorData = null): bool
    {
        $message = $this->findByWabaMessageId($wabaMessageId);
        
        if ($message === null) {
            return false;
        }
        
        $message->setStatus($status);
        
        if ($errorData !== null) {
            $message->setErrorCode($errorData['code'] ?? null);
            $message->setErrorMessage($errorData['message'] ?? null);
        }
        
        $this->getEntityManager()->flush();
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function countByUser(User $user, ?\DateTime $startDate = null, ?\DateTime $endDate = null): int
    {
        $qb = $this->getEntityManager()->getRepository(WhatsAppMessageHistory::class)
            ->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.oracleUser = :user')
            ->setParameter('user', $user);
        
        if ($startDate !== null) {
            $qb->andWhere('m.timestamp >= :startDate')
               ->setParameter('startDate', $startDate);
        }
        
        if ($endDate !== null) {
            $qb->andWhere('m.timestamp <= :endDate')
               ->setParameter('endDate', $endDate);
        }
        
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getStatistics(User $user, ?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $qb = $this->getEntityManager()->getRepository(WhatsAppMessageHistory::class)
            ->createQueryBuilder('m')
            ->where('m.oracleUser = :user')
            ->setParameter('user', $user);
        
        if ($startDate !== null) {
            $qb->andWhere('m.timestamp >= :startDate')
               ->setParameter('startDate', $startDate);
        }
        
        if ($endDate !== null) {
            $qb->andWhere('m.timestamp <= :endDate')
               ->setParameter('endDate', $endDate);
        }
        
        // Total des messages
        $total = (clone $qb)->select('COUNT(m.id)')->getQuery()->getSingleScalarResult();
        
        // Messages par direction
        $byDirection = (clone $qb)
            ->select('m.direction, COUNT(m.id) as count')
            ->groupBy('m.direction')
            ->getQuery()
            ->getResult();
        
        // Messages par statut
        $byStatus = (clone $qb)
            ->select('m.status, COUNT(m.id) as count')
            ->groupBy('m.status')
            ->getQuery()
            ->getResult();
        
        // Messages par type
        $byType = (clone $qb)
            ->select('m.type, COUNT(m.id) as count')
            ->groupBy('m.type')
            ->getQuery()
            ->getResult();
        
        return [
            'total' => (int) $total,
            'by_direction' => $this->formatGroupedResult($byDirection),
            'by_status' => $this->formatGroupedResult($byStatus),
            'by_type' => $this->formatGroupedResult($byType)
        ];
    }
    
    /**
     * Formater les résultats groupés
     * 
     * @param array $results
     * @return array
     */
    private function formatGroupedResult(array $results): array
    {
        $formatted = [];
        foreach ($results as $result) {
            // Doctrine retourne un tableau associatif avec les clés nommées
            if (isset($result['direction'])) {
                $key = $result['direction'];
            } elseif (isset($result['status'])) {
                $key = $result['status'];
            } elseif (isset($result['type'])) {
                $key = $result['type'];
            } else {
                $key = $result[0] ?? 'unknown';
            }
            $formatted[$key] = (int) $result['count'];
        }
        return $formatted;
    }
}