<?php

namespace Tests\Repositories\WhatsApp;

use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\Entities\User;
use App\Repositories\Doctrine\WhatsApp\WhatsAppMessageHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\NoResultException;

/**
 * Tests unitaires pour le repository WhatsAppMessageHistory
 * 
 * Ces tests se concentrent sur la méthode countByStatus ajoutée au repository.
 */
class WhatsAppMessageHistoryRepositoryTest extends TestCase
{
    /**
     * @var WhatsAppMessageHistoryRepository
     */
    private $repository;

    /**
     * @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityManager;

    /**
     * @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $queryBuilder;

    /**
     * @var Query|\PHPUnit\Framework\MockObject\MockObject
     */
    private $query;

    /**
     * Configuration des mocks et du repository avant chaque test
     */
    protected function setUp(): void
    {
        // Créer les mocks
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);
        
        // Configurer les mocks
        $this->entityManager->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->method('select')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->method('from')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->method('where')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->method('andWhere')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->method('setParameter')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->method('getQuery')
            ->willReturn($this->query);
            
        // Créer une classe mock pour le repository à tester
        $this->repository = $this->getMockBuilder(WhatsAppMessageHistoryRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntityManager'])
            ->getMock();
            
        $this->repository->method('getEntityManager')
            ->willReturn($this->entityManager);
    }

    /**
     * Test de la méthode countByStatus sans dates
     */
    public function testCountByStatusWithoutDates(): void
    {
        $userId = 1;
        $statuses = ['sent', 'delivered'];
        $expected = 42;
        
        // Configurer le comportement du mock de la requête
        $this->query->method('getSingleScalarResult')
            ->willReturn($expected);
        
        // Vérifier les appels attendus
        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with('COUNT(m.id)');
            
        $this->queryBuilder->expects($this->once())
            ->method('from')
            ->with(WhatsAppMessageHistory::class, 'm');
            
        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with('m.oracleUser = :userId');
            
        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('m.status IN (:statuses)');
            
        $this->queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['userId', $userId],
                ['statuses', $statuses]
            );
            
        // Appeler la méthode à tester
        $result = $this->repository->countByStatus($userId, $statuses);
        
        // Vérifier le résultat
        $this->assertEquals($expected, $result);
    }

    /**
     * Test de la méthode countByStatus avec startDate
     */
    public function testCountByStatusWithStartDate(): void
    {
        $userId = 1;
        $statuses = ['sent', 'delivered'];
        $startDate = new \DateTime('2025-05-01');
        $expected = 30;
        
        // Configurer le comportement du mock de la requête
        $this->query->method('getSingleScalarResult')
            ->willReturn($expected);
        
        // Configurer les attentes supplémentaires pour startDate
        $this->queryBuilder->expects($this->exactly(2))
            ->method('andWhere')
            ->withConsecutive(
                ['m.status IN (:statuses)'],
                ['m.timestamp >= :startDate']
            );
            
        $this->queryBuilder->expects($this->exactly(3))
            ->method('setParameter')
            ->withConsecutive(
                ['userId', $userId],
                ['statuses', $statuses],
                ['startDate', $startDate]
            );
            
        // Appeler la méthode à tester
        $result = $this->repository->countByStatus($userId, $statuses, $startDate);
        
        // Vérifier le résultat
        $this->assertEquals($expected, $result);
    }

    /**
     * Test de la méthode countByStatus avec endDate
     */
    public function testCountByStatusWithEndDate(): void
    {
        $userId = 1;
        $statuses = ['sent', 'delivered'];
        $endDate = new \DateTime('2025-05-31');
        $expected = 25;
        
        // Configurer le comportement du mock de la requête
        $this->query->method('getSingleScalarResult')
            ->willReturn($expected);
        
        // Configurer les attentes supplémentaires pour endDate
        $this->queryBuilder->expects($this->exactly(2))
            ->method('andWhere')
            ->withConsecutive(
                ['m.status IN (:statuses)'],
                ['m.timestamp <= :endDate']
            );
            
        $this->queryBuilder->expects($this->exactly(3))
            ->method('setParameter')
            ->withConsecutive(
                ['userId', $userId],
                ['statuses', $statuses],
                ['endDate', $endDate]
            );
            
        // Appeler la méthode à tester
        $result = $this->repository->countByStatus($userId, $statuses, null, $endDate);
        
        // Vérifier le résultat
        $this->assertEquals($expected, $result);
    }

    /**
     * Test de la méthode countByStatus avec startDate et endDate
     */
    public function testCountByStatusWithStartAndEndDate(): void
    {
        $userId = 1;
        $statuses = ['sent', 'delivered'];
        $startDate = new \DateTime('2025-05-01');
        $endDate = new \DateTime('2025-05-31');
        $expected = 20;
        
        // Configurer le comportement du mock de la requête
        $this->query->method('getSingleScalarResult')
            ->willReturn($expected);
        
        // Configurer les attentes supplémentaires pour startDate et endDate
        $this->queryBuilder->expects($this->exactly(3))
            ->method('andWhere')
            ->withConsecutive(
                ['m.status IN (:statuses)'],
                ['m.timestamp >= :startDate'],
                ['m.timestamp <= :endDate']
            );
            
        $this->queryBuilder->expects($this->exactly(4))
            ->method('setParameter')
            ->withConsecutive(
                ['userId', $userId],
                ['statuses', $statuses],
                ['startDate', $startDate],
                ['endDate', $endDate]
            );
            
        // Appeler la méthode à tester
        $result = $this->repository->countByStatus($userId, $statuses, $startDate, $endDate);
        
        // Vérifier le résultat
        $this->assertEquals($expected, $result);
    }

    /**
     * Test de la méthode countByStatus avec une exception de requête
     */
    public function testCountByStatusWithQueryException(): void
    {
        $userId = 1;
        $statuses = ['sent', 'delivered'];
        
        // Configurer le comportement du mock de la requête pour lancer une exception
        $this->query->method('getSingleScalarResult')
            ->willThrowException(new NoResultException());
        
        // Appeler la méthode à tester
        $result = $this->repository->countByStatus($userId, $statuses);
        
        // Vérifier le résultat
        $this->assertEquals(0, $result, 'La méthode devrait retourner 0 en cas d\'exception');
    }
}