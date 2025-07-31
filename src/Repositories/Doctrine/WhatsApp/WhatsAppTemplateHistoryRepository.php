<?php

declare(strict_types=1);

namespace App\Repositories\Doctrine\WhatsApp;

use App\Entities\WhatsApp\WhatsAppTemplateHistory;
use App\Repositories\Doctrine\BaseRepository;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateHistoryRepositoryInterface;
use App\Entities\User;
use Doctrine\ORM\EntityManagerInterface;

class WhatsAppTemplateHistoryRepository extends BaseRepository implements WhatsAppTemplateHistoryRepositoryInterface
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, WhatsAppTemplateHistory::class);
    }

    /**
     * {@inheritDoc}
     */
    public function findByUser(User $user, ?int $limit = null, ?int $offset = null): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('th')
            ->from($this->entityClass, 'th')
            ->where('th.oracleUser = :user')
            ->orderBy('th.usedAt', 'DESC')
            ->setParameter('user', $user);

        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        if ($offset !== null) {
            $queryBuilder->setFirstResult($offset);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * {@inheritDoc}
     */
    public function countByUser(User $user): int
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('COUNT(th.id)')
            ->from($this->entityClass, 'th')
            ->where('th.oracleUser = :user')
            ->setParameter('user', $user);

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * {@inheritDoc}
     */
    public function findByUserWithFilters(
        User $user,
        array $criteria = [],
        array $orderBy = ['usedAt' => 'DESC'],
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('th')
            ->from($this->entityClass, 'th')
            ->where('th.oracleUser = :user')
            ->setParameter('user', $user);

        // Ajouter les critères de filtrage
        $i = 0;
        foreach ($criteria as $field => $value) {
            if ($field === 'phoneNumber' && is_string($value)) {
                // Traitement spécial pour la recherche partielle de numéro de téléphone
                $queryBuilder->andWhere('th.phoneNumber LIKE :phoneNumber')
                    ->setParameter('phoneNumber', '%' . $value . '%');
            } elseif ($field === 'templateName' && is_string($value)) {
                // Traitement spécial pour la recherche partielle de nom de template
                $queryBuilder->andWhere('th.templateName LIKE :templateName')
                    ->setParameter('templateName', '%' . $value . '%');
            } else {
                $paramName = 'param' . $i++;
                $queryBuilder->andWhere('th.' . $field . ' = :' . $paramName)
                    ->setParameter($paramName, $value);
            }
        }

        // Ajouter les critères de tri
        foreach ($orderBy as $field => $direction) {
            $queryBuilder->addOrderBy('th.' . $field, $direction);
        }

        // Limiter les résultats
        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        if ($offset !== null) {
            $queryBuilder->setFirstResult($offset);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * {@inheritDoc}
     */
    public function findByTemplateId(string $templateId, ?User $user = null, ?int $limit = null): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('th')
            ->from($this->entityClass, 'th')
            ->where('th.templateId = :templateId')
            ->orderBy('th.usedAt', 'DESC')
            ->setParameter('templateId', $templateId);

        if ($user) {
            $queryBuilder->andWhere('th.oracleUser = :user')
                ->setParameter('user', $user);
        }

        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * {@inheritDoc}
     */
    public function getMostUsedTemplates(User $user, int $limit = 5): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('th.templateId, COUNT(th.id) as useCount, MAX(th.usedAt) as lastUsed')
            ->from($this->entityClass, 'th')
            ->where('th.oracleUser = :user')
            ->groupBy('th.templateId')
            ->orderBy('useCount', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('user', $user);

        $results = $queryBuilder->getQuery()->getResult();

        // Transformer les résultats en tableau associatif
        $mostUsed = [];
        foreach ($results as $result) {
            $mostUsed[$result['templateId']] = [
                'count' => $result['useCount'],
                'lastUsed' => $result['lastUsed']
            ];
        }

        return $mostUsed;
    }

    /**
     * {@inheritDoc}
     */
    public function getCommonParameterValues(string $templateId, User $user): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('th.parameters')
            ->from($this->entityClass, 'th')
            ->where('th.templateId = :templateId')
            ->andWhere('th.oracleUser = :user')
            ->andWhere('th.parameters IS NOT NULL')
            ->orderBy('th.usedAt', 'DESC')
            ->setMaxResults(10)
            ->setParameter('templateId', $templateId)
            ->setParameter('user', $user);

        $results = $queryBuilder->getQuery()->getResult();

        // Analyser les résultats pour obtenir les valeurs les plus courantes par position
        $valuesByPosition = [];
        $frequencyByPosition = [];

        foreach ($results as $result) {
            $parameters = $result['parameters'];
            
            if (is_array($parameters)) {
                foreach ($parameters as $position => $value) {
                    if (!isset($valuesByPosition[$position])) {
                        $valuesByPosition[$position] = [];
                        $frequencyByPosition[$position] = [];
                    }
                    
                    if (!isset($frequencyByPosition[$position][$value])) {
                        $frequencyByPosition[$position][$value] = 0;
                    }
                    
                    $frequencyByPosition[$position][$value]++;
                    
                    if (!in_array($value, $valuesByPosition[$position])) {
                        $valuesByPosition[$position][] = $value;
                    }
                }
            }
        }

        // Trier les valeurs par fréquence d'utilisation
        foreach ($valuesByPosition as $position => $values) {
            // Trier les valeurs par fréquence d'utilisation
            usort($values, function ($a, $b) use ($frequencyByPosition, $position) {
                return $frequencyByPosition[$position][$b] <=> $frequencyByPosition[$position][$a];
            });
            
            // Limiter à 5 valeurs par position
            $valuesByPosition[$position] = array_slice($values, 0, 5);
        }

        return $valuesByPosition;
    }
}