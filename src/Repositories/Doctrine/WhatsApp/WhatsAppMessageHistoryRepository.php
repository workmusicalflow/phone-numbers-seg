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
 * 
 * @method WhatsAppMessageHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method WhatsAppMessageHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method WhatsAppMessageHistory[] findAll()
 * @method WhatsAppMessageHistory[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
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
    
    /**
     * {@inheritdoc}
     */
    public function countByStatus(int $userId, array $statuses, ?\DateTime $startDate = null, ?\DateTime $endDate = null): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COUNT(m.id)')
           ->from(WhatsAppMessageHistory::class, 'm')
           ->where('m.oracleUser = :userId')
           ->andWhere('m.status IN (:statuses)')
           ->setParameter('userId', $userId)
           ->setParameter('statuses', $statuses);
        
        // Ajouter le filtre de date de début si fourni
        if ($startDate !== null) {
            $qb->andWhere('m.timestamp >= :startDate')
               ->setParameter('startDate', $startDate);
        }
        
        // Ajouter le filtre de date de fin si fourni
        if ($endDate !== null) {
            $qb->andWhere('m.timestamp <= :endDate')
               ->setParameter('endDate', $endDate);
        }
        
        try {
            return (int) $qb->getQuery()->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            // Si aucun résultat trouvé, retourner 0
            return 0;
        } catch (\Exception $e) {
            // En cas d'erreur inattendue, logger l'erreur et retourner 0
            return 0;
        }
    }

    /**
     * Obtenir les insights WhatsApp pour un contact spécifique
     */
    public function getContactInsights(Contact $contact, User $user): array
    {
        $phoneNumber = $contact->getPhoneNumber();
        
        // Requête principale pour les statistiques de base
        $qb = $this->getEntityManager()->createQueryBuilder();
        $stats = $qb->select([
                'COUNT(m.id) as totalMessages',
                'SUM(CASE WHEN m.direction = :outgoing THEN 1 ELSE 0 END) as outgoingMessages',
                'SUM(CASE WHEN m.direction = :incoming THEN 1 ELSE 0 END) as incomingMessages',
                'SUM(CASE WHEN m.status = :delivered THEN 1 ELSE 0 END) as deliveredMessages',
                'SUM(CASE WHEN m.status = :read THEN 1 ELSE 0 END) as readMessages',
                'SUM(CASE WHEN m.status = :failed THEN 1 ELSE 0 END) as failedMessages'
            ])
            ->from(WhatsAppMessageHistory::class, 'm')
            ->where('m.phoneNumber = :phoneNumber')
            ->andWhere('m.oracleUser = :user')
            ->setParameter('phoneNumber', $phoneNumber)
            ->setParameter('user', $user)
            ->setParameter('outgoing', 'OUTGOING')
            ->setParameter('incoming', 'INCOMING')
            ->setParameter('delivered', 'delivered')
            ->setParameter('read', 'read')
            ->setParameter('failed', 'failed')
            ->getQuery()
            ->getSingleResult();

        // Dernier message
        $lastMessage = $this->getEntityManager()->createQueryBuilder()
            ->select('m')
            ->from(WhatsAppMessageHistory::class, 'm')
            ->where('m.phoneNumber = :phoneNumber')
            ->andWhere('m.oracleUser = :user')
            ->setParameter('phoneNumber', $phoneNumber)
            ->setParameter('user', $user)
            ->orderBy('m.timestamp', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        // Messages par type
        $messagesByType = $this->getEntityManager()->createQueryBuilder()
            ->select('m.type, COUNT(m.id) as count')
            ->from(WhatsAppMessageHistory::class, 'm')
            ->where('m.phoneNumber = :phoneNumber')
            ->andWhere('m.oracleUser = :user')
            ->setParameter('phoneNumber', $phoneNumber)
            ->setParameter('user', $user)
            ->groupBy('m.type')
            ->getQuery()
            ->getResult();

        // Messages par statut
        $messagesByStatus = $this->getEntityManager()->createQueryBuilder()
            ->select('m.status, COUNT(m.id) as count')
            ->from(WhatsAppMessageHistory::class, 'm')
            ->where('m.phoneNumber = :phoneNumber')
            ->andWhere('m.oracleUser = :user')
            ->setParameter('phoneNumber', $phoneNumber)
            ->setParameter('user', $user)
            ->groupBy('m.status')
            ->getQuery()
            ->getResult();

        // Templates utilisés (uniquement pour les messages sortants de type template)
        $templatesUsed = $this->getEntityManager()->createQueryBuilder()
            ->select('DISTINCT m.templateName')
            ->from(WhatsAppMessageHistory::class, 'm')
            ->where('m.phoneNumber = :phoneNumber')
            ->andWhere('m.oracleUser = :user')
            ->andWhere('m.direction = :outgoing')
            ->andWhere('m.type = :template')
            ->andWhere('m.templateName IS NOT NULL')
            ->setParameter('phoneNumber', $phoneNumber)
            ->setParameter('user', $user)
            ->setParameter('outgoing', 'OUTGOING')
            ->setParameter('template', 'template')
            ->getQuery()
            ->getSingleColumnResult();

        // Messages par mois - récupérer et traiter en PHP
        $sixMonthsAgo = new \DateTime('-6 months');
        $recentMessages = $this->getEntityManager()->createQueryBuilder()
            ->select('m.timestamp')
            ->from(WhatsAppMessageHistory::class, 'm')
            ->where('m.phoneNumber = :phoneNumber')
            ->andWhere('m.oracleUser = :user')
            ->andWhere('m.timestamp >= :sixMonthsAgo')
            ->setParameter('phoneNumber', $phoneNumber)
            ->setParameter('user', $user)
            ->setParameter('sixMonthsAgo', $sixMonthsAgo)
            ->getQuery()
            ->getResult();

        // Traitement en PHP pour les messages par mois
        $messagesByMonth = [];
        foreach ($recentMessages as $message) {
            $month = substr($message['timestamp']->format('Y-m-d'), 0, 7);
            if (!isset($messagesByMonth[$month])) {
                $messagesByMonth[$month] = 0;
            }
            $messagesByMonth[$month]++;
        }
        ksort($messagesByMonth);

        // Nombre de conversations (groupées par jour) - traitement en PHP
        $uniqueDays = [];
        foreach ($recentMessages as $message) {
            $day = $message['timestamp']->format('Y-m-d');
            $uniqueDays[$day] = true;
        }
        $conversationCount = count($uniqueDays);

        // Calcul des taux
        $totalOutgoing = (int) $stats['outgoingMessages'];
        $delivered = (int) $stats['deliveredMessages'];
        $read = (int) $stats['readMessages'];
        
        $deliveryRate = $totalOutgoing > 0 ? ($delivered / $totalOutgoing) * 100 : 0;
        $readRate = $totalOutgoing > 0 ? ($read / $totalOutgoing) * 100 : 0;

        return [
            'totalMessages' => (int) $stats['totalMessages'],
            'outgoingMessages' => $totalOutgoing,
            'incomingMessages' => (int) $stats['incomingMessages'],
            'deliveredMessages' => $delivered,
            'readMessages' => $read,
            'failedMessages' => (int) $stats['failedMessages'],
            'lastMessageDate' => $lastMessage ? $lastMessage->getTimestamp()->format('Y-m-d H:i:s') : null,
            'lastMessageType' => $lastMessage ? $lastMessage->getType() : null,
            'lastMessageContent' => $lastMessage ? $this->truncateContent($lastMessage->getContent()) : null,
            'templatesUsed' => array_filter($templatesUsed),
            'conversationCount' => (int) $conversationCount,
            'messagesByType' => $this->formatGroupedResult($messagesByType),
            'messagesByStatus' => $this->formatGroupedResult($messagesByStatus),
            'messagesByMonth' => $this->formatMonthlyResult($messagesByMonth),
            'deliveryRate' => round($deliveryRate, 2),
            'readRate' => round($readRate, 2)
        ];
    }

    /**
     * Tronquer le contenu des messages pour l'aperçu
     */
    private function truncateContent(?string $content): ?string
    {
        if ($content === null) {
            return null;
        }
        
        return strlen($content) > 100 ? substr($content, 0, 100) . '...' : $content;
    }

    /**
     * Formater les résultats mensuels
     */
    private function formatMonthlyResult(array $monthlyData): array
    {
        // Format attendu: ['2024-01' => 5, '2024-02' => 3]
        // On transforme en format GraphQL: {january: 5, february: 3}
        $formatted = [
            'january' => 0, 'february' => 0, 'march' => 0, 'april' => 0,
            'may' => 0, 'june' => 0, 'july' => 0, 'august' => 0,
            'september' => 0, 'october' => 0, 'november' => 0, 'december' => 0
        ];
        
        foreach ($monthlyData as $yearMonth => $count) {
            if (strlen($yearMonth) >= 7) { // Format YYYY-MM
                $month = (int) substr($yearMonth, 5, 2); // Extraire le mois
                $monthNames = [
                    1 => 'january', 2 => 'february', 3 => 'march', 4 => 'april',
                    5 => 'may', 6 => 'june', 7 => 'july', 8 => 'august',
                    9 => 'september', 10 => 'october', 11 => 'november', 12 => 'december'
                ];
                
                if (isset($monthNames[$month])) {
                    $formatted[$monthNames[$month]] = (int) $count;
                }
            }
        }
        
        return $formatted;
    }

    /**
     * Obtenir un résumé rapide des insights pour plusieurs contacts
     */
    public function getContactsInsightsSummary(array $contacts, User $user): array
    {
        if (empty($contacts)) {
            return [];
        }

        $phoneNumbers = array_map(fn($contact) => $contact->getPhoneNumber(), $contacts);
        
        // Requête groupée pour obtenir les statistiques de base pour tous les contacts
        $qb = $this->getEntityManager()->createQueryBuilder();
        $results = $qb->select([
                'm.phoneNumber',
                'COUNT(m.id) as totalMessages',
                'MAX(m.timestamp) as lastMessageDate'
            ])
            ->from(WhatsAppMessageHistory::class, 'm')
            ->where('m.phoneNumber IN (:phoneNumbers)')
            ->andWhere('m.oracleUser = :user')
            ->setParameter('phoneNumbers', $phoneNumbers)
            ->setParameter('user', $user)
            ->groupBy('m.phoneNumber')
            ->getQuery()
            ->getResult();

        // Reformater en tableau associatif par numéro de téléphone
        $summary = [];
        foreach ($results as $result) {
            $summary[$result['phoneNumber']] = [
                'totalMessages' => (int) $result['totalMessages'],
                'lastMessageDate' => $result['lastMessageDate'] ?? null
            ];
        }

        return $summary;
    }
}