<?php

namespace App\Repositories\Doctrine\WhatsApp;

use App\Entities\WhatsApp\WhatsAppMessageHistory; // Changed
use App\Repositories\Doctrine\BaseRepository;
use App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface; // Interface name implies History
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\LockMode; // Added import
use InvalidArgumentException;
use App\Repositories\Interfaces\SearchRepositoryInterface;

/**
 * Repository Doctrine pour les messages WhatsApp
 */
class WhatsAppMessageRepository extends BaseRepository implements WhatsAppMessageHistoryRepositoryInterface // Interface name implies History
{
    /**
     * Constructeur
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, WhatsAppMessageHistory::class); // Changed
    }

    /**
     * Enregistre un message WhatsApp
     *
     * @param mixed $message
     * @return mixed
     */
    public function save($message)
    {
        if (!$message instanceof WhatsAppMessageHistory) { // Changed
            throw new InvalidArgumentException('Expected instance of WhatsAppMessageHistory'); // Changed
        }
        $this->getEntityManager()->persist($message);
        $this->getEntityManager()->flush();
        return $message;
    }

    /**
     * Sauvegarde plusieurs messages WhatsApp
     *
     * @param array $entities
     * @return array
     */
    public function saveMany(array $entities): array
    {
        foreach ($entities as $entity) {
            if (!$entity instanceof WhatsAppMessageHistory) { // Changed
                throw new InvalidArgumentException('Expected instance of WhatsAppMessageHistory'); // Changed
            }
            $this->getEntityManager()->persist($entity);
        }

        $this->getEntityManager()->flush();
        return $entities;
    }

    /**
     * Recherche un message par son identifiant Meta
     *
     * @param string $wabaMessageId
     * @return WhatsAppMessageHistory|null
     */
    public function findByWabaMessageId(string $wabaMessageId): ?WhatsAppMessageHistory // Changed method name to match interface
    {
        return $this->getEntityManager()->getRepository(WhatsAppMessageHistory::class) // Changed
            ->findOneBy(['wabaMessageId' => $wabaMessageId]); // Corrected field name based on WhatsAppMessageHistory entity
    }

    /**
     * Récupère tous les messages d'un expéditeur
     *
     * @param string $sender
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findBySender(string $sender, int $limit = 50, int $offset = 0): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        // Assuming 'sender' corresponds to 'phoneNumber' for outgoing, or a related field.
        // WhatsAppMessageHistory does not have a direct 'sender' field.
        // This method might need re-evaluation based on how 'sender' is defined for WhatsAppMessageHistory.
        // For now, let's assume it refers to phoneNumber for outgoing messages.
        return $queryBuilder->select('wm')
            ->from(WhatsAppMessageHistory::class, 'wm') // Changed
            ->where('wm.phoneNumber = :sender AND wm.direction = :direction') // Adjusted for WhatsAppMessageHistory
            ->setParameter('sender', $sender)
            ->setParameter('direction', WhatsAppMessageHistory::DIRECTION_OUTBOUND) // Assuming sender means outgoing
            ->orderBy('wm.timestamp', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les messages d'un destinataire
     *
     * @param string $recipient
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findByRecipient(string $recipient, int $limit = 50, int $offset = 0): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        // Assuming 'recipient' corresponds to 'phoneNumber' for incoming messages.
        return $queryBuilder->select('wm')
            ->from(WhatsAppMessageHistory::class, 'wm') // Changed
            ->where('wm.phoneNumber = :recipient AND wm.direction = :direction') // Adjusted for WhatsAppMessageHistory
            ->setParameter('recipient', $recipient)
            ->setParameter('direction', WhatsAppMessageHistory::DIRECTION_INBOUND) // Assuming recipient means incoming
            ->orderBy('wm.timestamp', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de messages pour un expéditeur
     *
     * @param string $sender
     * @return int
     */
    public function countBySender(string $sender): int
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        return (int) $queryBuilder->select('COUNT(wm.id)') // Cast to int
            ->from(WhatsAppMessageHistory::class, 'wm') // Changed
            ->where('wm.phoneNumber = :sender AND wm.direction = :direction') // Adjusted
            ->setParameter('sender', $sender)
            ->setParameter('direction', WhatsAppMessageHistory::DIRECTION_OUTBOUND) // Assuming sender means outgoing
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les messages par type
     *
     * @param string $type
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findByType(string $type, int $limit = 50, int $offset = 0): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        return $queryBuilder->select('wm')
            ->from(WhatsAppMessageHistory::class, 'wm') // Changed
            ->where('wm.type = :type')
            ->setParameter('type', $type)
            ->orderBy('wm.timestamp', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les messages dans une plage de dates
     *
     * @param int $startTimestamp
     * @param int $endTimestamp
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findByDateRange(int $startTimestamp, int $endTimestamp, int $limit = 50, int $offset = 0): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        // Convert timestamps to DateTime objects for comparison if 'timestamp' field is DateTime
        $startDate = (new \DateTime())->setTimestamp($startTimestamp);
        $endDate = (new \DateTime())->setTimestamp($endTimestamp);

        return $queryBuilder->select('wm')
            ->from(WhatsAppMessageHistory::class, 'wm') // Changed
            ->where('wm.timestamp >= :startTimestamp')
            ->andWhere('wm.timestamp <= :endTimestamp')
            ->setParameter('startTimestamp', $startDate) // Use DateTime objects
            ->setParameter('endTimestamp', $endDate)   // Use DateTime objects
            ->setParameter('startTimestamp', $startTimestamp)
            ->setParameter('endTimestamp', $endTimestamp)
            ->orderBy('wm.timestamp', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne le nom de la classe d'entité gérée par ce repository
     * 
     * @return string Le nom de la classe d'entité
     */
    public function getEntityClassName(): string
    {
        return WhatsAppMessageHistory::class; // Changed
    }

    /**
     * Supprime plusieurs entités en une seule opération
     * 
     * @param array $entities Les entités à supprimer
     * @return bool
     */
    public function deleteMany(array $entities): bool
    {
        foreach ($entities as $entity) {
            if (!$entity instanceof WhatsAppMessageHistory) { // Changed
                throw new InvalidArgumentException('Expected instance of WhatsAppMessageHistory'); // Changed
            }
            $this->getEntityManager()->remove($entity);
        }

        $this->getEntityManager()->flush();
        return true;
    }

    /**
     * Recherche des entités par une requête textuelle
     * 
     * @param string $query La requête de recherche
     * @param array|null $fields Les champs à rechercher
     * @param int|null $limit Limite le nombre d'entités retournées
     * @param int|null $offset Décalage pour la pagination
     * @return array Les entités trouvées
     */
    public function search(string $query, ?array $fields = null, ?int $limit = null, ?int $offset = null): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('wm')
            ->from(WhatsAppMessageHistory::class, 'wm'); // Changed

        // Définir les champs de recherche par défaut si non spécifiés, adaptés à WhatsAppMessageHistory
        if ($fields === null) {
            // 'sender' and 'recipient' are not direct fields in WhatsAppMessageHistory.
            // Assuming search on 'phoneNumber' and 'content' and 'type'.
            $fields = ['content', 'phoneNumber', 'type', 'wabaMessageId'];
        }

        // Construire la clause WHERE pour la recherche textuelle
        $whereExpressions = [];
        $parameters = [];

        foreach ($fields as $index => $field) {
            $paramName = 'query_' . $index;
            $whereExpressions[] = "wm.$field LIKE :$paramName";
            $parameters[$paramName] = '%' . $query . '%';
        }

        if (count($whereExpressions) > 0) {
            $queryBuilder->where(implode(' OR ', $whereExpressions));
            foreach ($parameters as $key => $value) {
                $queryBuilder->setParameter($key, $value);
            }
        }

        // Tri par défaut
        $queryBuilder->orderBy('wm.timestamp', 'DESC');

        // Ajouter la limite et le décalage
        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }

        if ($offset) {
            $queryBuilder->setFirstResult($offset);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Recherche des entités selon des critères donnés
     * 
     * @param array $criteria Les critères de recherche
     * @param array|null $orderBy Les critères de tri (optionnel)
     * @param int|null $limit Limite de résultats (optionnel)
     * @param int|null $offset Décalage (optionnel)
     * @return array Les entités trouvées
     */
    public function findByCriteria(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('e') // Changed alias for consistency
            ->from(WhatsAppMessageHistory::class, 'e'); // Changed, Changed alias

        // Ajouter les critères de recherche
        $paramIndex = 0; // Renamed for clarity
        foreach ($criteria as $field => $value) {
            $paramName = 'critval_' . $paramIndex++; // Renamed for clarity
            // Handle 'oracleUser' specifically if it's an object
            if ($field === 'oracleUser' && $value instanceof \App\Entities\User) {
                // Assuming 'oracleUser' is a mapped association in WhatsAppMessageHistory entity
                $queryBuilder->andWhere($queryBuilder->expr()->eq('e.oracleUser', ':' . $paramName)); // Changed alias
                $queryBuilder->setParameter($paramName, $value); // Pass the User entity directly if it's an association
            } elseif ($value === null) {
                $queryBuilder->andWhere($queryBuilder->expr()->isNull("e.$field")); // Changed alias
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->eq("e.$field", ":$paramName")); // Changed alias
                $queryBuilder->setParameter($paramName, $value);
            }
        }

        // Ajouter les critères de tri
        if ($orderBy !== null) { // Check for null explicitly
            foreach ($orderBy as $field => $direction) {
                $queryBuilder->addOrderBy("e.$field", $direction); // Changed alias
            }
        } else {
            // Tri par défaut
            $queryBuilder->orderBy('e.timestamp', 'DESC'); // Changed alias
        }

        // Ajouter la limite et le décalage
        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        if ($offset !== null) {
            $queryBuilder->setFirstResult($offset);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Trouver un message par son ID.
     *
     * @param mixed $id
     * @param LockMode|int|null $lockMode
     * @param int|null $lockVersion
     * @return object|null
     */
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object
    {
        return $this->getEntityManager()->find(WhatsAppMessageHistory::class, $id, $lockMode, $lockVersion);
    }

    /**
     * Trouver des messages par un ensemble de critères.
     *
     * @param array $criteria
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return WhatsAppMessageHistory[]
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array // Changed return type
    {
        // This can directly use the existing findByCriteria or Doctrine's own findBy
        // For consistency with how criteria might be handled (e.g. oracleUser object),
        // let's adapt the logic from findByCriteria or call it.
        // Re-implementing here for clarity based on typical Doctrine findBy needs.

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('e')
            ->from($this->getEntityClassName(), 'e');

        $paramIndex = 0;
        foreach ($criteria as $field => $value) {
            $paramName = 'critval_' . $paramIndex++;
            if ($field === 'oracleUser' && $value instanceof \App\Entities\User) {
                // Assuming 'oracleUser' is a mapped association in WhatsAppMessage entity
                $qb->andWhere($qb->expr()->eq('e.oracleUser', ':' . $paramName));
                $qb->setParameter($paramName, $value); // Pass the User entity directly if it's an association
            } elseif ($field === 'phoneNumber' && is_string($value) && $value !== '') {
                // Use LIKE for partial phone number matching
                $qb->andWhere($qb->expr()->like('e.phoneNumber', ':' . $paramName));
                $qb->setParameter($paramName, '%' . $value . '%');
            } elseif ($value === null) {
                $qb->andWhere($qb->expr()->isNull('e.' . $field));
            } else {
                $qb->andWhere($qb->expr()->eq('e.' . $field, ':' . $paramName));
                $qb->setParameter($paramName, $value);
            }
        }

        if ($orderBy !== null) {
            foreach ($orderBy as $sort => $order) {
                $qb->addOrderBy('e.' . $sort, $order);
            }
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compter les messages par un ensemble de critères.
     *
     * @param array $criteria
     * @return int
     */
    public function count(array $criteria = []): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select($qb->expr()->count('e.id'))
            ->from($this->getEntityClassName(), 'e');

        $paramIndex = 0;
        foreach ($criteria as $field => $value) {
            $paramName = 'countval_' . $paramIndex++;
            if ($field === 'oracleUser' && $value instanceof \App\Entities\User) {
                $qb->andWhere($qb->expr()->eq('e.oracleUser', ':' . $paramName));
                $qb->setParameter($paramName, $value);
            } elseif ($value === null) {
                $qb->andWhere($qb->expr()->isNull('e.' . $field));
            } else {
                $qb->andWhere($qb->expr()->eq('e.' . $field, ':' . $paramName));
                $qb->setParameter($paramName, $value);
            }
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouver un message par un ensemble de critères, retournant le premier résultat.
     *
     * @param array $criteria
     * @param array|null $orderBy
     * @return object|null
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object // Changed
    {
        // This can directly use Doctrine's own findOneBy
        // The existing findBy method in this class has custom logic for 'oracleUser'
        // which might not be what parent::findOneBy does directly if 'oracleUser' is just an ID.
        // For simplicity and directness, using the parent's findOneBy.
        // If specific handling for 'oracleUser' as an entity is needed here,
        // the logic from the custom findBy would need to be adapted.
        return $this->getEntityManager()->getRepository($this->getEntityClassName())->findOneBy($criteria, $orderBy);
    }

    // Implementation for methods from WhatsAppMessageHistoryRepositoryInterface
    // Note: findByMessageId was already implemented, but its return type was WhatsAppMessage, now it's WhatsAppMessageHistory.
    // The existing findByMessageId uses 'messageId' as criteria key, WhatsAppMessageHistory uses 'wabaMessageId'. This was corrected.

    public function findByUser(\App\Entities\User $user, int $limit = 50, int $offset = 0): array
    {
        return $this->findBy(['oracleUser' => $user], ['timestamp' => 'DESC'], $limit, $offset);
    }

    public function findByContact(\App\Entities\Contact $contact, int $limit = 50, int $offset = 0): array
    {
        return $this->findBy(['contact' => $contact], ['timestamp' => 'DESC'], $limit, $offset);
    }

    // findByPhoneNumber was not in the original WhatsAppMessageRepository, adding it.
    public function findByPhoneNumber(string $phoneNumber, ?\App\Entities\User $user = null, int $limit = 50, int $offset = 0): array
    {
        $criteria = ['phoneNumber' => $phoneNumber];
        if ($user !== null) {
            $criteria['oracleUser'] = $user;
        }
        return $this->findBy($criteria, ['timestamp' => 'DESC'], $limit, $offset);
    }

    public function findByStatus(string $status, int $limit = 100): array
    {
        return $this->findBy(['status' => $status], ['timestamp' => 'DESC'], $limit);
    }

    public function updateStatus(string $wabaMessageId, string $status, ?array $errorData = null): bool
    {
        $message = $this->findByWabaMessageId($wabaMessageId);
        if ($message) {
            $message->setStatus($status);
            if ($errorData) {
                $message->setErrorCode($errorData['code'] ?? null);
                $message->setErrorMessage($errorData['message'] ?? null);
                // Potentially log more detailed errors if $errorData contains more
                $message->setErrors($errorData['errors'] ?? null);
            }
            $this->save($message);
            return true;
        }
        return false;
    }

    public function countByUser(\App\Entities\User $user, ?\DateTime $startDate = null, ?\DateTime $endDate = null): int
    {
        $criteria = ['oracleUser' => $user];
        if ($startDate) {
            // Add criteria for startDate - requires QueryBuilder
            // $criteria['timestamp >='] = $startDate; // This simple form won't work with findBy
        }
        if ($endDate) {
            // Add criteria for endDate - requires QueryBuilder
            // $criteria['timestamp <='] = $endDate; // This simple form won't work with findBy
        }
        // This needs a more complex query for date ranges. For now, counting all by user.
        // A proper implementation would use QueryBuilder.

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select($qb->expr()->count('e.id'))
            ->from($this->getEntityClassName(), 'e')
            ->where($qb->expr()->eq('e.oracleUser', ':user'));
        $qb->setParameter('user', $user);

        if ($startDate) {
            $qb->andWhere($qb->expr()->gte('e.timestamp', ':startDate'));
            $qb->setParameter('startDate', $startDate);
        }
        if ($endDate) {
            $qb->andWhere($qb->expr()->lte('e.timestamp', ':endDate'));
            $qb->setParameter('endDate', $endDate);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getStatistics(\App\Entities\User $user, ?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        // Placeholder - requires complex query logic
        // Example: count messages by status, type, direction within a date range for a user
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('e.status, COUNT(e.id) as count')
            ->from($this->getEntityClassName(), 'e')
            ->where($qb->expr()->eq('e.oracleUser', ':user'))
            ->groupBy('e.status');
        $qb->setParameter('user', $user);

        if ($startDate) {
            $qb->andWhere($qb->expr()->gte('e.timestamp', ':startDate'));
            $qb->setParameter('startDate', $startDate);
        }
        if ($endDate) {
            $qb->andWhere($qb->expr()->lte('e.timestamp', ':endDate'));
            $qb->setParameter('endDate', $endDate);
        }

        $results = $qb->getQuery()->getResult();
        $stats = [];
        foreach ($results as $row) {
            $stats[$row['status']] = $row['count'];
        }
        return $stats; // Example: ['sent' => 10, 'delivered' => 8, 'failed' => 2]
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
            $qb->andWhere('m.createdAt >= :startDate')
               ->setParameter('startDate', $dateFilters['startDate']);
        }
        
        if (isset($dateFilters['endDate'])) {
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
        
        return $qb->getQuery()->getResult();
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
            error_log('[WhatsAppMessageRepository] Applying phone LIKE filter: ' . $phoneFilter);
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
        $sql = $qb->getQuery()->getSQL();
        $parameters = $qb->getQuery()->getParameters();
        error_log('[WhatsAppMessageRepository] SQL Query: ' . $sql);
        foreach ($parameters as $param) {
            error_log('[WhatsAppMessageRepository] Parameter ' . $param->getName() . ': ' . $param->getValue());
        }
        
        return $qb->getQuery()->getResult();
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
            error_log('[WhatsAppMessageRepository] Applying phone LIKE filter for count: ' . $phoneFilter);
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
