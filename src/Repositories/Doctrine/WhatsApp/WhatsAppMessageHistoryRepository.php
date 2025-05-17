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
    
    /**
     * {@inheritdoc}
     */
    public function findByWithDateRange(array $criteria, array $dateFilters, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('m')
           ->from(WhatsAppMessageHistory::class, 'm');
        
        // Ajouter les critères standards
        foreach ($criteria as $field => $value) {
            if ($field === 'oracleUser') {
                $qb->andWhere('m.oracleUser = :' . $field)
                   ->setParameter($field, $value);
            } else {
                $qb->andWhere('m.' . $field . ' = :' . $field)
                   ->setParameter($field, $value);
            }
        }
        
        // Ajouter les filtres de date
        if (isset($dateFilters['startDate'])) {
            error_log('[Repository] Filtering with start date: ' . $dateFilters['startDate']->format('Y-m-d H:i:s'));
            $qb->andWhere('m.createdAt >= :startDate')
               ->setParameter('startDate', $dateFilters['startDate']);
        }
        
        if (isset($dateFilters['endDate'])) {
            error_log('[Repository] Filtering with end date: ' . $dateFilters['endDate']->format('Y-m-d H:i:s'));
            $qb->andWhere('m.createdAt <= :endDate')
               ->setParameter('endDate', $dateFilters['endDate']);
        }
        
        // Ajouter le tri
        if ($orderBy !== null) {
            foreach ($orderBy as $field => $direction) {
                $qb->orderBy('m.' . $field, $direction);
            }
        } else {
            $qb->orderBy('m.createdAt', 'DESC');
        }
        
        // Ajouter la pagination
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }
        
        $query = $qb->getQuery();
        error_log('[Repository] SQL Query: ' . $query->getSQL());
        
        // Log des paramètres de manière plus détaillée
        $params = [];
        foreach ($query->getParameters() as $param) {
            $value = $param->getValue();
            if ($value instanceof \DateTime) {
                $params[$param->getName()] = $value->format('Y-m-d H:i:s');
            } else {
                $params[$param->getName()] = $value;
            }
        }
        error_log('[Repository] Parameters: ' . json_encode($params));
        
        $results = $query->getResult();
        error_log('[Repository] Results count: ' . count($results));
        
        return $results;
    }
    
    /**
     * {@inheritdoc}
     */
    public function countWithDateRange(array $criteria, array $dateFilters): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COUNT(m.id)')
           ->from(WhatsAppMessageHistory::class, 'm');
        
        // Ajouter les critères standards
        foreach ($criteria as $field => $value) {
            if ($field === 'oracleUser') {
                $qb->andWhere('m.oracleUser = :' . $field)
                   ->setParameter($field, $value);
            } else {
                $qb->andWhere('m.' . $field . ' = :' . $field)
                   ->setParameter($field, $value);
            }
        }
        
        // Ajouter les filtres de date
        if (isset($dateFilters['startDate'])) {
            error_log('[Repository] Filtering with start date: ' . $dateFilters['startDate']->format('Y-m-d H:i:s'));
            $qb->andWhere('m.createdAt >= :startDate')
               ->setParameter('startDate', $dateFilters['startDate']);
        }
        
        if (isset($dateFilters['endDate'])) {
            error_log('[Repository] Filtering with end date: ' . $dateFilters['endDate']->format('Y-m-d H:i:s'));
            $qb->andWhere('m.createdAt <= :endDate')
               ->setParameter('endDate', $dateFilters['endDate']);
        }
        
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByWithFilters(array $criteria, array $dateFilters = [], ?string $phoneFilter = null, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('m')
           ->from(WhatsAppMessageHistory::class, 'm');
        
        // Ajouter les critères standards
        foreach ($criteria as $field => $value) {
            if ($field === 'oracleUser') {
                $qb->andWhere('m.oracleUser = :' . $field)
                   ->setParameter($field, $value);
            } else {
                $qb->andWhere('m.' . $field . ' = :' . $field)
                   ->setParameter($field, $value);
            }
        }
        
        // Ajouter le filtre de téléphone avec LIKE si présent
        if ($phoneFilter !== null) {
            error_log('[WhatsAppMessageHistoryRepository] Applying phone LIKE filter: ' . $phoneFilter);
            $qb->andWhere('m.phoneNumber LIKE :phoneFilter')
               ->setParameter('phoneFilter', '%' . $phoneFilter . '%');
        }
        
        // Ajouter les filtres de date
        if (isset($dateFilters['startDate'])) {
            error_log('[Repository] Filtering with start date: ' . $dateFilters['startDate']->format('Y-m-d H:i:s'));
            $qb->andWhere('m.createdAt >= :startDate')
               ->setParameter('startDate', $dateFilters['startDate']);
        }
        
        if (isset($dateFilters['endDate'])) {
            error_log('[Repository] Filtering with end date: ' . $dateFilters['endDate']->format('Y-m-d H:i:s'));
            $qb->andWhere('m.createdAt <= :endDate')
               ->setParameter('endDate', $dateFilters['endDate']);
        }
        
        // Ajouter le tri
        if ($orderBy !== null) {
            foreach ($orderBy as $field => $direction) {
                $qb->orderBy('m.' . $field, $direction);
            }
        } else {
            $qb->orderBy('m.createdAt', 'DESC');
        }
        
        // Ajouter la pagination
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }
        
        // Log de la requête SQL
        $query = $qb->getQuery();
        error_log('[Repository] SQL Query: ' . $query->getSQL());
        
        // Log des paramètres de manière plus détaillée
        $params = [];
        foreach ($query->getParameters() as $param) {
            $value = $param->getValue();
            if ($value instanceof \DateTime) {
                $params[$param->getName()] = $value->format('Y-m-d H:i:s');
            } else {
                $params[$param->getName()] = $value;
            }
        }
        error_log('[Repository] Parameters: ' . json_encode($params));
        
        $results = $query->getResult();
        error_log('[Repository] Results count: ' . count($results));
        
        return $results;
    }
    
    /**
     * {@inheritdoc}
     */
    public function countWithFilters(array $criteria, array $dateFilters = [], ?string $phoneFilter = null): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COUNT(m.id)')
           ->from(WhatsAppMessageHistory::class, 'm');
        
        // Ajouter les critères standards
        foreach ($criteria as $field => $value) {
            if ($field === 'oracleUser') {
                $qb->andWhere('m.oracleUser = :' . $field)
                   ->setParameter($field, $value);
            } else {
                $qb->andWhere('m.' . $field . ' = :' . $field)
                   ->setParameter($field, $value);
            }
        }
        
        // Ajouter le filtre de téléphone avec LIKE si présent
        if ($phoneFilter !== null) {
            error_log('[WhatsAppMessageHistoryRepository] Applying phone LIKE filter for count: ' . $phoneFilter);
            $qb->andWhere('m.phoneNumber LIKE :phoneFilter')
               ->setParameter('phoneFilter', '%' . $phoneFilter . '%');
        }
        
        // Ajouter les filtres de date
        if (isset($dateFilters['startDate'])) {
            $qb->andWhere('m.createdAt >= :startDate')
               ->setParameter('startDate', $dateFilters['startDate']);
        }
        
        if (isset($dateFilters['endDate'])) {
            $qb->andWhere('m.createdAt <= :endDate')
               ->setParameter('endDate', $dateFilters['endDate']);
        }
        
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}