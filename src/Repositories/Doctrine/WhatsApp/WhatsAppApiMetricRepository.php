<?php

declare(strict_types=1);

namespace App\Repositories\Doctrine\WhatsApp;

use App\Entities\WhatsApp\WhatsAppApiMetric;
use App\Repositories\Interfaces\WhatsApp\WhatsAppApiMetricRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

/**
 * Repository Doctrine pour les métriques API WhatsApp
 */
class WhatsAppApiMetricRepository implements WhatsAppApiMetricRepositoryInterface
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;
    
    /**
     * @var EntityRepository
     */
    private EntityRepository $repository;
    
    /**
     * Constructeur
     * 
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(WhatsAppApiMetric::class);
    }
    
    /**
     * {@inheritdoc}
     */
    public function save(WhatsAppApiMetric $metric): WhatsAppApiMetric
    {
        $this->entityManager->persist($metric);
        $this->entityManager->flush();
        
        return $metric;
    }
    
    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return $this->repository->findBy($criteria, $orderBy, $limit, $offset);
    }
    
    /**
     * {@inheritdoc}
     */
    public function count(array $criteria): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('count(m.id)')
            ->from(WhatsAppApiMetric::class, 'm');
        
        foreach ($criteria as $field => $value) {
            if (str_contains($field, ' ')) {
                list($field, $operator) = explode(' ', $field, 2);
                $paramName = str_replace('.', '_', $field);
                $qb->andWhere("m.$field $operator :$paramName")
                    ->setParameter($paramName, $value);
            } else {
                $paramName = str_replace('.', '_', $field);
                $qb->andWhere("m.$field = :$paramName")
                    ->setParameter($paramName, $value);
            }
        }
        
        return (int)$qb->getQuery()->getSingleScalarResult();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAverageDuration(array $criteria): float
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('AVG(m.duration)')
            ->from(WhatsAppApiMetric::class, 'm');
        
        foreach ($criteria as $field => $value) {
            if (str_contains($field, ' ')) {
                list($field, $operator) = explode(' ', $field, 2);
                $paramName = str_replace('.', '_', $field);
                $qb->andWhere("m.$field $operator :$paramName")
                    ->setParameter($paramName, $value);
            } else {
                $paramName = str_replace('.', '_', $field);
                $qb->andWhere("m.$field = :$paramName")
                    ->setParameter($paramName, $value);
            }
        }
        
        $result = $qb->getQuery()->getSingleScalarResult();
        
        return $result !== null ? (float)$result : 0.0;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getP95Duration(array $criteria): float
    {
        // Récupérer toutes les durées correspondant aux critères
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('m.duration')
            ->from(WhatsAppApiMetric::class, 'm');
        
        foreach ($criteria as $field => $value) {
            if (str_contains($field, ' ')) {
                list($field, $operator) = explode(' ', $field, 2);
                $paramName = str_replace('.', '_', $field);
                $qb->andWhere("m.$field $operator :$paramName")
                    ->setParameter($paramName, $value);
            } else {
                $paramName = str_replace('.', '_', $field);
                $qb->andWhere("m.$field = :$paramName")
                    ->setParameter($paramName, $value);
            }
        }
        
        $result = $qb->getQuery()->getScalarResult();
        
        if (empty($result)) {
            return 0.0;
        }
        
        // Extraire les durées
        $durations = array_map(function ($item) {
            return (float)$item['duration'];
        }, $result);
        
        // Trier les durées
        sort($durations);
        
        // Calculer l'index du P95
        $p95Index = (int)ceil(count($durations) * 0.95) - 1;
        
        // Retourner le P95 ou 0 si pas assez de données
        return $p95Index >= 0 ? $durations[$p95Index] : 0.0;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getMetricsByDay(int $userId, \DateTime $startDate, ?\DateTime $endDate = null): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        $qb->select('DATE(m.createdAt) as day')
            ->addSelect('COUNT(m.id) as count')
            ->addSelect('AVG(m.duration) as avgDuration')
            ->addSelect('SUM(CASE WHEN m.success = true THEN 1 ELSE 0 END) as successful')
            ->addSelect('SUM(CASE WHEN m.success = false THEN 1 ELSE 0 END) as failed')
            ->from(WhatsAppApiMetric::class, 'm')
            ->where('m.userId = :userId')
            ->andWhere('m.createdAt >= :startDate')
            ->setParameter('userId', $userId)
            ->setParameter('startDate', $startDate)
            ->groupBy('day')
            ->orderBy('day', 'ASC');
        
        if ($endDate !== null) {
            $qb->andWhere('m.createdAt <= :endDate')
                ->setParameter('endDate', $endDate);
        }
        
        $result = $qb->getQuery()->getResult();
        
        // Formater les résultats
        $formattedResult = [];
        foreach ($result as $item) {
            $formattedResult[$item['day']] = [
                'day' => $item['day'],
                'count' => (int)$item['count'],
                'avg_duration' => round((float)$item['avgDuration'], 2),
                'successful' => (int)$item['successful'],
                'failed' => (int)$item['failed'],
                'success_rate' => (int)$item['count'] > 0 
                    ? round(((int)$item['successful'] / (int)$item['count']) * 100, 2)
                    : 0
            ];
        }
        
        return $formattedResult;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getMetricsByOperation(int $userId, \DateTime $startDate, ?\DateTime $endDate = null): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        $qb->select('m.operation')
            ->addSelect('COUNT(m.id) as count')
            ->addSelect('AVG(m.duration) as avgDuration')
            ->addSelect('SUM(CASE WHEN m.success = true THEN 1 ELSE 0 END) as successful')
            ->addSelect('SUM(CASE WHEN m.success = false THEN 1 ELSE 0 END) as failed')
            ->from(WhatsAppApiMetric::class, 'm')
            ->where('m.userId = :userId')
            ->andWhere('m.createdAt >= :startDate')
            ->setParameter('userId', $userId)
            ->setParameter('startDate', $startDate)
            ->groupBy('m.operation')
            ->orderBy('count', 'DESC');
        
        if ($endDate !== null) {
            $qb->andWhere('m.createdAt <= :endDate')
                ->setParameter('endDate', $endDate);
        }
        
        $result = $qb->getQuery()->getResult();
        
        // Formater les résultats
        $formattedResult = [];
        foreach ($result as $item) {
            $formattedResult[$item['operation']] = [
                'operation' => $item['operation'],
                'count' => (int)$item['count'],
                'avg_duration' => round((float)$item['avgDuration'], 2),
                'successful' => (int)$item['successful'],
                'failed' => (int)$item['failed'],
                'success_rate' => (int)$item['count'] > 0 
                    ? round(((int)$item['successful'] / (int)$item['count']) * 100, 2)
                    : 0
            ];
        }
        
        return $formattedResult;
    }
}