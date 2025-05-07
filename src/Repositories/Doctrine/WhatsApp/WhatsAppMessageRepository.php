<?php

namespace App\Repositories\Doctrine\WhatsApp;

use App\Entities\WhatsApp\WhatsAppMessage;
use App\Repositories\Doctrine\BaseRepository;
use App\Repositories\Interfaces\WhatsApp\WhatsAppMessageRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use App\Repositories\Interfaces\SearchRepositoryInterface;

/**
 * Repository Doctrine pour les messages WhatsApp
 */
class WhatsAppMessageRepository extends BaseRepository implements WhatsAppMessageRepositoryInterface
{
    /**
     * Constructeur
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, WhatsAppMessage::class);
    }

    /**
     * Enregistre un message WhatsApp
     *
     * @param mixed $message
     * @return mixed
     */
    public function save($message)
    {
        if (!$message instanceof WhatsAppMessage) {
            throw new InvalidArgumentException('Expected instance of WhatsAppMessage');
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
            if (!$entity instanceof WhatsAppMessage) {
                throw new InvalidArgumentException('Expected instance of WhatsAppMessage');
            }
            $this->getEntityManager()->persist($entity);
        }
        
        $this->getEntityManager()->flush();
        return $entities;
    }

    /**
     * Recherche un message par son identifiant Meta
     *
     * @param string $messageId
     * @return WhatsAppMessage|null
     */
    public function findByMessageId(string $messageId): ?WhatsAppMessage
    {
        return $this->getEntityManager()->getRepository(WhatsAppMessage::class)
            ->findOneBy(['messageId' => $messageId]);
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
        
        return $queryBuilder->select('wm')
            ->from(WhatsAppMessage::class, 'wm')
            ->where('wm.sender = :sender')
            ->setParameter('sender', $sender)
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
        
        return $queryBuilder->select('wm')
            ->from(WhatsAppMessage::class, 'wm')
            ->where('wm.recipient = :recipient')
            ->setParameter('recipient', $recipient)
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
        
        return $queryBuilder->select('COUNT(wm.id)')
            ->from(WhatsAppMessage::class, 'wm')
            ->where('wm.sender = :sender')
            ->setParameter('sender', $sender)
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
            ->from(WhatsAppMessage::class, 'wm')
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
        
        return $queryBuilder->select('wm')
            ->from(WhatsAppMessage::class, 'wm')
            ->where('wm.timestamp >= :startTimestamp')
            ->andWhere('wm.timestamp <= :endTimestamp')
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
        return WhatsAppMessage::class;
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
            if (!$entity instanceof WhatsAppMessage) {
                throw new InvalidArgumentException('Expected instance of WhatsAppMessage');
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
            ->from(WhatsAppMessage::class, 'wm');
            
        // Définir les champs de recherche par défaut si non spécifiés
        if ($fields === null) {
            $fields = ['content', 'sender', 'recipient', 'type'];
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
        $queryBuilder->select('wm')
            ->from(WhatsAppMessage::class, 'wm');
            
        // Ajouter les critères de recherche
        $i = 0;
        foreach ($criteria as $field => $value) {
            $paramName = 'param_' . $i;
            if ($i === 0) {
                $queryBuilder->where("wm.$field = :$paramName");
            } else {
                $queryBuilder->andWhere("wm.$field = :$paramName");
            }
            $queryBuilder->setParameter($paramName, $value);
            $i++;
        }
        
        // Ajouter les critères de tri
        if ($orderBy) {
            foreach ($orderBy as $field => $direction) {
                $queryBuilder->addOrderBy("wm.$field", $direction);
            }
        } else {
            // Tri par défaut
            $queryBuilder->orderBy('wm.timestamp', 'DESC');
        }
        
        // Ajouter la limite et le décalage
        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }
        
        if ($offset) {
            $queryBuilder->setFirstResult($offset);
        }
        
        return $queryBuilder->getQuery()->getResult();
    }
}